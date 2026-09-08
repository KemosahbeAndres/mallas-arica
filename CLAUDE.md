# Mallas Arica v2 — Plan de Ejecución
**Sistema Integral de Gestión Mallas Arica** (nombre operativo actual: **mallas-arica**, identificador de infraestructura `appmallas`) · Reemplaza a `plan_para_claude.md` (v1)

---

## 1. Deltas respecto al plan v1

| # | v1 (original) | v2 (rediseño + stack final) | Impacto |
|---|---|---|---|
| 1 | Vue.js SPA + API interna | **Laravel 12 + Livewire 3 (SSR)**. La API REST existe **solo** para la app móvil (Etapa 2) | Alto — elimina capa HTTP interna |
| 2 | MySQL o PostgreSQL | **MariaDB** | Bajo |
| 3 | Cotiza por **m²** | Cotiza por **metro lineal + tramo de altura** (FAQ del rediseño lo declara explícito) | **Alto — cambia el core de negocio** |
| 4 | Precio único estimado | **Rango min–max** (ej. `$68.000 – $81.000`) | Medio — refuerza el disclaimer legal |
| 5 | 2 tipos de espacio (Balcón, Ventana) | **6 tipos**: Ventana, Balcón, Terraza, Escalera, Mascotas, Piscina | Medio |
| 6 | Sin variantes de material | **2 tipos de malla**: Estándar / Reforzada mascotas (1 mm, rombo 1,5 cm) | Medio |
| 7 | Abono de reserva online (Transbank) al agendar | **Eliminado del MVP.** Conversión = handoff a WhatsApp + agendar visita gratuita. Transbank queda como *badge* de confianza (pago en terreno) | **Alto — quita integración de pagos del MVP** |
| 8 | Galería estática | Galería administrable desde Etapa 3, imágenes en filesystem local (ver §1 nota) | Bajo |

> **Cambio de infraestructura (Sprint 4, implementado):** Cloudflare R2 fue **descartado por completo** — nunca se contrataron credenciales y el volumen de imágenes (galería pública + fotos de instaladores) es lo bastante bajo para no justificar un servicio externo. Tanto `galeria_items` como `trabajo_fotos` usan el filesystem local del contenedor `mallas-arica-app` (disco `public` de Laravel, `storage/app/public`), servido vía el symlink `public/storage`. Esto reemplaza cualquier mención a R2 en este documento — tratarlas como decisión descartada, no vigente.

> **Decisión #7 es la que más acelera el MVP**: sacar la pasarela de pago elimina integración, conciliación, reembolsos y estados de pago del Sprint 1.

---

## 2. Identidad visual (extraída del isologo + mockup)

```css
/* tailwind.config.js → theme.extend.colors */
--brand-red:      #E53329;  /* rojo del isologo — logotipo, acentos puros */
--brand-red-ui:   #CA1E1E;  /* rojo del mockup — botones y texto (mejor contraste AA) */
--brand-red-dark: #A81818;  /* hover / active */
--ink:            #211D1C;  /* negro carbón: hero, footer, panel de precio */
--ink-soft:       #3A3533;  /* texto secundario sobre crema */
--cream:          #FAF7F0;  /* fondo base del sitio */
--cream-deep:     #F3EAE1;  /* fondo de secciones alternas */
--line:           #EAE1D8;  /* bordes de cards */
```

**Reglas de marca**
- El isologo (dos cuadrados redondeados superpuestos, rojo + negro) es la marca de agua conceptual: **usar el motivo de superposición** en separadores y en el badge del hero. No inventar iconografía nueva.
- El sitio es **crema, no blanco**. El blanco puro (`#FFF`) se reserva para las cards elevadas.
- Hero y footer en `--ink` con glow rojo radial. Todo lo demás en crema.
- Rojo = **solo CTA y micro-etiquetas de sección** (`QUÉ PROTEGEMOS`, `GALERÍA`). Nunca como fondo de bloques grandes.
- Tipografía: sans geométrica (Inter / Plus Jakarta). Titulares peso 700, tracking negativo `-0.02em`.

---

## 3. Arquitectura (congelada)

```
Cliente ──► Traefik (TLS: certresolver "myle")
              └─► mallas-arica-app  (Nginx + PHP-FPM vía supervisord, límite 1 GB)
                    ├─► MariaDB compartida (DB lógica propia, límite ~1.28 GB)
                    ├─► Redis compartido (índices 0–1)
                    └─► Filesystem local (galería pública + fotos de instaladores)

App móvil (Etapa 2) ──► /api/v1/* (Sanctum) ──► mismos Services
```

**Regla no negociable:** toda la lógica de precio vive en `CotizacionCalculatorService`. Livewire lo llama como PHP plano; los controllers de API lo llaman igual. **Cero duplicación de fórmula.**

**Almacenamiento de imágenes (implementado, Sprint 4):** tanto las fotografías y evidencias de los trabajos (`trabajo_fotos`) como la galería pública (`galeria_items`) se almacenan en el **mismo contenedor de `mallas-arica-app`** (filesystem local, disco `public` de Laravel — `storage/app/public`, servido vía `public/storage`). No se usa ningún servicio externo de almacenamiento: el endpoint `POST /api/v1/trabajos/{id}/fotos` sube el archivo directo al VPS, sin presigned URL.

**Entorno de desarrollo:** se desarrolla en **Fedora**. Los comandos que deban ejecutarse en el host (fuera de un contenedor/sandbox, p. ej. para interactuar con el sistema gráfico o servicios del host) requieren `flatpak-spawn --host` como prefijo.

**Dominios:**
- Producción: `mallasarica.cl` — **DNS ya propagado**, apunta al VPS. Es el dominio oficial y público a usar en el deploy del Sprint 6.
- Desarrollo: `mallas.tinorte.cl` — queda como dominio de desarrollo/staging únicamente.

**Fase estática antes que CRM:** el MVP (Etapa 1) se construye con contenido **casi estático**, en páginas Livewire + Blade convencionales (sin editor de contenido, sin bloques dinámicos). El **CRM real se activa después** (ver §11) — no bloquear ni sobre-diseñar el Sprint 1–4 pensando en el editor de bloques; ese dinamismo se incorpora en una etapa posterior sin rehacer las páginas base si se respeta la separación de secciones del §4.1.

---

## 4. Etapa 1 — Landing + captación de contacto (MVP)

> **Sprint 12 — el cotizador con cálculo automático se ELIMINÓ.** Ver §11 septies y la
> memoria `cotizador-oculto-formulario-contacto`. El negocio cotiza **siempre a mano**
> tras la visita técnica. Lo que sigue en §4.2–4.7 describe el flujo antiguo y se
> conserva solo como registro histórico — **no aplica**. El flujo vigente:
>
> - El formulario de la landing (`<livewire:solicitud-contacto />`) **crea un `Cliente`**
>   (match por teléfono) + su dirección, y dispara el aviso al dueño (`NotificarNuevoCliente`
>   → `NuevoClienteAdmin`). No calcula precio, no crea cotización.
> - Las cotizaciones se arman en el panel (`/admin/cotizaciones`) con ítems de línea libres.
>   Estados `borrador → generada → aceptada → rechazada`. Al aceptar → se crea la **OT**
>   (`trabajos`).
> - Borrado en cascada del motor: `CotizadorWizard`/`PanelPrecio`/`ItemEspacio`,
>   `CotizacionCalculatorService`, `TarifaCacheService`, CRUD admin de Tarifas, DTOs de
>   cálculo, y las tablas `tarifas` / `tramos_altura` / `tipos_espacio` / `tipos_malla`
>   (más `visitas`, que nunca tuvo UI). `config/cache.php` ya no tiene `tarifas_store`.

### 4.1 Secciones de la landing (orden del mockup)
1. **Navbar** — Servicios · Cotizador · Galería · FAQ · [Escríbenos] (rojo)
2. **Hero** — badge `🛡 Mallas de seguridad certificadas · Arica`, H1 con "tranquilidad" en rojo, 2 CTA, 3 checks de confianza
3. **Barra de atributos** — Transparente · 200 kg/m² · Filtro UV · Rápida
4. **Qué protegemos** — grid 3×2 de los 6 tipos (contenido estático hardcodeado en `protection-grid.blade.php` desde el Sprint 12; antes leía `tipos_espacio`)
5. **Tipos de malla** (Sprint 7) — 3 espesores con precios/specs de referencia + 2 sistemas de instalación
6. **Cómo trabajamos** — 4 pasos numerados
7. **Formulario de contacto** (`<livewire:solicitud-contacto />`, ancla `#cotizador`) — nombre/teléfono/dirección obligatorios + email opcional → crea un `Cliente`
8. **Galería** — grid mosaico + "Ver más en WhatsApp →"
9. **Nosotros** — texto + dirección Av. Diego Portales #1333 + teléfono + medios de pago
10. **FAQ** — acordeón, 7 preguntas (editable en BD, Sprint 8)
11. **CTA final + Footer** — `--ink`, íconos de redes sociales (Sprint 7)

> **Corrección de contenido (post-Sprint 3):** la **visita técnica** (medir y cotizar) y la **instalación** son dos citas distintas, agendadas por separado — nunca "el mismo día" el uno del otro.
>
> **No comprometer una duración de instalación en horas ni en "una mañana"** — cada trabajo es distinto y fijar un plazo es un riesgo comercial. El mensaje correcto es **"instalación rápida"** sin cuantificar, combinado con **confianza y puntualidad**: llegamos a la hora acordada, siempre respondemos. Evitar "mismo día" y evitar rangos de horas (ej. "3 a 5 horas") en cualquier copy nuevo.
>
> **Medios de pago (agregado post-Sprint 3):** se acepta efectivo, transferencia bancaria y tarjetas de débito/crédito hasta 3 cuotas, pagados en terreno al finalizar la instalación. Mencionado en Hero (check de confianza), Nosotros y FAQ. Sigue sin existir integración de pago online (Transbank queda fuera del MVP, ver §1 decisión #7).
>
> **Sección "Tipos de malla" (implementado, Sprint 7, pedido directo del dueño):** `resources/views/components/landing/mesh-types.blade.php`, ubicada entre "Qué protegemos" y "Cómo trabajamos". Muestra **3 espesores** (0,80 mm rombo 5×5 cm, $25.000/m² de referencia; 0,90 mm rombo 3×3 cm para gatitos pequeños, soporta 250 kg/m²; 1,9 mm rombo 4×4 cm para gatitos mordedores, soporta 300 kg/m²) — **contenido estático hardcodeado en el Blade, no consulta el modelo `TipoMalla`** (que solo tiene 2 tipos por uso: Estándar/Reforzada mascotas, usados por el cálculo de tarifas ya oculto al público). Los precios mostrados son **de referencia/marketing únicamente**, no alimentan `CotizacionCalculatorService` ni ningún cálculo real — el negocio cotiza manualmente. Incluye además 2 sistemas de instalación: ángulos de aluminio 20×20×1,2 mm con amarres de alambre galvanizado (estándar), y **Sistema Netzen** con arpones de poliamida, "a pedido", explícitamente descrito como **certificado internacionalmente** (badge rojo).
>
> **Imagen del hero (Sprint 7, cerrado con placeholder):** el hero apunta a `public/images/hero-placeholder.svg` — placeholder de marca (silueta del Morro de Arica + patrón de malla + isotipo, paleta oficial), mismo criterio que los SVG de arranque de la galería (§4.8). Cuando el dueño entregue la foto real con el Morro de Arica de fondo: subir el `.jpg` a `public/images/` y cambiar el nombre del archivo en `resources/views/components/landing/hero.blade.php` (una línea). No es un bloqueante de cierre.
>
> **Redes sociales (implementado, Sprint 7):** footer con íconos de Facebook (`facebook.com/mallas.arica`) e Instagram (`instagram.com/mallas_arica_jacob`), SVG inline (sin librería de íconos externa).

### 4.2–4.6 (histórico — flujo del cotizador automático, ELIMINADO en el Sprint 12)

El modelo de cálculo por metro lineal × tramo de altura × multiplicador de malla, los
8 edge cases, `CotizadorWizard`/`PanelPrecio`, el snapshot de tarifas y los dos botones
de conversión (WhatsApp / PDF) **ya no existen**. Se conservan en el historial de git.
Si el negocio alguna vez quiere volver a un cotizador instantáneo, es un rediseño nuevo,
no una reactivación. Ver §11 septies para el flujo vigente.

### 4.7 Diseño del PDF de cotización (Sprint 12 — ítems libres)

Fuente de verdad de estilo: `./diseño/cotizacion-v2.pdf`. Generación:
`App\Services\CotizacionPdfDataBuilder` + `resources/views/pdf/cotizacion.blade.php`
vía `barryvdh/laravel-dompdf`. Descarga en `GET /admin/cotizaciones/{cotizacion}/pdf`
(`CotizacionPdfController`).

- **Header:** isologo + wordmark; badge rojo `COTIZACIÓN` con el **folio** de 4 dígitos y la fecha (en español vía `App\Support\FechaEsp`).
- **Bloques EMPRESA / CLIENTE** lado a lado, fondo `--cream-deep`:
  - EMPRESA: razón social, RUT, dirección, teléfono y correo — **hardcodeado** en `CotizacionPdfDataBuilder::EMPRESA` (mismo criterio que el JSON-LD, §4.9).
  - CLIENTE: nombre / dirección / contacto tomados del `Cliente` y la `ClienteDireccion` de la cotización, con fallback a los campos-copia de `cotizaciones` (`nombre`/`telefono`/`email`/`direccion`).
- **Tabla de ítems** — columnas `Descripción` | `P. unitario` | `Cant.` | `Desc.` | `Subtotal`. Las líneas son **libres** (`cotizacion_items.descripcion`, `precio_unitario`, `cantidad`, `descuento_pct`, `subtotal`). `subtotal` de línea = `precio_unitario × cantidad × (1 − descuento_pct/100)`, redondeado, calculado por `CotizacionItem::calcularSubtotal()`.
- **Totales:** (si hay descuento global, una fila lo señala) `Neto` → `IVA (19%)` → `Total` en barra roja. `Cotizacion::neto` = suma de subtotales de línea × `(1 − descuento_pct/100)`; `iva` y `total` son accessors (`Cotizacion::IVA_TASA = 0.19`).
- **Nota de vigencia:** editable en BD (`site_contents` key `cotizaciones.mensaje_vigencia`, Sprint 8), con `CotizacionPdfDataBuilder::MENSAJE_VIGENCIA_DEFAULT` de fallback.
- **Folio:** `Cotizacion::folio` — accessor sobre `id` a 4 dígitos. **Antes se llamaba `numero`** (renombrado en el Sprint 12). El `codigo` alfanumérico `MA-XXXX` sigue eliminado.

### 4.8 Galería (implementado, Sprint 4)

- **`GaleriaItem`** (tabla `galeria_items`, ver §5) — `foto_path` es una ruta relativa dentro del disco `public` de Laravel (`storage/app/public/galeria/…`), servida vía el symlink `public/storage` (`php artisan storage:link`). El accessor `GaleriaItem::url` resuelve la URL pública con `Storage::disk('public')->url(...)`.
- **`app/Livewire/GaleriaMosaico.php`** — Livewire simple (sin estado propio más allá del listado), consulta `GaleriaItem::where('publicado', true)->orderBy('orden')->get()`. Vista en `resources/views/livewire/galeria-mosaico.blade.php`: grid mosaico (primer ítem ocupa 2×2) + lightbox construido en **Alpine puro** (navegación con flechas de teclado y clic fuera para cerrar), sin roundtrip a servidor — mismo patrón que el acordeón de FAQ (§4.4). Termina con el CTA `Ver más en WhatsApp →` del mockup original.
- **Contenido de arranque:** `database/seeders/GaleriaItemSeeder.php` puebla 6 ítems (uno por tipo de espacio) usando SVG generados con la paleta de marca (`storage/app/public/galeria/*.svg`) como placeholder — **no son fotos reales de trabajos**, deben reemplazarse cuando existan. El componente maneja explícitamente el estado sin filas (`galeria_items` vacía o todas `publicado=false`): muestra *"Muy pronto vamos a publicar fotos de nuestros trabajos aquí"* en vez de un grid roto.
- **R2 descartado** (ver §1, §3): no hay integración con ningún servicio externo de almacenamiento para la galería.

### 4.9 SEO y schema.org (implementado, Sprint 4)

- **`LocalBusiness`** — JSON-LD en `resources/views/components/layouts/app.blade.php` (aplica a toda la landing, un solo layout). Incluye nombre, teléfono, dirección (Av. Diego Portales #1333, Arica) y `priceRange`. Datos hardcodeados en la plantilla, igual criterio que el bloque EMPRESA del PDF (§4.7).
- **`FAQPage`** — JSON-LD en `resources/views/components/landing/faq.blade.php`, generado a partir de la misma colección que renderiza el acordeón — una sola fuente de verdad. Desde el Sprint 8 esa colección viene de la tabla `faqs` vía `SiteContentService::faqs()` (antes era un array hardcodeado en el Blade); el `<script>` solo se emite si hay preguntas publicadas.
- **Open Graph + canonical** — meta tags `og:*` y `<link rel="canonical">` en el layout, usando `url()->current()`.
- **`/sitemap.xml`** (`routes/web.php`) — respuesta XML (`Content-Type: application/xml`) con cache pública de 1h. Sitio de una sola página: el sitemap solo lista la home; las secciones son anclas (`#servicios`, `#galeria`, etc.), que no son URLs indexables por separado.
- **`/robots.txt`** — ruta dinámica (reemplaza el archivo estático que había en `public/`), referencia el sitemap con `Sitemap:`.

### 4.10 Mapa de 301 (implementado sin acceso al Wix real)

No se tuvo acceso al listado real de URLs de producción del sitio Wix anterior (§7 "Migración desde Wix" lo daba por pendiente). `routes/web.php` define redirects 301 para las rutas típicas de un sitio Wix de landing — `/page4` (explícitamente reportada como rota en la navegación original), `/servicios`, `/cotizar`, `/cotizacion`, `/galeria`, `/nosotros`, `/contacto`, `/preguntas-frecuentes`, `/faq`, `/home`, `/index` — todas apuntando a la sección equivalente de la página única (`/#seccion`) o a `/`. **Pendiente:** revisar los logs de acceso de Wix (o Google Search Console) tras el corte de DNS para detectar rutas 404 no cubiertas por este mapa y agregarlas.

### 4.11 Panel admin (implementado, Sprint 5)

- **Auth propia, sin Breeze/Filament:** `App\Livewire\Admin\Auth\Login` (componente de clase, `Auth::attempt()` nativo) sobre el guard `web` estándar. Se descartó Breeze porque su instalador (`php artisan breeze:install livewire`) pisa el pipeline Tailwind 4 CSS-first del proyecto (reemplaza `resources/css/app.css`/`vite.config.js` por Tailwind 3 clásico + `tailwind.config.js`), y usa Livewire Volt en vez de componentes de clase, inconsistente con el resto del proyecto. Mismo patrón de throttle que `CotizadorWizard::persistirCotizacion()` (`RateLimiter` por IP, clave `admin-login:{ip}`, 5 intentos/10 min), con `RateLimiter::clear()` en éxito. **Sin registro público en ningún momento** — `bootstrap/app.php` declara `redirectGuestsTo(fn () => route('admin.login'))`.
- **Alta del admin:** único punto de verdad en `App\Support\AdminUserManager::crear()`, usado por `database/seeders/AdminUserSeeder` (lee `ADMIN_EMAIL`/`ADMIN_PASSWORD`/`ADMIN_NAME` del `.env`) y por `php artisan app:crear-admin` (interactivo por defecto, o `--from-env`/`--email`/`--password` para uso no interactivo en deploy).
- **Alta del admin:** `App\Support\AdminUserManager::crear()`, usado por `AdminUserSeeder` (lee `ADMIN_*` del `.env`) y por `php artisan app:crear-admin`.
- **Rutas y chrome actuales (post Sprints 8–12):** navbar horizontal (`x-admin.navbar`, `diseño/dashboard-v1.pdf`) con **Resumen · Cotizaciones · Clientes · Calendario · Sitio web**. `/admin` redirige a `/admin/resumen`. Ya **no existen** `/admin/tarifas` ni `/admin/leads` (Sprint 12). `/admin/galeria` sigue viva pero se accede desde el sub-tab "Imágenes" de Sitio web. `x-admin.sidebar` fue eliminado en el Sprint 8.
- **CRUD Galería** (`App\Livewire\Admin\Galeria\GaleriaIndex` + `GaleriaForm`): alta/edición con `WithFileUploads` (`$foto->store('galeria', 'public')`), toggle publicado, reordenar con botones ↑/↓ (`DB::transaction()`), **hard delete real** (fila + archivo) porque `galeria_items` no tiene SoftDeletes. Ya no tiene el campo `tipo_espacio_id` (Sprint 12).
- Las demás secciones del panel están documentadas en §11 quater (Clientes), §11 quinquies (Calendario), §11 sexies (Resumen) y §11 septies (Cotizaciones).

---

## 5. Esquema de base de datos

> **Sprint 12 eliminó** `tipos_espacio`, `tipos_malla`, `tramos_altura`, `tarifas` y `visitas`
> (junto con el motor de cálculo). Sus modelos, seeders y el CRUD de Tarifas ya no existen.

```php
// Transaccional — cotizaciones, cotizacion_items usan SoftDeletes (ver nota abajo)
cotizaciones:    id, cliente_id(null → clientes), cliente_direccion_id(null → cliente_direcciones),
                 nombre(null), telefono(null), email(null), direccion(null),
                 estado(enum: borrador|generada|aceptada|rechazada),
                 descuento_pct(decimal 5,2), total_min(int), total_max(int),
                 notificado_at(null), deleted_at, timestamps
                 // nombre/telefono/email/direccion son una COPIA de conveniencia
                 // (fallback del PDF cuando no hay cliente). El contacto real vive
                 // en `clientes`. Folio = accessor Cotizacion::folio sobre `id`
                 // (antes `numero`). total_min/total_max se conservan pero con
                 // ítems libres no hay rango: ambos = neto.

cotizacion_items: id, cotizacion_id, descripcion, precio_unitario(int),
                  cantidad(decimal 8,2), descuento_pct(decimal 5,2), subtotal(int),
                  deleted_at, timestamps
                  // Líneas LIBRES (Sprint 12). subtotal = precio_unitario × cantidad
                  // × (1 − descuento_pct/100), redondeado. Cotizacion::neto aplica
                  // además el descuento_pct global de la cotización.

// «OT» (Orden de Trabajo) — §11 ter. Se crea al aceptar una cotización
// (CotizacionEstadoService). Versión mínima: sin medidas/firma/fotos/consumos
// (eso es Etapa 2, app móvil de instaladores).
trabajos:        id, cliente_id(null), cotizacion_id(null), evento_id(null → eventos),
                 titulo, descripcion(null),
                 estado(pendiente|en_curso|ejecutada|cancelada),
                 meses_mantencion(smallint, default 12), finalizado_at(null),
                 timestamps, deleted_at
// trabajo_fotos / consumos: Etapa 2, no creados todavía.

// Implementado Sprint 4 — foto_path es la ruta relativa en el disco `public`
// (storage/app/public), no una key de R2 (ver §1 y §3: R2 descartado).
galeria_items:   id, foto_path, titulo, orden, publicado(bool)
                 // tipo_espacio_id eliminado en el Sprint 12.

// Implementado Sprint 8 — contenido editable de la landing en BD (§11 bis).
// Sin SoftDeletes (no son datos de cliente). site_contents.key con notación
// de punto; faqs alimenta el acordeón y el JSON-LD FAQPage.
site_contents:   id, key(unique), value(text nullable), grupo, label,
                 tipo(text|textarea), orden, timestamps
faqs:            id, pregunta, respuesta, orden, publicada(bool), timestamps

// Implementado Sprint 9 — entidad Clientes (§11 ter). clientes con SoftDeletes
// (dato de negocio); cliente_direcciones sin SoftDeletes (cascada de BD al
// forceDelete). notificado_at (Sprint 12): fecha del aviso al dueño para los
// clientes que entran por el formulario del sitio.
clientes:            id, nombre, telefono(null), email(null), notas(null),
                     notificado_at(null), timestamps, deleted_at
cliente_direcciones: id, cliente_id, direccion, etiqueta(null), timestamps

// Implementado Sprint 10 — agenda interna (§11 quinquies). Unidad de agenda
// genérica, compatible con calendarios estándar. La OT (tabla `trabajos`,
// sprint de Cotizaciones) apuntará a su evento vía trabajos.evento_id — el
// evento no sabe de la OT. google_event_id se declara ya para el sync futuro,
// sin uso todavía.
eventos:         id, titulo, descripcion(null), tipo(terreno|oficina),
                 estado(agendado|hecho|cancelado), inicio(datetime),
                 fin(datetime null), todo_el_dia(bool), cliente_id(null),
                 ubicacion(null), notas(null), google_event_id(null, unique),
                 timestamps, deleted_at
```

**Índices:** `cotizaciones(estado, created_at)`, `eventos(inicio, estado)`, `trabajos(estado, finalizado_at)`.

> **Política de borrado:** `cotizaciones`, `cotizacion_items`, `clientes`, `eventos`, `trabajos` usan `SoftDeletes`. **Ningún registro de cliente/negocio se elimina físicamente.** `Cotizacion::booted()` replica la cascada a mano (`static::deleting`) porque `cascadeOnDelete()` de la FK no dispara con soft deletes — al borrar una cotización, sus `items` quedan soft-deleted. `cliente_direcciones` **no** lleva SoftDeletes (la cascada de BD las limpia solo en `forceDelete()` del cliente). Reservar `forceDelete()` para mantenimiento, nunca en la UI.

---

## 6. API REST (solo para Etapa 2)

| Verbo | Ruta | Uso |
|---|---|---|
| `POST` | `/api/v1/auth/token` | Login instalador (Sanctum) |
| `GET` | `/api/v1/trabajos/asignados` | Agenda del día del equipo |
| `POST` | `/api/v1/trabajos/{id}/medidas` | Medidas reales de la instalación (sin recálculo automático de precio — el motor se eliminó en el Sprint 12; el ajuste de precio, si lo hay, lo hace el dueño a mano en la cotización) |
| `POST` | `/api/v1/trabajos/{id}/firma` | Firma digital del cliente |
| `POST` | `/api/v1/trabajos/{id}/fotos` | Sube el archivo directo al VPS (filesystem local, ver §1 y §3) |
| `POST` | `/api/v1/trabajos/{id}/finalizar` | Bloqueado hasta tener las 3 fotos obligatorias |

**Nota (post-Sprint 12):** no hay recálculo automático de precio en terreno. La cotización se hizo a mano; si tras medir cambia el alcance, el dueño edita la cotización en el panel.

---

## 7. Infraestructura y despliegue (ya operativo)

- VPS OpenCloud 4 GB / 2 vCPU, **Ubuntu 22.04.5 LTS**. **Los builds Docker ocurren solo en GitHub Actions → GHCR.** Nunca en el VPS.
- **1 contenedor por entorno**: Laravel (Nginx + PHP-FPM vía supervisord). MariaDB y Redis son **instancias compartidas del VPS**, gestionadas por el repositorio `vpsa-infra` (`/opt/infra/data`), alcanzables por la red externa `backend-shared` con los aliases `mariadb` y `redis`. Sin puertos publicados al host.
- Galería pública y fotografías de trabajos (`trabajo_fotos`) se guardan en disco **dentro del contenedor `mallas-arica-app`** (ver §1, §3) — sin servicio externo de almacenamiento. Requiere volumen persistente y considerar en `backup-db.sh`/estrategia de backup un backup de archivos aparte.
- Backups gestionados por infraestructura: `/opt/infra/scripts/backup-app.sh appmallas` produce **un `.sql.gz` por base** (`appmallas\_%`) y **un `.jsonl.gz` por índice de Redis** (export lógico del prefijo `appmallas:`), en `/var/backups/vps-apps/appmallas/`. Cron diario a las 03:15 con retención de 14 días y monitor Push en Uptime Kuma. Restauración: `/opt/infra/scripts/restore-app.sh` (hace dump de seguridad previo).

**Migración desde Wix**
1. Wix vivo hasta 1 semana de Mallas Arica estable en producción.
2. Mapa de 301 (incluye `/page4` y los ítems de nav rotos) — **implementado sin acceso al sitio real, ver §4.10**. Revisar logs de Wix tras el corte de DNS para completar rutas que falten.
3. `LocalBusiness` + `FAQPage` schema.org, `sitemap.xml`, OG por página — **implementado, ver §4.9**.

### 7.1 Pipeline CI/CD (implementado — pendiente solo configuración externa)

Documento de referencia completo: `./plan-cicd.md` (nota: ese documento asumía MariaDB/Redis como instancias **compartidas preexistentes** en el VPS — decisión descartada, ver abajo). **Decisiones fijadas:**
- **PHP 8.4** en el runtime de producción y en el job de test de Actions. `composer.json` declara `^8.2`, pero el `composer.lock` real quedó resuelto (generado con el PHP 8.5 del entorno de desarrollo, sin `platform.php` fijado en `composer.json`) con `symfony/clock`, `symfony/string`, `symfony/event-dispatcher` y otros en versión 8.1.x, que exigen PHP `>= 8.4.1`. Bajar el runtime a 8.2 rompe `composer dump-autoload` en el build (`platform_check.php` aborta). Si en algún momento se quiere soportar 8.2 real, hay que fijar `"config": {"platform": {"php": "8.2.0"}}` en `composer.json` y correr `composer update` para forzar el lock a versiones de Symfony 7.x — cambio de dependencias real, no solo de infraestructura, y no se hizo aquí.
- **MariaDB y Redis son instancias compartidas multi-tenant del VPS**, definidas en `vpsa-infra` (`/opt/infra/data/docker-compose.yml`). Mallas Arica solo aporta el contenedor `mallas-arica-app`. Decisión tomada para alojar varias aplicaciones en 4 GB de RAM sin duplicar motores de datos. **Esto aplica solo a producción** — en desarrollo local se sigue usando el `docker-compose.yml` de la raíz del repo con contenedores propios (`mallas-arica-mariadb-dev`, `mallas-arica-redis-dev`).
- **Aislamiento por inquilino:** base `appmallas_prod` con `GRANT` sobre `` `appmallas\_%`.* `` (el `\_` escapado es imprescindible: sin él `_` es comodín LIKE) y `MAX_USER_CONNECTIONS 20`. En Redis, usuario ACL `appmallas` restringido al patrón `~appmallas:*`, con `FLUSHALL` denegado y `FLUSHDB` permitido — este último lo necesita `Illuminate\Cache\RedisStore::flush()`, que ignora el prefijo.
- **`REDIS_PREFIX=appmallas:` es obligatorio en el `.env`.** Sin él, Laravel usa `<app_name>_database_`, ninguna clave coincide con el ACL y la aplicación falla entera con `NOPERM`.
- **Red `backend`:** externa, `backend-shared`, creada por el stack de infraestructura. El despliegue aborta con mensaje explícito si no existe (guard en `deploy.yml`).
- **`DB_ROOT_PASSWORD` eliminada** del `.env` de Mallas Arica: la contraseña de root vive ahora solo en `/opt/infra/.env` y la app nunca la usó.

**Repositorio de infraestructura:** `vpsa-infra` (privado), desplegado en `/opt/infra`. Contiene el stack de borde (Traefik, Portainer, Uptime Kuma, Dozzle) y el de datos compartidos (MariaDB, Redis), más los scripts de provisión y backup por aplicación. Mallas Arica está registrada en `apps/appmallas.conf`. Cualquier cambio de credenciales, red o motor de datos se hace ahí, no aquí.

**Archivos creados en el repositorio:**
```
docker/prod/Dockerfile             # build multi-stage: composer → node/Vite → php:8.4-fpm-alpine
docker/prod/nginx.conf
docker/prod/php.ini
docker/prod/php-fpm-pool.conf
docker/prod/supervisord.conf       # php-fpm + nginx + schedule:work
docker/prod/entrypoint.sh          # valida APP_KEY/APP_DEBUG, espera MariaDB/Redis, cachea en runtime
.dockerignore
.github/workflows/deploy.yml       # test → build+push GHCR → deploy SSH
deploy/docker-compose.yml          # referencia para copiar a /opt/mallas-arica/ en el VPS
                                    # servicio único: app (datos en el stack compartido)
deploy/.env.production.example     # plantilla del .env de producción
```

`bootstrap/app.php` ya tiene `trustProxies(at: '*', headers: ...)` — sin esto Livewire generaría URLs `http://` detrás de Traefik (mixed content).

**Validado localmente antes del primer push:**
- `php artisan route:cache` sin errores (no hay closures en las rutas).
- `./vendor/bin/pint --test` en verde (se corrigieron 7 issues de estilo preexistentes de sprints anteriores).
- `php artisan test` verde (26 tests) tras el fix de Pint.
- Build de la imagen Docker completo sin errores (multi-stage: vendor → assets → runtime), validado con Podman localmente.
- El Dockerfile instala Composer vía el instalador oficial (`getcomposer.org/installer`) directamente en el stage runtime, en vez de copiar el binario nativo del stage `vendor` (imagen `composer:2`) — ese binario está compilado contra una versión de PHP más nueva que el runtime en algunos casos y falla en tiempo de ejecución con "Invalid" / platform check.

**Pendiente — configuración externa al repo, no requiere más código:** generar `VPS_SSH_KEY` y cargar los 4 secrets en GitHub, completar el prerrequisito bloqueante en `vpsa-infra` (stack `data/` con `shared-mariadb`/`shared-redis` arriba, `provision-app.sh appmallas prod` ejecutado), crear `/opt/mallas-arica/` en el VPS con el `.env` real (credenciales de `provision-app.sh`, sin `DB_ROOT_PASSWORD`), emitir el certificado TLS (Traefik + certresolver `myle`, automático vía ACME al primer request — no requiere paso manual si el DNS ya apunta al VPS). La red `backend-shared`, la base `appmallas_prod` y el usuario ACL de Redis los crea `vpsa-infra`, no `docker compose up` de Mallas Arica.

---

## 8. Orden de ejecución

| Sprint | Entregable | Criterio de cierre |
|---|---|---|
| 1 | Migraciones + seeders de catálogos + `CotizacionCalculatorService` + **tests unitarios de los 8 edge cases** | `php artisan test` verde, sin UI |
| 2 | Layout, tokens Tailwind, secciones estáticas 1–5 y 8–10 | Lighthouse ≥ 95 mobile |
| 3 | ✅ `CotizadorWizard` + `PanelPrecio` + persistencia de lead + handoff WhatsApp + descarga PDF + honeypot/throttle | Lead guardado aunque no se envíe el WhatsApp — **cerrado** |
| 4 | ✅ Galería (filesystem local) + FAQ + SEO/schema + 301 | Sitemap indexable — **cerrado** |
| 5 | ✅ Panel admin: tarifas, leads, galería (Etapa 3 parcial) — ver §4.11 | El papá cambia un precio sin tocar código — **cerrado**, verificado con `php artisan test` (70 tests) |
| 5c | ✅ Correo transaccional (Resend + Cloudflare Email Routing) — ver `plan-correo.md`. **Post-Sprint 12** el aviso es "nuevo cliente desde el sitio" (`NotificarNuevoCliente` → `NuevoClienteAdmin`), ya no "nueva cotización"; la infraestructura de correo (Resend, mailer, config) es la misma | Código listo y testeado; **pendiente la configuración externa** (Cloudflare Email Routing, dominio en Resend, secrets en el VPS) — **cerrado del lado del repositorio** |
| 6 | ✅ Deploy prod | Sitio en producción en `mallasarica.cl`, DNS propagado — **cerrado** |
| 7 | ✅ Ajustes de contenido/negocio en la landing pedidos por el dueño (sección "Tipos de Malla" con 3 espesores + precios de referencia, sistemas de instalación Netzen/aluminio, redes sociales en footer, imagen del hero) — ver §4.1 | Sección Tipos de Malla + redes sociales + placeholder de marca del hero **implementados**, `pint --test` y `php artisan test` (70) verdes — **cerrado**. La foto real del Morro de Arica (insumo del dueño) es un swap de archivo posterior, no bloquea el cierre |
| 8 | ✅ CRM «Sitio web»: contenido editable de la landing (Hero, Nosotros, mensaje de vigencia del PDF), FAQ editable, galería re-enlazada como sub-tab, **todo en base de datos** (tabla `site_contents` clave-valor + tabla `faqs`), sin editor de bloques. Nuevo chrome del admin = navbar horizontal del `diseño/dashboard-v1.pdf` (Resumen · Cotizaciones · Clientes · Calendario · Sitio web) con placeholders «Próximamente» para lo aún no construido — ver §11 bis | El dueño edita el título del Hero y una pregunta de FAQ desde el panel y se refleja en el sitio sin deploy — **cerrado**, `pint --test` y `php artisan test` (87) verdes |
| 9 | ✅ Entidad **Clientes** (alta manual): `clientes` + `cliente_direcciones`, CRUD maestro-detalle en `/admin/clientes` — ver §11 quater. Convención de dominio OT/instalaciones/sin-TK anotada en §11 ter | El dueño da de alta un cliente con sus direcciones desde el panel — **cerrado**, `php artisan test` (95) y `pint --test` verdes |
| 10 | ✅ **Calendario / agenda interna**: tabla `eventos` (genérica, preparada para Google Calendar), grilla mensual a mano + agendas semanal/mensual + CRUD de eventos en `/admin/calendario` — ver §11 quinquies. Google Calendar queda para su propio sprint | El dueño agenda un evento y lo ve en la grilla del mes — **cerrado**, `php artisan test` (110) y `pint --test` verdes |
| 11 | ✅ **Dashboard Resumen** (`/admin/resumen`, home del admin): 4 KPIs + "trabajos de esta semana" (de `eventos`) + "últimas cotizaciones" — ver §11 sexies | **cerrado**, `php artisan test` (120) y `pint --test` verdes |
| 12 | ✅ **Rediseño de Cotizaciones** — ver §11 septies. Se **eliminó** el motor de cálculo automático completo (cotizador público, `CotizacionCalculatorService`, tarifas, catálogos `tipos_*`, `tramos_altura`, `visitas`). El formulario del sitio crea un `Cliente`. Cotizaciones internas con ítems libres, estados `borrador→generada→aceptada→rechazada`, folio, PDF adaptado. Al aceptar → se crea la **OT** (`trabajos`) | El dueño arma una cotización a mano, la acepta y se crea la orden de trabajo — **cerrado**, `php artisan test` (96) y `pint --test` verdes |
| 13–16 | CRM restante: sincronización con Google Calendar; historial de OT + cotizaciones + alerta de mantención en la ficha de Cliente; agenda de OT en el Calendario (`trabajos.evento_id`). Diseño: `diseño/dashboard-v1.pdf` | Ver desglose cuando se aborde cada uno |
| Final | Editor de páginas por bloques avanzado (ex-Sprint 5b, ver §11), al estilo WordPress + Otter Blocks — **última pieza del roadmap, después de todo el CRM** | Página editada por bloques desde el panel se refleja en el sitio sin deploy |

> **Nota sobre el modelo de negocio (post-Sprint 12):** el dueño cotiza **siempre a mano** en el panel. El motor de cálculo automático (`CotizacionCalculatorService`, `tarifas`, catálogos) se **eliminó**. Los precios de referencia por m² de la sección "Tipos de Malla" son **contenido informativo** hardcodeado, no alimentan nada.
>
> **Sprint 5c antes que el Sprint 6:** el dueño necesita enterarse de leads reales por correo durante la semana en paralelo con Wix, no solo después del corte de DNS. Documento de referencia completo: `./plan-correo.md`.

---

## 9. Decisiones pendientes (bloquean Sprint 1)

1. **Tarifas reales** por (tipo_espacio × tramo_altura): valores min y max en CLP.
2. **Multiplicador exacto** de malla para mascotas.
3. **¿IVA incluido** en el rango mostrado? (recomendado: sí, es venta a consumidor final).
4. **Metraje mínimo facturable** — asumido 2 ml.
5. ¿Piscina cotiza o solo genera lead? — asumido *solo lead*.
6. ¿Se reintroduce el abono Transbank en alguna etapa posterior, o el negocio se queda con pago en terreno?

---

## 10. Assets de diseño

Todo en la carpeta `./diseño`:

- `rediseño.pdf` — **guía de diseño oficial**, fuente de verdad para layout, tipografía y mockups (ver también §2).
- `isologo.svg` / `isologo-b.svg` — **isologos oficiales** (versión color / versión b).
- **Lockup horizontal:** a la derecha del isologo va el wordmark en dos líneas, ambas en mayúsculas: `MALLAS` (rojo, `--brand-red` o `--brand-red-ui`) sobre `ARICA` (negro, `--ink`).

---

## 11. Evolución a CRM (post-MVP)

**Fase actual (Etapa 1):** contenido casi estático — páginas Livewire + Blade tradicionales, sin capa de edición. Prioridad es shippear el cotizador (§4) sin cargar complejidad de CMS.

**Fase futura:** el panel admin (Sprint 5, §8) evoluciona a un **CRM completo**, que incluye:
- Gestión de leads/cotizaciones, tarifas y galería (ya cubierto en Sprint 5).
- **Contenido editable de la landing en base de datos** (Sprint 8): tabla `site_contents` (clave-valor, agrupada, cacheada en Redis) + tabla `faqs`. Los Blade y la plantilla del PDF leen de ahí; el contenido hoy hardcodeado se migra vía seeder y pasa a ser el contenido inicial editable. **No es un editor de bloques** — son formularios de campos fijos.
- **Editor de páginas por bloques avanzado**, al estilo **WordPress + Otter Blocks** (o equivalente): permite componer/editar secciones de la landing (hero, qué protegemos, FAQ, etc.) desde el panel sin tocar código ni requerir deploy. **Decisión del dueño: es la ÚLTIMA pieza del roadmap**, después de todo el CRM (dashboard, Clientes, Calendario, Cotizaciones). No confundir con el contenido editable del Sprint 8, que es campos fijos en BD, no bloques.

**Implicancias de diseño a tener en cuenta desde ya:**
- Las secciones estáticas del §4.1 deben construirse como **componentes Blade/Livewire desacoplados y con props claras**, para que cada una pueda convertirse más adelante en un "bloque" editable sin reescritura completa.
- El editor de bloques es la última funcionalidad del roadmap (post-CRM); no bloquea ni forma parte del criterio de cierre de ningún sprint anterior.

### 11 bis. Sprint 8 — CRM «Sitio web» (implementado)

**Contenido editable en base de datos, sin editor de bloques.** Dos tablas nuevas:

- **`site_contents`** (`key` unique, `value` nullable, `grupo`, `label`, `tipo` = `text|textarea`, `orden`) — campos de texto fijos de la landing y del PDF. `key` con notación de punto (`hero.titulo_1`, `nosotros.texto_2`, `cotizaciones.mensaje_vigencia`); el `grupo` (primer segmento) agrupa la UI del panel. `database/seeders/SiteContentSeeder` la puebla con los textos que antes vivían hardcodeados en los Blade — al sembrarse **pasan a ser el contenido inicial editable, no un fallback**. El seeder solo escribe `value` al crear la fila; si ya existía, respeta lo editado por el dueño (la metadata `label`/`tipo`/`orden`/`grupo` sí la refresca siempre).
- **`faqs`** (`pregunta`, `respuesta`, `orden`, `publicada`) — reemplaza el array hardcodeado de `faq.blade.php`. `database/seeders/FaqSeeder` migra las 7 preguntas. El acordeón y el JSON-LD `FAQPage` (§4.9) leen ambos de esta tabla vía `SiteContentService::faqs()` — una sola fuente de verdad. El `<script type="application/ld+json">` solo se emite si hay preguntas publicadas.

**`App\Services\SiteContentService`** — mismo patrón que `TarifaCacheService` (§4.4): colección completa cacheada bajo key versionada (`site_content:v{n}`, `faqs:v{n}`), invalidación por `Cache::increment` de la versión (no `forget`), store configurable vía `config('cache.site_content_store')` (`CACHE_SITE_CONTENT_STORE`, default `redis`). Observers `SiteContentObserver` / `FaqObserver` registrados en `AppServiceProvider::boot()` invalidan en `saved`/`deleted` — cubre tinker y comandos, no solo el admin.

**Helper global `site_content('hero.titulo_1', 'default')`** (`app/helpers.php`, cargado vía `composer.json` → `autoload.files`). Los Blade de la landing (`hero`, `about-us`) lo usan; `faq.blade.php` resuelve `SiteContentService` directo. El PDF: `CotizacionPdfDataBuilder` recibe `SiteContentService` por constructor y expone `mensajeVigencia` (con `MENSAJE_VIGENCIA_DEFAULT` como fallback); la plantilla `pdf/cotizacion.blade.php` ya no hardcodea la franja de vigencia. Los datos de `EMPRESA` del PDF (RUT, dirección, teléfono) **siguen hardcodeados** en el builder — no son parte del alcance del Sprint 8 (el `diseño/dashboard-v1.pdf` solo expone el mensaje de vigencia como editable).

**Chrome del admin rediseñado** (`components/layouts/admin.blade.php` + `components/admin/navbar.blade.php`): navbar horizontal negra del `diseño/dashboard-v1.pdf` con 5 entradas — **Resumen · Cotizaciones · Clientes · Calendario · Sitio web**. El `x-admin.sidebar` del Sprint 5 fue **eliminado**. Las 4 secciones aún no construidas (Sprints 9+) apuntan a `App\Livewire\Admin\Proximamente` (rutas con `->defaults('seccion', ...)`/`->defaults('detalle', ...)`). Las rutas del panel del Sprint 5 (`/admin/tarifas`, `/admin/leads`, `/admin/galeria`) **siguen vivas** — enlaces guardados y tests; `/admin` redirige ahora a `/admin/sitio-web` (antes a `/admin/tarifas`).

**«Sitio web»** (`App\Livewire\Admin\SitioWeb\SitioWebPanel`, ruta `admin.sitio-web`): 3 sub-tabs con estado en query string (`#[Url] $tab`):
- **Contenido** (`ContenidoForm`) — formulario agrupado sobre `site_contents`. `valores` se indexa por **id** de la fila, no por `key` (Livewire interpreta los puntos de `wire:model="valores.hero.titulo_1"` como path anidado). Valor vacío → `null`. Validación `nullable|string|max:2000` por campo.
- **Imágenes** — reusa `App\Livewire\Admin\Galeria\GaleriaIndex` tal cual, embebido como `<livewire:...>` (su `->layout()` se ignora al anidarse).
- **FAQ** (`FaqManager`) — CRUD inline: agregar/editar/eliminar filas, toggle `publicada`, el orden se deriva del índice del array al guardar (sin drag-and-drop). `updateOrCreate` por `id` en `DB::transaction`.

**Tests:** `SiteContentServiceTest`, `ContenidoFormTest`, `FaqManagerTest`, `CrmNavegacionTest`, casos de vigencia en `CotizacionPdfDataBuilderTest`, reseed de FAQ en `LandingPageTest`. `phpunit.xml` fuerza `CACHE_SITE_CONTENT_STORE=array`.

**No se tocó** el esquema de Calendario — eso va en su propio sprint (Sprint 10). Cotizaciones se rediseñó en el Sprint 12 (§11 septies).

### 11 ter. Convención de dominio del CRM (decidida por el dueño, Sprint 9)

Flujo de negocio, de principio a fin:

1. El cliente pide una cotización por **WhatsApp** o por la **landing** (que en la práctica lo lleva al chat de WhatsApp — el `CotizadorWizard` con precio automático sigue oculto, ver memoria `cotizador_oculto_formulario_contacto`).
2. El dueño **da de alta el Cliente** en el panel (Sprint 9).
3. El dueño **crea una Cotización** para **una dirección de ese cliente** (`/admin/cotizaciones/nueva`), con ítems de línea libres. Estados: `borrador → generada → aceptada → rechazada` (`Cotizacion::ESTADOS`, §11 septies).
4. Cuando el cliente **acepta** la cotización, recién ahí se crea una **OT (Orden de Trabajo)** = tabla `trabajos`.
   - Una OT es **una salida a terreno con un objetivo**: ir a cotizar, instalar, dar mantención, cambiar una pieza de instalación o retirar. Lleva `titulo` + `descripcion` que explican ese objetivo.
   - La OT tiene **doble relación**: `cliente_id` **y** `cotizacion_id` (ambas pueden ser null en las primeras etapas — p. ej. una OT de "ir a cotizar" aún no tiene cotización). Con esa doble FK **no hacen falta tickets (TK)** — se descartó esa entidad.
   - Una OT en estado **ejecutado** es lo que el mockup de la ficha de Cliente (`diseño/dashboard-v1.pdf` pág. 5) llama «instalación». **No hay tabla `instalaciones` separada**: es la misma OT en otro estado.
   - La alerta «Mantención vencida» se calcula sobre la OT ejecutada (`finalizado_at + meses_mantencion < hoy`). `meses_mantencion` se agrega a `trabajos` en el sprint de Cotizaciones.
5. Cuando una OT se **agenda** (día + hora), genera un **`evento`** relacionado (§11 quinquies). **La OT apunta al evento** (`trabajos.evento_id`), no al revés — así el calendario es compatible con formatos estándar y con el futuro Google Calendar. También existen eventos **que no son OT**: llamar a un cliente, enviar una cotización → `evento` tipo `oficina`, sin OT.
6. El historial de OT ejecutadas y las cotizaciones relacionadas se muestran en la ficha del Cliente — **se implementa en el sprint de Cotizaciones**, cuando exista la doble relación.

`trabajos` (mínima) y `cotizaciones.cliente_id` / `cliente_direccion_id` **ya existen** (Sprint 12). `trabajo_fotos`, `consumos` y el poblado de `trabajos.evento_id` (agendar la OT) siguen pendientes (Etapa 2 / Sprints 13+). La app móvil de instaladores puebla las OT en terreno; las OT viejas pre-sistema las carga el dueño a mano.

### 11 quater. Sprint 9 — entidad Clientes (implementado)

- **`clientes`** (SoftDeletes — dato de seguimiento comercial) + **`cliente_direcciones`** (sin SoftDeletes; `cascadeOnDelete` de BD las limpia en `forceDelete` del cliente, no en el soft delete). Ver §5.
- **`App\Livewire\Admin\Clientes\ClientesIndex`** (ruta `admin.clientes`, reemplaza el placeholder «Próximamente» del Sprint 8): una sola página maestro-detalle como el mockup — lista con buscador (`nombre`/`telefono`/`email`, `like`), y a la derecha el formulario del cliente seleccionado con sus direcciones agregables/quitables inline. `#[Url] $seleccionado` guarda el id en query string; un id inválido en la URL **no rompe la página** (se ignora en `mount`, no `findOrFail`). Guardado en `DB::transaction`: `updateOrCreate` por dirección + `whereNotIn` para borrar las quitadas. Eliminar cliente = soft delete.
- **Alta manual únicamente.** El vínculo lead→cliente (matchear por teléfono, botón «convertir en cliente») se hace en el sprint de Cotizaciones.
- **Fuera de alcance del Sprint 9:** historial de OT/instalaciones, alerta de mantención, «cotizaciones relacionadas» — todo depende de la OT (§11 ter). El Blade deja un comentario donde irán.
- **Tests:** `ClientesIndexTest` (7 casos) + `CrmNavegacionTest` actualizado. `php artisan test` **95 en verde**, `pint --test` verde.

### 11 quinquies. Sprint 10 — Calendario / agenda interna (implementado)

- **`eventos`** (SoftDeletes) — unidad de agenda genérica (ver §5 y §11 ter paso 5). `tipo` `terreno|oficina`, `estado` `agendado|hecho|cancelado`, `inicio` datetime (+ `todo_el_dia` bool que anula la hora), `cliente_id` nullable. `google_event_id` unique nullable declarado desde ya para el sync con Google (sprint posterior), sin uso. **`trabajos` no se creó** — cuando exista, la OT apuntará al evento vía `trabajos.evento_id`; hoy los eventos se crean sueltos.
- **`App\Livewire\Admin\Calendario\CalendarioIndex`** (ruta `admin.calendario`, reemplaza el placeholder del Sprint 8): grilla mensual construida a mano (Blade + `CarbonImmutable`, lunes–domingo, `#[Url] $mes` en `Y-m`, navegación mes ± / "Hoy"; `mes` inválido en la URL se ignora). Click en un día abre el form con esa fecha; click en un evento lo edita. Panel lateral con "Agenda semanal" (semana en curso, sin cancelados) y "Agenda del mes" como el mockup. CRUD completo de eventos (`updateOrCreate`, soft delete). Botón "Conectar con Google Calendar" **visible pero deshabilitado** con nota "próximamente" — **sin librería JS de calendario** (mismo criterio que el resto del proyecto).
- **`App\Support\FechaEsp`** — formato de fechas en español sin depender del locale (`APP_LOCALE=en`). Centraliza los nombres de meses/días; `CotizacionPdfDataBuilder` dejó de duplicar su `const MESES` privada y ahora usa `FechaEsp::largo()`.
- **Google Calendar NO se implementó** — es su propio sprint. El esquema queda preparado (`google_event_id`).
- **Tests:** `CalendarioIndexTest` (11 casos, con `Carbon::setTestNow`) + `FechaEspTest` (4) + `CrmNavegacionTest` actualizado. `php artisan test` **110 en verde**, `pint --test` verde.

### 11 sexies. Sprint 11 — Dashboard Resumen (implementado)

- **`App\Livewire\Admin\Resumen\ResumenIndex`** (ruta `admin.resumen`, reemplaza el placeholder del Sprint 8). **`/admin` ahora redirige aquí** (antes a `/admin/sitio-web`) — Resumen es la home del panel, como el mockup. Todo son `#[Computed]`, sin estado.
- **KPIs — criterios aproximados a lo que hay hoy en BD, se afinan con el rediseño de Cotizaciones y las OT:**
  - **Cotizaciones (mes)**: `count` de `cotizaciones` con `created_at >= startOfMonth`, más variación % contra el mes anterior (`variacionPorcentual`: si el mes anterior fue 0 → `+100%` cuando hay algo este mes, `null` si no).
  - **Pendientes**: `cotizaciones` en estado `borrador` (decisión del dueño — un lead que llegó y nadie procesó). Se remapea a los estados nuevos al rediseñar Cotizaciones.
  - **Clientes activos**: `Cliente::has('direcciones')` — "con dirección registrada". Pasará a "con OT ejecutada" cuando existan las OT.
  - **Ticket promedio**: `avg(total_max)` de cotizaciones con `total_max > 0` (techo del rango, no el precio real — el negocio cotiza a mano). Con variación % mensual.
  - Nota post-Sprint 12: "Ticket promedio" ahora es sobre `total_max`, que con ítems libres = `neto`; "Pendientes" sigue siendo estado `borrador`.
- **Listados:** "Trabajos de esta semana" (de `eventos`) y "Últimas cotizaciones" (6 más recientes; enlaza a `/admin/cotizaciones?seleccionada={id}`).
- **Tests:** `ResumenIndexTest` (9 casos, `Carbon::setTestNow`) + `CrmNavegacionTest`.

### 11 septies. Sprint 12 — Rediseño de Cotizaciones (implementado)

**Demolición del motor de cálculo automático.** Eliminados: `CotizadorWizard` / `PanelPrecio` / `ItemEspacio` (+ vistas), `CotizacionCalculatorService`, `TarifaCacheService` + `TarifaObserver`, DTOs `CotizacionCalculoResultado` / `ItemCalculoResultado`, CRUD admin de Tarifas (`TarifasMatriz`), CRUD de Leads (`LeadsIndex` / `LeadDetalle`), `Proximamente`, modelos `Tarifa` / `TramoAltura` / `TipoEspacio` / `TipoMalla` / `Visita`, sus seeders, `config('cache.tarifas_store')` + `CACHE_TARIFAS_STORE`. Migración `redesign_cotizaciones_for_manual_crm` dropea las tablas `tarifas`, `tramos_altura`, `tipos_espacio`, `tipos_malla`, `visitas`. `protection-grid.blade.php` y `GaleriaItem` / `GaleriaForm` / `GaleriaItemSeeder` perdieron su dependencia de `tipos_espacio` (contenido hardcodeado / columna dropeada).

**Formulario público → Cliente.** `SolicitudContacto::enviar()` crea/actualiza un `Cliente` (match por `telefono`, `firstOrNew`) + su dirección — ya no una `Cotizacion`. Honeypot + throttle sin cambios. Dispara `App\Jobs\NotificarNuevoCliente` → `App\Mail\NuevoClienteAdmin` (`emails/nuevo-cliente-admin.blade.php`). `clientes.notificado_at` (idempotencia). `App\Console\Commands\ReintentarNotificaciones` reencola clientes sin notificar. **Se eliminaron** `EnviarNotificacionesCotizacion`, `NuevaCotizacionAdmin`, `CopiaCotizacionCliente` y sus vistas — el plan-correo (Sprint 5c) ahora notifica clientes, no cotizaciones.

**Cotización interna.** `cotizaciones` gana `cliente_id` / `cliente_direccion_id` / `descuento_pct`; pierde `uuid` / `canal` / `requiere_visita` / `utm_source` / `ip_hash`; `nombre` / `telefono` pasan a nullable (copia de conveniencia). Enum estado → `borrador|generada|aceptada|rechazada` (remapeo de datos; en MySQL `ALTER … MODIFY`, SQLite lo guarda como texto). `Cotizacion::numero` → `folio`. `Cotizacion` gana accessors `neto` / `iva` / `total` (`IVA_TASA = 0.19`) y relaciones `cliente` / `clienteDireccion` / `trabajo`. `cotizacion_items` pasa a líneas libres (`descripcion`, `precio_unitario`, `cantidad`, `descuento_pct`, `subtotal`); `CotizacionItem::calcularSubtotal()` centraliza la fórmula de línea.

**Panel** (`/admin/cotizaciones`, reemplaza el placeholder):
- `CotizacionesIndex` — listado paginado con filtro por estado + panel de vista previa (folio, cliente, ítems, total, badges de estado, cambio de estado, "Descargar PDF", editar, eliminar/soft delete). `#[Url]` en `estadoFiltro` y `seleccionada`.
- `CotizacionForm` (`/admin/cotizaciones/nueva`, `/admin/cotizaciones/{cotizacion}/editar`) — cliente existente (buscador) o nuevo, dirección del trabajo (de las del cliente o texto libre → se guarda como dirección del cliente), ítems de línea libres con subtotales en vivo, descuento global, Neto/IVA/Total reactivos (`#[Computed]`). Guarda en `DB::transaction`, borra y recrea los items.
- `App\Services\CotizacionEstadoService::cambiar()` — cambio de estado; al pasar a `aceptada` crea la **OT** (`App\Models\Trabajo`) con doble FK `cliente_id` + `cotizacion_id`, estado `pendiente`, **idempotente** (no duplica si ya hay OT). `Trabajo` tiene accessor `mantencion_vencida` (`finalizado_at + meses_mantencion < hoy`, solo si `estado === 'ejecutada'`).

**PDF** — `CotizacionPdfDataBuilder` + `pdf/cotizacion.blade.php` adaptados a líneas libres (columnas Descripción/P.unitario/Cant./Desc./Subtotal), datos de cliente desde la relación, `folio`, `FechaEsp::largo()`. Ruta `GET /admin/cotizaciones/{cotizacion}/pdf` (`CotizacionPdfController`). `mensajeVigencia` sigue editable en `site_contents` (§8).

**Tests:** `CotizacionFormTest` (6), `CotizacionesIndexTest` (5, incluye idempotencia de la OT), `CotizacionPdfDataBuilderTest` reescrito, `CotizacionSoftDeleteTest` reescrito, `SolicitudContactoTest` reescrito (crea Cliente + notificación), `LandingPageTest` / `AdminAccessTest` / `CrmNavegacionTest` actualizados. `php artisan test` **96 en verde**, `pint --test` verde, `migrate:fresh --seed` OK.

**Pendiente (Sprints 13+):** historial de OT + cotizaciones + alerta de mantención en la ficha de Cliente; agenda de OT en el Calendario vía `trabajos.evento_id`; sincronización con Google Calendar.
