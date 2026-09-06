# Plan de correo (Mallas Arica)

> **Estado:** decisión tomada, implementación pendiente — Sprint 5c del plan de ejecución (`CLAUDE.md` §8).
> **Audiencia:** instancia de Claude ejecutando en local sobre el repo `mallas-arica`.
> **Precondición de lectura:** `CLAUDE.md` (§4.5 persistencia del lead, §4.7 PDF, §5 esquema) y `plan-cicd.md` (§3.5 supervisord, §4.1 compose, §9 deuda técnica).

---

## 0. TL;DR de la decisión

| Función | Solución elegida | Costo |
|---|---|---|
| **Recepción** de correo en `@mallasarica.cl` | Cloudflare Email Routing (reenvío al Gmail del dueño) | $0 |
| **Envío transaccional** desde la app | Resend (relay API/SMTP, dominio verificado con DKIM) | $0 hasta 3.000/mes, 100/día |
| **Respuestas del dueño** con remitente corporativo | Gmail → "Enviar correo como", autenticado contra el SMTP de Resend | $0 |
| **Servidor de correo en el VPS** | **Descartado** | — |

Costo total: **$0/mes**. Ruta de salida definida en §9 (Zoho Mail Lite, USD 12/año) sin tocar el envío transaccional.

---

## 1. Contexto y descarte del self-hosting

El VPS (OpenCloud, 4 GB RAM, 2 vCPU compartidos) ya corre Traefik, MariaDB, Redis, Portainer, Uptime Kuma, Dozzle y dos apps. Montar un MTA propio se descarta por:

1. **Puerto 25 saliente** bloqueado por defecto en la mayoría de proveedores VPS. Sin él no hay entrega directa.
2. **Reputación de IP**: IP de datacenter, nueva y probablemente en rango compartido → Gmail/Outlook la mandan a spam o la rechazan. Recuperar reputación requiere volumen sostenido que este negocio no genera (~20-100 correos/mes).
3. **PTR/rDNS** lo controla el proveedor.
4. **Requisitos de remitente masivo de Gmail/Yahoo**: SPF + DKIM + DMARC obligatorios y tasa de queja <0,3%. Cumplirlos con IP propia es mantención permanente.
5. **RAM**: Mailcow pide ~6 GB; docker-mailserver ~1 GB. Ambos compiten con el `innodb_buffer_pool_size` de MariaDB en una máquina de 4 GB.
6. **Superficie de ataque y backups** de buzones que hoy nadie va a mantener.

Traefik es irrelevante aquí: SMTP es TCP 25/465/587, no pasa por el proxy HTTP.

---

## 2. Arquitectura resultante

```
                       ┌──────────── Cloudflare DNS (mallasarica.cl) ────────────┐
                       │  MX      → Email Routing                                │
                       │  TXT SPF, CNAME DKIM (Resend), TXT _dmarc               │
                       └──────────────────────────────────────────────────────────┘
                                  │ recibe                    ▲ envía
                                  ▼                           │
   Cliente ──► contacto@mallasarica.cl / ventas@mallasarica.cl ──► Gmail personal del dueño
      ▲                                          │
      │                                          │ responde con "Enviar como"
      └──────────────────────────────────────────┘  (SMTP de Resend, From corporativo)


   Formulario de contacto en el sitio
      │
      ▼
   persistirCotizacion() / SolicitudContacto::enviar()  ── BD = fuente de verdad (estado `borrador`)
      │
      └─► dispatch(EnviarNotificacionesCotizacion) ──► Redis (cola)
                                                        │
                                                        ▼
                                          worker (supervisord, numprocs=1)
                                                        │
                                    ┌────────────────────┴────────────────────┐
                                    ▼                                       ▼
                    NuevaCotizacionAdmin                      CopiaCotizacionCliente
                    to: MAIL_ADMIN_ADDRESS                    to: cotizacion.email (si existe)
                    reply-to: correo del cliente              adjunto: PDF §4.7
```

**Invariante no negociable:** el correo es un efecto secundario. Un fallo de Resend, de Redis o del worker **nunca** puede impedir que la cotización/solicitud quede persistida ni que el cliente sea redirigido a WhatsApp.

---

## 3. Configuración de Cloudflare (manual, fuera del repo)

Estos pasos los ejecuta el humano en el panel, no la instancia local. Se documentan aquí para que el plan quede completo.

### 3.1 Prerrequisito: NS de `mallasarica.cl` en Cloudflare

Email Routing exige que el dominio use los nameservers de Cloudflare (no funciona en modo CNAME/partial). Cambiar los NS en NIC Chile. **Esto se solapa con la migración de dominio pendiente** (`plan-cicd.md` §9: `mallas.tinorte.cl` → `mallasarica.cl`) → conviene hacer ambos en la misma ventana.

> El sitio puede seguir sirviéndose en `mallas.tinorte.cl` mientras el correo ya opera en `mallasarica.cl`. Son independientes.

### 3.2 Email Routing

Ubicación en el panel: **Compute → Email Service → Email Routing** (Cloudflare movió la sección; ya no está en un menú "Email" de primer nivel).

- Verificar la dirección de destino (el Gmail del dueño). La verificación llega por correo; revisar spam.
- Cloudflare inserta los registros MX y el SPF de routing automáticamente. **No editarlos a mano.**
- Direcciones a crear:
  - `contacto@mallasarica.cl` → Gmail del dueño *(pública, va en el sitio, el PDF y el footer)*
  - `ventas@mallasarica.cl` → Gmail del dueño *(pública, va en el sitio junto a `contacto@`)*
  - `cotizaciones@mallasarica.cl` → Gmail del dueño *(remitente de la app; recibe respuestas accidentales)*
- **Catch-all: desactivado.** Direcciones inventadas por spammers solo generan ruido en el buzón.

### 3.3 Registros de autenticación

Los valores exactos los entrega el panel de Resend al verificar el dominio → **no inventarlos ni copiarlos de este documento**. Lo que sí importa conocer de antemano:

- Resend publica su MX y su SPF de return-path bajo un **subdominio de envío** (`send.mallasarica.cl` por defecto), de modo que **no colisiona** con el SPF/MX que Cloudflare puso en la raíz. El `From` visible sigue siendo `@mallasarica.cl`.
- DKIM va como CNAME/TXT en `resend._domainkey.mallasarica.cl`.
- DMARC lo agrega el humano:
  ```
  _dmarc  TXT  "v=DMARC1; p=none; rua=mailto:dmarc@mallasarica.cl"
  ```
  Arrancar en `p=none` 1-2 semanas, revisar los reportes agregados, y recién ahí subir a `p=quarantine`. Crear `dmarc@mallasarica.cl` como ruta más en Email Routing.
- Si Cloudflare tiene el proxy naranja activo para la raíz, **los registros MX y TXT no se proxean** (solo A/AAAA/CNAME). No hay conflicto.

### 3.4 "Enviar correo como" en el Gmail del dueño

Gmail → Configuración → Cuentas → *Enviar correo como* → añadir `contacto@mallasarica.cl` (repetir para `ventas@mallasarica.cl` si el dueño quiere responder también con ese remitente):

- Servidor SMTP: el de Resend (`smtp.resend.com`, puerto 465 SSL).
- Usuario: `resend`. Contraseña: **una API key distinta a la de la app**, para poder revocarla sin tumbar el formulario de contacto.
- Desmarcar "Tratar como alias" si se quiere que las respuestas mantengan el hilo correctamente.

> **Trampa conocida:** usar el SMTP propio de Gmail solo funciona si la cuenta que envía es exactamente la misma que recibe el reenvío; con cualquier otra combinación Gmail reescribe el `From` y se pierde el remitente corporativo. Por eso se usa el SMTP de Resend.

---

## 4. Cambios en el repositorio

### 4.1 Dependencia

```bash
composer require resend/resend-laravel
```

`config/mail.php` de Laravel 12 ya trae el transport `resend` y el mailer `failover`; no hay que crearlos.

### 4.2 `config/mail.php` — ajustar el mailer `failover`

Editar **solo** el array `failover.mailers` (el archivo actual lista `['smtp', 'log']`):

```php
'failover' => [
    'transport' => 'failover',
    // Resend primero; si el relay cae, el correo queda en el log del contenedor
    // (stderr → Dozzle) en vez de lanzar excepción y quemar reintentos del job.
    'mailers' => [
        'resend',
        'log',
    ],
    'retry_after' => 60,
],
```

### 4.3 `config/mail.php` — destinatario administrativo

Añadir al final del array de retorno, antes del cierre:

```php
    /*
    |--------------------------------------------------------------------------
    | Destinatario interno de avisos
    |--------------------------------------------------------------------------
    | Dirección del dueño para notificaciones de cotizaciones/solicitudes nuevas.
    | Sale de config y no del código para que migrar de Cloudflare Routing a
    | un buzón real (Zoho) sea un cambio de .env y nada más.
    */
    'admin_address' => env('MAIL_ADMIN_ADDRESS', 'contacto@mallasarica.cl'),
```

### 4.4 `config/database.php` — conexión Redis dedicada para colas

El stack usa índices: `0` caché, `1` sesiones/default, `2` reservado para Pulse (ver plan de observabilidad). Las colas van al **3**:

```php
'queue' => [
    'url' => env('REDIS_URL'),
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'username' => env('REDIS_USERNAME'),
    'password' => env('REDIS_PASSWORD'),
    'port' => env('REDIS_PORT', '6379'),
    'database' => env('REDIS_QUEUE_DB', '3'),
],
```

Y en `config/queue.php`, en `connections.redis`, apuntar `'connection' => env('REDIS_QUEUE_CONNECTION', 'queue')`.

> **Verificar antes de escribir:** el `deploy/docker-compose.yml` actual levanta un Redis **dedicado** para la app (`REDIS_PASSWORD=null`), mientras que `plan-cicd.md` describe uno compartido con ACL `~appmallas:*`. Confirmar cuál está vivo en el VPS con `docker inspect`. Si es el compartido con ACL, el prefijo de las claves de cola debe caer dentro de `appmallas:*` → revisar `REDIS_PREFIX` (ver `CLAUDE.md` §7.1).

### 4.5 `deploy/.env.production.example` — nuevas variables

```diff
-QUEUE_CONNECTION=sync               # sin worker en el MVP
+QUEUE_CONNECTION=redis
+REDIS_QUEUE_DB=3
+
+# --- Correo transaccional (Resend) ---
+# failover: resend → log. El log deja rastro en stderr sin romper el job.
+MAIL_MAILER=failover
+RESEND_API_KEY=
+MAIL_FROM_ADDRESS=cotizaciones@mallasarica.cl
+MAIL_FROM_NAME="Mallas Arica"
+# Buzón del dueño vía Cloudflare Email Routing → Gmail personal.
+MAIL_ADMIN_ADDRESS=contacto@mallasarica.cl
```

Replicar los mismos valores (con `MAIL_MAILER=log`) en `.env.example` para desarrollo local.

### 4.6 `docker/prod/supervisord.conf` — activar el worker

Descomentar el bloque que ya está previsto y completarlo. **No** crear un contenedor aparte: el contenedor `app` ya corre supervisord con nginx, php-fpm y scheduler, y añadir un servicio implica otro pull y otros 256 MB reservados en una máquina de 4 GB.

```ini
[program:queue]
command=php /var/www/html/artisan queue:work redis --queue=default --sleep=3 --tries=3 --backoff=30 --max-time=3600 --max-jobs=500
user=www-data
autostart=true
autorestart=true
priority=40
stopwaitsecs=3600
numprocs=1
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

**Presupuesto de memoria** (límite del contenedor: 1 GB). El cálculo documentado en `php-fpm-pool.conf` era nginx ~15 + master ~30 + scheduler ~40 + 10 workers ×~65 ≈ 735 MB. El worker de colas suma ~70 MB → ~805 MB. Cabe, pero queda menos holgura: si aparece OOM, bajar `pm.max_children` de 10 a 9 antes que subir el límite del contenedor.

`--max-time=3600` y `--max-jobs=500` fuerzan el reciclaje del proceso, que es la defensa estándar contra fugas de memoria en workers de larga vida. `stopwaitsecs=3600` evita que supervisord mate un job a medio enviar durante un deploy.

> **Consecuencia en el deploy:** cada recreación del contenedor reinicia el worker. Los jobs en Redis sobreviven (`appendonly yes` en el Redis dedicado); los que estaban *en vuelo* vuelven a la cola tras el timeout de reserva. Los mailables deben ser idempotentes en la práctica → de ahí `notificado_at` (§4.7).

### 4.7 Migración — `notificado_at`

```
database/migrations/xxxx_add_notificado_at_to_cotizaciones_table.php
```

- `timestamp('notificado_at')->nullable()->after('requiere_visita')`
- Índice parcial no aplica en MariaDB; basta un índice normal sobre `notificado_at` si se va a consultar seguido por nulos. Con el volumen esperado, **omitir el índice** → es sobreingeniería.

Sirve para dos cosas: saber qué leads quedaron sin avisar (fallo de relay, worker caído durante un deploy) y alimentar el comando de reintento (§4.11).

### 4.8 Extraer el render del PDF a un service

Hoy `CotizadorWizard::crearCotizacionYDescargarPdf()` genera el PDF inline. El mailable al cliente necesita **los mismos bytes**. Extraer sin duplicar:

```
app/Services/CotizacionPdfService.php
```

- Método `render(Cotizacion $cotizacion): string` → devuelve el PDF como string binario.
- Método `nombreArchivo(Cotizacion $cotizacion): string` → `"cotizacion-{$cotizacion->numero}.pdf"`.
- `CotizadorWizard` pasa a usarlo para la descarga; el mailable lo usa para el adjunto.

Cargar las relaciones (`items.tipoEspacio`, `items.tipoMalla`, `items.tramoAltura`) dentro del service para no depender de que el llamador lo haya hecho → el job las va a necesitar tras rehidratar el modelo desde la BD.

> **Nota (post-ocultamiento del cotizador en línea):** mientras el cotizador en línea esté oculto (ver `CLAUDE.md` — sección de landing, formulario `SolicitudContacto`), la mayoría de los leads no tendrán ítems ni rango de precio, solo datos de contacto. `CotizacionPdfService` debe tolerar una cotización sin ítems (por ejemplo, mostrando "Pendiente de visita técnica" en vez de una tabla vacía) o, más simple, `CopiaCotizacionCliente` (§4.9) puede omitir el adjunto PDF por completo si `cotizacion->items->isEmpty()`. Decidir esto al implementar, no antes — no bloquea el resto del plan.

### 4.9 Mailables

**`app/Mail/NuevaCotizacionAdmin.php`**

- `From`: `config('mail.from')` → `cotizaciones@mallasarica.cl`.
- `To`: `config('mail.admin_address')`.
- `Reply-To`: el correo del cliente **si existe**; si no, omitirlo.
- Asunto: `"Cotización N° {numero} — {nombre} ({telefono})"`. El teléfono en el asunto permite al dueño llamar sin abrir el correo.
- Cuerpo: nombre, teléfono (como enlace `tel:` y `wa.me`), dirección, rango total (si existe cálculo) o aviso de "solicitud de visita sin cálculo de precio" (si viene del formulario de contacto), tabla de ítems si los hay, badge si `requiere_visita`, y enlace directo al registro en el panel Filament.
- **Sin adjunto.** El dueño no necesita el PDF; necesita el teléfono.

> **Crítico — DMARC:** el `From` es siempre del dominio propio. Poner el correo del cliente en `From` para que "se vea de quién viene" rompe la alineación DMARC y manda el aviso a spam. Esa información va en `Reply-To` y en el cuerpo.

**`app/Mail/CopiaCotizacionCliente.php`**

- Solo si `cotizacion.email` no es null.
- `From`: `cotizaciones@mallasarica.cl`. `Reply-To`: `contacto@mallasarica.cl`.
- Asunto: `"Tu solicitud N° {numero} — Mallas Arica"`.
- Adjunto: `CotizacionPdfService::render()` **solo si la cotización tiene ítems calculados** (ver nota de §4.8); si no, se omite el adjunto y el cuerpo lo indica.
- Cuerpo breve: agradecimiento, número, próximos pasos (coordinación de visita técnica), botón de WhatsApp.
- Ambos mailables implementan `ShouldQueue`.

**Plantillas Blade:** `resources/views/emails/`. Usar los tokens de marca ya definidos (`--brand-red-ui`, `--ink`, `--cream-deep`) pero **con estilos inline y tablas**, no clases Tailwind → los clientes de correo no ejecutan el CSS del sitio. No reutilizar los componentes Blade del sitio.

### 4.10 Job

**`app/Jobs/EnviarNotificacionesCotizacion.php`**

- `implements ShouldQueue`, propiedad `public Cotizacion $cotizacion` (serialización por modelo: se rehidrata desde la BD, así que si el registro fue borrado el job muere solo).
- `public int $tries = 3;` `public array $backoff = [30, 120];`
- Guarda temprana: `if ($this->cotizacion->notificado_at !== null) return;` → idempotencia frente a reintentos y a jobs re-encolados por un deploy.
- Envía el aviso al dueño; si `email` está presente, envía también la copia al cliente.
- Al terminar: `$this->cotizacion->forceFill(['notificado_at' => now()])->save();`
- `failed(Throwable $e)`: `Log::error` con el `id` de la cotización. **No** dejar `notificado_at` marcado en el fallo.

### 4.11 Enganche en `CotizadorWizard` y `SolicitudContacto`

El enganche va en **ambos** puntos de persistencia — `CotizadorWizard::persistirCotizacion()` (cotizador en línea, hoy oculto pero no eliminado) y `SolicitudContacto::enviar()` (formulario de contacto en terreno, activo actualmente) — ya que los dos crean un `Cotizacion` de forma independiente:

```php
// El correo es un efecto secundario: si el dispatch falla (Redis caído),
// la cotización ya está persistida y el flujo de conversión sigue intacto.
try {
    EnviarNotificacionesCotizacion::dispatch($cotizacion)->afterResponse();
} catch (\Throwable $e) {
    Log::error('No se pudo encolar la notificación de cotización', [
        'cotizacion_id' => $cotizacion->id,
        'error' => $e->getMessage(),
    ]);
}
```

`afterResponse()` saca el encolado del ciclo de vida de la petición Livewire.

### 4.12 Comando de reintento

**`app/Console/Commands/ReintentarNotificaciones.php`** — firma `app:reintentar-notificaciones`

- Busca cotizaciones con `notificado_at` null y `created_at` de las últimas 72 horas.
- Re-despacha el job para cada una.
- Registrarlo en `routes/console.php` con `->hourly()` → el scheduler ya corre vía supervisord.

### 4.13 Rate limiting

Al agregar envío de correo, el spam deja de ser solo ruido en la BD y pasa a consumir cuota del relay (100/día en el plan gratis de Resend). El honeypot `sitioWeb` existente (en ambos componentes, ver `CLAUDE.md` y `app/Livewire/SolicitudContacto.php`) no basta por sí solo, pero el `RateLimiter` por IP **ya está implementado** en ambos puntos de persistencia:

- `CotizadorWizard::persistirCotizacion()` → clave `cotizador:{ip}`, 5 intentos / 10 minutos.
- `SolicitudContacto::enviar()` → clave `solicitud-contacto:{ip}`, 5 intentos / 10 minutos.

No se requiere trabajo adicional aquí: basta con que el job de correo respete la cuota diaria del relay (ver deuda técnica, §10) y con que el throttle exista antes de que exista el envío de correo, cosa que ya ocurre.

---

## 5. Tests

`tests/Feature/Notificaciones/CotizacionNotificacionTest.php`:

| Test | Aserción |
|---|---|
| `test_se_encola_el_job_al_persistir_la_cotizacion` | `Queue::fake()` → `Queue::assertPushed(EnviarNotificacionesCotizacion::class)` |
| `test_la_cotizacion_persiste_aunque_falle_el_encolado` | Mock que lanza en `dispatch` → `assertDatabaseCount('cotizaciones', 1)` y sin excepción propagada |
| `test_el_aviso_al_dueno_lleva_reply_to_del_cliente` | `Mail::fake()` → `assertSent(NuevaCotizacionAdmin::class, fn ($m) => $m->hasReplyTo('cliente@correo.cl'))` |
| `test_no_se_envia_copia_si_el_cliente_no_dejo_email` | `Mail::assertNotSent(CopiaCotizacionCliente::class)` |
| `test_el_job_es_idempotente` | Ejecutar el job dos veces con `notificado_at` ya seteado → `Mail::assertNothingSent()` en la segunda |
| `test_el_job_marca_notificado_at` | Tras `handle()`, `notificado_at` no es null |
| `test_no_adjunta_pdf_si_la_cotizacion_no_tiene_items` | Cotización creada desde `SolicitudContacto` (sin ítems) → `CopiaCotizacionCliente` se envía sin adjunto |

Extender `tests/Feature/Livewire/CotizadorWizardTest.php` y `tests/Feature/Livewire/SolicitudContactoTest.php` con `Mail::fake()` en el `setUp` para que los tests existentes no intenten enviar.

---

## 6. Verificación post-deploy

```bash
# 1. Drivers activos
docker compose exec -T app php artisan about --only=drivers
#    Esperado: Mail = failover, Queue = redis

# 2. El worker está vivo
docker compose exec -T app supervisorctl status queue
#    Esperado: RUNNING

# 3. Envío real de prueba
docker compose exec -T app php artisan tinker --execute="\
  Mail::raw('prueba', fn(\$m) => \$m->to(config('mail.admin_address'))->subject('SMOKE TEST'));"

# 4. La cola se drena
docker compose exec -T app php artisan queue:monitor default --max=10

# 5. Jobs fallidos
docker compose exec -T app php artisan queue:failed
```

**Validación de entregabilidad** (una sola vez, tras configurar DNS): enviar un correo de prueba a `check-auth@verifier.port25.com` o usar `mail-tester.com` desde la app. Objetivo: SPF `pass`, DKIM `pass`, DMARC `pass`, puntaje ≥ 9/10.

**Prueba manual del circuito completo:**
1. Solicitud desde el sitio con un correo real → llega aviso al Gmail del dueño con `From: cotizaciones@mallasarica.cl`.
2. El dueño pulsa *Responder* → el destinatario es el cliente (`Reply-To` funcionando).
3. Correo enviado a `contacto@mallasarica.cl` o `ventas@mallasarica.cl` desde una cuenta externa → aparece en el Gmail del dueño.
4. El dueño responde ese correo eligiendo el remitente corporativo → el externo ve `contacto@mallasarica.cl` (o `ventas@mallasarica.cl`), no el Gmail.

El paso 4 es el que suele fallar y es el que justifica toda la configuración. Probarlo antes de publicar las direcciones en el sitio.

---

## 7. Edge cases resueltos por este diseño

| # | Problema | Resolución |
|---|---|---|
| 1 | Resend caído al momento de cotizar/contactar | Job en cola con 3 reintentos y backoff; fallback `log` en el mailer failover |
| 2 | Redis caído al momento de cotizar/contactar | `try/catch` en el dispatch; el registro persiste igual y `app:reintentar-notificaciones` lo recupera |
| 3 | Deploy en medio de un envío | `stopwaitsecs=3600` + jobs idempotentes vía `notificado_at` |
| 4 | Reintento duplica el correo al cliente | Guarda temprana por `notificado_at` al inicio de `handle()` |
| 5 | Aviso al dueño cae en spam | `From` del dominio propio + DKIM; el correo del cliente va en `Reply-To`, nunca en `From` |
| 6 | Cliente sin correo (campo opcional) | Solo se envía el aviso interno; la copia se omite sin error |
| 7 | Spam agota la cuota de 100/día | Honeypot existente + `RateLimiter` de 5/10min por IP (ya implementado en ambos formularios) |
| 8 | Worker con fuga de memoria mata el contenedor | `--max-time=3600 --max-jobs=500` reciclan el proceso |
| 9 | Credenciales del buzón personal en `.env` | No ocurre: el envío usa una API key de relay, revocable de forma independiente |
| 10 | Duplicar la lógica de generación del PDF | `CotizacionPdfService` compartido entre descarga y adjunto |
| 11 | Migrar a un buzón real más adelante | Solo cambian los MX y `MAIL_ADMIN_ADDRESS`; el envío no se toca (§9) |
| 12 | Lead sin ítems (formulario de contacto, cotizador oculto) | `CopiaCotizacionCliente` omite el adjunto PDF si no hay ítems calculados (§4.8) |

---

## 8. Checklist de implementación

**Repositorio (instancia local)**
- [ ] `composer require resend/resend-laravel`
- [ ] `config/mail.php`: `failover.mailers = ['resend', 'log']` + clave `admin_address`
- [ ] `config/database.php`: conexión Redis `queue` (índice 3) → *verificar antes si el Redis es dedicado o compartido con ACL*
- [ ] `config/queue.php`: `connections.redis.connection = 'queue'`
- [ ] Migración `notificado_at`
- [ ] `app/Services/CotizacionPdfService.php` + refactor de `crearCotizacionYDescargarPdf()` + tolerancia a cotizaciones sin ítems
- [ ] `app/Mail/NuevaCotizacionAdmin.php` + vista Blade con estilos inline
- [ ] `app/Mail/CopiaCotizacionCliente.php` + vista Blade con estilos inline
- [ ] `app/Jobs/EnviarNotificacionesCotizacion.php`
- [ ] Enganche + `try/catch` en `CotizadorWizard::persistirCotizacion()` **y** `SolicitudContacto::enviar()`
- [ ] `app/Console/Commands/ReintentarNotificaciones.php` (firma `app:reintentar-notificaciones`) + registro `->hourly()`
- [ ] `docker/prod/supervisord.conf`: bloque `[program:queue]` activo
- [ ] `deploy/.env.production.example` y `.env.example` actualizados
- [ ] Tests de §5 en verde
- [ ] `./vendor/bin/pint --test` en verde
- [ ] `php artisan route:cache && php artisan route:clear` sin errores

**Cloudflare / DNS (humano)**
- [ ] NS de `mallasarica.cl` apuntando a Cloudflare (coordinar con la migración de dominio)
- [ ] Email Routing activo, destino verificado
- [ ] Rutas `contacto@`, `ventas@`, `cotizaciones@`, `dmarc@` creadas; catch-all **desactivado**
- [ ] Dominio verificado en Resend (DKIM + registros de `send.`)
- [ ] `_dmarc` con `p=none` y `rua`
- [ ] API key de la app creada (solo envío)
- [ ] API key separada para el "Enviar como" del Gmail del dueño
- [ ] "Enviar correo como" configurado y probado en el Gmail del dueño (`contacto@` y, si aplica, `ventas@`)

**VPS**
- [ ] `.env` con `RESEND_API_KEY`, `MAIL_*`, `QUEUE_CONNECTION=redis`, `REDIS_QUEUE_DB=3`
- [ ] `docker compose up -d --wait` + `migrate --force`
- [ ] `supervisorctl status queue` → RUNNING
- [ ] Smoke test de §6 completo, incluido el paso 4
- [ ] Monitor en Uptime Kuma: *push* desde el comando horario, o alerta si `queue:failed` crece
- [ ] Confirmar que `contacto@mallasarica.cl` y `ventas@mallasarica.cl` ya están publicados en el sitio (footer, sección de contacto, PDF) antes de anunciar el correo al público

---

## 9. Ruta de salida (cuando el negocio lo justifique)

Disparadores para migrar a un buzón real:

- El dueño quiere que otra persona (hijo, secretaria, instalador) tenga dirección propia.
- Necesita histórico del correo corporativo independiente de su Gmail personal.
- Quiere responder desde un cliente de escritorio o desde una app dedicada.
- El volumen supera los 100 correos/día del plan gratis de Resend.

**Migración a Zoho Mail** (plan gratis: 5 usuarios, 5 GB, dominio propio, pero sin IMAP/POP; Mail Lite: USD 1/usuario/mes anual, con IMAP/POP/SMTP):

1. Desactivar Email Routing en Cloudflare.
2. Reemplazar los MX por los de Zoho. **Solo eso.**
3. Cambiar `MAIL_ADMIN_ADDRESS` si la dirección varía.
4. El envío transaccional sigue por Resend, sin tocar una línea de código.

Ese desacoplamiento es la razón principal por la que el envío de la app **nunca** debe apoyarse en el SMTP del buzón, aunque técnicamente se pueda.

---

## 10. Deuda técnica que este plan introduce

| Tema | Estado | Cuándo abordarlo |
|---|---|---|
| Webhooks de rebote de Resend | No implementados | Cuando haya volumen suficiente para que importe una dirección inválida |
| Cuota de 100 correos/día | Sin alerta | Añadir monitor si el formulario supera ~40 leads/día |
| Vistas de correo sin test de render | Solo se testea el envío | Aceptable; un snapshot test es sobreingeniería aquí |
| `notificado_at` sin índice | Consulta full-scan en el comando horario | Solo si `cotizaciones` supera ~50k filas |
| DMARC en `p=none` | Modo observación | Subir a `p=quarantine` tras 2 semanas de reportes limpios |
| Sin cola dedicada por prioridad | Todo en `default` | Al entrar la Etapa 2 (subida de fotos), separar `default` y `media` |
