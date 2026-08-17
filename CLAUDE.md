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
- Producción: `mallasarica.cl`
- Desarrollo: `mallas.tinorte.cl` — el cambio de dominio a producción se hará más adelante, al desplegar.

**Fase estática antes que CRM:** el MVP (Etapa 1) se construye con contenido **casi estático**, en páginas Livewire + Blade convencionales (sin editor de contenido, sin bloques dinámicos). El **CRM real se activa después** (ver §11) — no bloquear ni sobre-diseñar el Sprint 1–4 pensando en el editor de bloques; ese dinamismo se incorpora en una etapa posterior sin rehacer las páginas base si se respeta la separación de secciones del §4.1.

---

## 4. Etapa 1 — Landing + Cotizador (MVP)

### 4.1 Secciones (orden del mockup)
1. **Navbar** — Servicios · Cotizador · Galería · FAQ · [Escríbenos] (rojo)
2. **Hero** — badge `🛡 Mallas de seguridad certificadas · Arica`, H1 con "tranquilidad" en rojo, 2 CTA (`Cotizar 30 segundos` / `Agendar visita`), 3 checks de confianza
3. **Barra de atributos** — Transparente · 200 kg/m² · Filtro UV · Rápida
4. **Qué protegemos** — grid 3×2 de los 6 tipos
5. **Cómo trabajamos** — 4 pasos numerados
6. **Cotizador** — split: formulario (izq, crema) + panel de precio (der, `--ink`, sticky)
7. **Galería** — grid mosaico + "Ver más en WhatsApp →"
8. **Nosotros** — texto + dirección Av. Diego Portales #1333 + teléfono + medios de pago
9. **FAQ** — acordeón, 7 preguntas (incluye medios de pago)
10. **CTA final + Footer** — `--ink`

> **Corrección de contenido (post-Sprint 3):** la **visita técnica** (medir y cotizar) y la **instalación** son dos citas distintas, agendadas por separado — nunca "el mismo día" el uno del otro.
>
> **No comprometer una duración de instalación en horas ni en "una mañana"** — cada trabajo es distinto y fijar un plazo es un riesgo comercial. El mensaje correcto es **"instalación rápida"** sin cuantificar, combinado con **confianza y puntualidad**: llegamos a la hora acordada, siempre respondemos. Evitar "mismo día" y evitar rangos de horas (ej. "3 a 5 horas") en cualquier copy nuevo.
>
> **Medios de pago (agregado post-Sprint 3):** se acepta efectivo, transferencia bancaria y tarjetas de débito/crédito hasta 3 cuotas, pagados en terreno al finalizar la instalación. Mencionado en Hero (check de confianza), Nosotros y FAQ. Sigue sin existir integración de pago online (Transbank queda fuera del MVP, ver §1 decisión #7).

### 4.2 Modelo de cálculo (el cambio crítico)

```
subtotal_item = max(metros_lineales, METRAJE_MINIMO)
                × precio_ml(tipo_espacio, tramo_altura, tipo_malla)

total_min = Σ subtotal_item(precio_ml_min)
total_max = Σ subtotal_item(precio_ml_max)
```

- El **tramo de altura** es un *lookup* discreto (`hasta 1,5 m` / `1,5–2 m` / `2–3 m` / `+3 m`), **no** un multiplicador continuo. Refleja cómo se corta la malla en rollos.
- `tipo_malla` aplica un **multiplicador** sobre el precio del tramo (Estándar `1.0`, Mascotas `~1.35`).
- Redondeo final **hacia arriba al millar** en CLP. Enteros, sin decimales, en toda la BD.

### 4.3 Edge cases obligatorios

| Caso | Comportamiento |
|---|---|
| `metros_lineales < METRAJE_MINIMO` (2 ml) | Se cobra el mínimo. Mostrar nota: *"Cobro mínimo de 2 m lineales"* |
| Altura `+3 m` | **No mostrar precio.** Reemplazar panel por *"Requiere evaluación en terreno"* + CTA WhatsApp |
| `metros_lineales > 40` | Mismo tratamiento: derivar a contacto, no calcular |
| Tipo = Piscina | Sin precio automático en v1 (geometría irregular). Solo lead |
| Sin tarifa vigente para la combinación | Fallback a lead, **nunca** a `$0` ni excepción visible |
| Input no numérico / negativo | Validación reactiva, panel congela el último valor válido |
| Cambio de precios en admin | **Snapshot** de `precio_ml_min/max` en cada `cotizacion_item` al momento de crear |
| IVA | Definir una vez y declararlo en el panel: `Valores incluyen IVA` |

### 4.4 Componentes Livewire

```
app/Livewire/
├── Cotizador/
│   ├── CotizadorWizard.php      # estado, ítems (array), wire:model.live.debounce.400ms
│   └── PanelPrecio.php          # hijo #[Reactive], rango, desglose, CTA WhatsApp (sticky)
└── GaleriaMosaico.php           # + lightbox Alpine — pendiente, Sprint 4
```

> **Decisión de implementación (Sprint 3):** la fila de ítem (`ItemEspacio`) se implementa como **Blade component puro** (`resources/views/components/cotizador/item-espacio.blade.php`), no como componente Livewire independiente. Un Livewire real por fila obligaría a sincronizar estado entre componentes hijos y el wizard padre solo para un array editable — complejidad innecesaria en Livewire 3. El Blade component recibe el item y su índice como props y usa `wire:model` apuntando a `items.{index}.campo` directo en el estado del `CotizadorWizard`.

> **Decisión de implementación (Sprint 3, cierre):** `LeadForm` tampoco se implementó como componente Livewire separado — honeypot y throttle viven dentro de `CotizadorWizard::persistirCotizacion()`, junto a la validación de `nombre`/`telefono`/`email` que ya usan ambos botones de conversión. El throttle es un `RateLimiter` de Laravel por IP (`cotizador:{ip}`, 5 intentos / 10 minutos); al superarlo, `persistirCotizacion()` devuelve `null` y agrega el error de validación `throttle`, mostrado en el formulario junto al honeypot.

- El acordeón de FAQ y el menú móvil son **Alpine puro**, sin roundtrip a servidor.
- Tarifas vigentes cacheadas en Redis (`appmallas:tarifas:v{n}`), invalidadas al guardar desde el admin → el cotizador **no toca la BD por tecla**.

### 4.5 Datos del cliente y persistencia del lead

> **Decisión (Sprint 3, ampliación post-cierre inicial):** el cotizador pide **condominio/dirección** (texto libre, opcional) en vez de `comuna`, y agrega **correo electrónico** (opcional). Objetivo: dejar registro completo del cliente y su cotización para hacer seguimiento comercial, no solo capturar el lead mínimo para WhatsApp.

- Campos del formulario de contacto: `nombre` (requerido), `telefono` (requerido), `direccion` (opcional, reemplaza a `comuna`), `email` (opcional).
- La cotización se persiste **siempre** al confirmar (estado `borrador`), independientemente de qué botón de conversión use el cliente después.
- **Cambio de esquema:** en `cotizaciones`, la columna `comuna` se reemplaza por `direccion` (string nullable). `email` ya existía en el esquema base (§5) como nullable — se activa su captura en el formulario.

### 4.6 Conversión: dos caminos, no uno

Tras calcular la cotización, el cliente tiene **dos botones de conversión** (ya no solo WhatsApp):

1. **`Crear por WhatsApp`**: persiste la cotización y abre `wa.me` con mensaje prellenado que incluye el **N° correlativo** de la cotización (`Cotizacion::numero`, ver §4.7). **El lead se guarda aunque el usuario nunca envíe el mensaje.**
2. **`Descargar PDF`** (implementado, Sprint 3): persiste la cotización (comparte `persistirCotizacion()` con el flujo de WhatsApp en `CotizadorWizard` — sin duplicar lógica) y genera un PDF vía `barryvdh/laravel-dompdf` (`resources/views/pdf/cotizacion.blade.php`) con diseño de marca (ver §4.7).
   - Ambos botones (`crearCotizacionYAbrirWhatsapp`, `crearCotizacionYDescargarPdf`) llaman a `persistirCotizacion()`, que valida `nombre`/`telefono`/`email` y aplica el honeypot antes de crear el registro.

### 4.7 Diseño del PDF de cotización (implementado)

Fuente de verdad: `./diseño/cotizacion-v2.pdf`. Layout tipo factura/comprobante:

- **Header:** isologo + wordmark "Mallas Arica" (subtítulo "Instalación de mallas de protección · Arica") a la izquierda; badge rojo `COTIZACIÓN` a la derecha, con **N°** correlativo de 4 dígitos y **Fecha** debajo.
- **Bloques EMPRESA / CLIENTE** lado a lado, fondo `--cream-deep`, etiqueta roja en mayúsculas (`EMPRESA`, `CLIENTE`):
  - EMPRESA: razón social fija ("Mallas Arica"), RUT fijo, dirección fija (Av. Diego Portales #1333, Arica), teléfono y correo fijos de la empresa — **hardcodeado en la plantilla**, no viene de BD.
  - CLIENTE: `nombre`, `direccion` (condominio/dirección del cliente), `telefono`, `email` de la cotización.
- **Tabla de ítems** con header `--ink` (fondo negro, texto blanco): columnas `Descripción` | `P. unitario` | `Cant.` | `Subtotal`.
  - **Decisión:** `P. unitario` = `precio_ml_max_snapshot × multiplicador_snapshot` del ítem (el techo del rango, no el mínimo) — el PDF es un documento formal que el cliente puede mostrar a terceros; comprometerse al mínimo arriesga tener que cobrar más tras la visita técnica sin respaldo escrito. `Cant.` = `metros_lineales`. `Subtotal` = `precio_unitario × cantidad` (recalculado en la vista, no usa `subtotal_max` directo, para que la aritmética de la línea cierre visualmente).
  - `Descripción` compone: nombre del tipo de espacio + tipo de malla (si no es la estándar) + tramo de altura, ej. *"Malla de protección estándar — instalación en Ventana, hasta 1,5 m"*.
  - Si `requiere_visita` es true para el ítem (altura +3m, >40ml, piscina), la línea muestra `Subtotal: A confirmar en visita técnica` en vez de un monto, y no entra en la suma de Neto.
- **Totales:** `Neto` (suma subtotales, sin IVA) → `IVA (19%)` → `Total` en barra roja `--brand-red-ui` con texto blanco. El sitio ya declara "valores incluyen IVA" en el cotizador web (rango min-max); el PDF **desglosa** el IVA explícitamente porque el precio unitario ahí es fijo, no rango.
- **Nota de vigencia:** franja con ícono de reloj, borde izquierdo rojo, fondo `--cream-deep`: *"Esta cotización tiene una vigencia de 10 días a contar de la fecha de emisión. Los valores están expresados en pesos chilenos (CLP) e incluyen IVA según se detalla."*
- **Footer:** teléfono + correo de la empresa, centrado, gris.
- **N° correlativo:** único identificador público de la cotización — se deriva del `id` autoincremental de `cotizaciones` formateado a 4 dígitos (accessor `Cotizacion::numero`, no es una secuencia separada). Se usa en el PDF, en el mensaje de WhatsApp y en la pantalla de confirmación del cotizador. **El antiguo código alfanumérico `MA-XXXX` fue eliminado** (columna `codigo` dropeada, ver migración `drop_codigo_from_cotizaciones_table`) — no reintroducirlo.

### 4.8 Galería (implementado, Sprint 4)

- **`GaleriaItem`** (tabla `galeria_items`, ver §5) — `foto_path` es una ruta relativa dentro del disco `public` de Laravel (`storage/app/public/galeria/…`), servida vía el symlink `public/storage` (`php artisan storage:link`). El accessor `GaleriaItem::url` resuelve la URL pública con `Storage::disk('public')->url(...)`.
- **`app/Livewire/GaleriaMosaico.php`** — Livewire simple (sin estado propio más allá del listado), consulta `GaleriaItem::where('publicado', true)->orderBy('orden')->get()`. Vista en `resources/views/livewire/galeria-mosaico.blade.php`: grid mosaico (primer ítem ocupa 2×2) + lightbox construido en **Alpine puro** (navegación con flechas de teclado y clic fuera para cerrar), sin roundtrip a servidor — mismo patrón que el acordeón de FAQ (§4.4). Termina con el CTA `Ver más en WhatsApp →` del mockup original.
- **Contenido de arranque:** `database/seeders/GaleriaItemSeeder.php` puebla 6 ítems (uno por tipo de espacio) usando SVG generados con la paleta de marca (`storage/app/public/galeria/*.svg`) como placeholder — **no son fotos reales de trabajos**, deben reemplazarse cuando existan. El componente maneja explícitamente el estado sin filas (`galeria_items` vacía o todas `publicado=false`): muestra *"Muy pronto vamos a publicar fotos de nuestros trabajos aquí"* en vez de un grid roto.
- **R2 descartado** (ver §1, §3): no hay integración con ningún servicio externo de almacenamiento para la galería.

### 4.9 SEO y schema.org (implementado, Sprint 4)

- **`LocalBusiness`** — JSON-LD en `resources/views/components/layouts/app.blade.php` (aplica a toda la landing, un solo layout). Incluye nombre, teléfono, dirección (Av. Diego Portales #1333, Arica) y `priceRange`. Datos hardcodeados en la plantilla, igual criterio que el bloque EMPRESA del PDF (§4.7).
- **`FAQPage`** — JSON-LD en `resources/views/components/landing/faq.blade.php`, generado a partir del mismo array `$preguntas` que renderiza el acordeón — una sola fuente de verdad, sin duplicar las 7 preguntas.
- **Open Graph + canonical** — meta tags `og:*` y `<link rel="canonical">` en el layout, usando `url()->current()`.
- **`/sitemap.xml`** (`routes/web.php`) — respuesta XML (`Content-Type: application/xml`) con cache pública de 1h. Sitio de una sola página: el sitemap solo lista la home; las secciones son anclas (`#servicios`, `#galeria`, etc.), que no son URLs indexables por separado.
- **`/robots.txt`** — ruta dinámica (reemplaza el archivo estático que había en `public/`), referencia el sitemap con `Sitemap:`.

### 4.10 Mapa de 301 (implementado sin acceso al Wix real)

No se tuvo acceso al listado real de URLs de producción del sitio Wix anterior (§7 "Migración desde Wix" lo daba por pendiente). `routes/web.php` define redirects 301 para las rutas típicas de un sitio Wix de landing — `/page4` (explícitamente reportada como rota en la navegación original), `/servicios`, `/cotizar`, `/cotizacion`, `/galeria`, `/nosotros`, `/contacto`, `/preguntas-frecuentes`, `/faq`, `/home`, `/index` — todas apuntando a la sección equivalente de la página única (`/#seccion`) o a `/`. **Pendiente:** revisar los logs de acceso de Wix (o Google Search Console) tras el corte de DNS para detectar rutas 404 no cubiertas por este mapa y agregarlas.

---

## 5. Esquema de base de datos

```php
// Catálogos (seeders, editables desde admin)
tipos_espacio:   id, slug, nombre, descripcion, icono, permite_calculo(bool), orden, activo
tipos_malla:     id, slug, nombre, grosor_mm, rombo_cm, multiplicador(decimal 4,2), activo
tramos_altura:   id, etiqueta, altura_min(decimal), altura_max(nullable), requiere_visita(bool)

tarifas:         id, tipo_espacio_id, tramo_altura_id, precio_ml_min(int), precio_ml_max(int),
                 vigente_desde(date), vigente_hasta(nullable)
                 UNIQUE(tipo_espacio_id, tramo_altura_id, vigente_desde)

// Transaccional — cotizaciones, cotizacion_items y visitas usan SoftDeletes (ver nota abajo)
cotizaciones:    id, uuid, nombre, telefono, email(null), direccion(null),
                 canal(enum: web|whatsapp|telefono), estado(enum: borrador|contactado|
                 agendado|cerrado|perdido), total_min(int), total_max(int),
                 requiere_visita(bool), utm_source, ip_hash, deleted_at, timestamps
                 // direccion reemplaza a comuna (v2.1, implementado): texto libre
                 // "condominio/dirección", opcional. Ver migración rename_comuna_to_direccion.
                 // columna `codigo` (MA-XXXX) eliminada (v2.2): el identificador público
                 // es el N° correlativo derivado de `id` (accessor Cotizacion::numero, §4.7).
                 // Ver migración drop_codigo_from_cotizaciones_table.

cotizacion_items: id, cotizacion_id, tipo_espacio_id, tipo_malla_id, tramo_altura_id,
                  metros_lineales(decimal 6,2),
                  precio_ml_min_snapshot(int), precio_ml_max_snapshot(int),
                  multiplicador_snapshot(decimal 4,2),
                  subtotal_min(int), subtotal_max(int), deleted_at

visitas:         id, cotizacion_id, equipo_id(null), fecha_agendada, ventana_horaria,
                 direccion, estado, notas, deleted_at

// Etapas 2–3
trabajos:        id, cotizacion_id, equipo_id, medidas_finales(json), total_final(int),
                 firma_path, estado, finalizado_at
trabajo_fotos:   id, trabajo_id, tipo(enum: anclaje|tension|panoramica), foto_path,
                 tomada_at, lat, lng, aprobada(bool), revisada_por
consumos:        id, trabajo_id, malla_ml, cable_acero_ml, fijaciones(int), costo_total(int)

// Implementado Sprint 4 — foto_path es la ruta relativa en el disco `public`
// (storage/app/public), no una key de R2 (ver §1 y §3: R2 descartado).
galeria_items:   id, foto_path, titulo, tipo_espacio_id(nullable), orden, publicado(bool)
```

**Índices:** `cotizaciones(estado, created_at)`, `trabajo_fotos(trabajo_id, tipo)`.

> **Política de borrado (v2.2, implementado):** las tablas transaccionales — `cotizaciones`, `cotizacion_items`, `visitas` — usan `SoftDeletes` de Laravel (`deleted_at`). **Ningún registro de cliente se elimina físicamente**: son datos de seguimiento comercial y potencial evidencia de negocio, nunca deben desaparecer del todo. El modelo `Cotizacion` replica la cascada a mano en `booted()` (`static::deleting`) porque el `cascadeOnDelete()` de la FK no se dispara con soft deletes — al borrar una cotización, sus `items` y su `visita` también quedan soft-deleted.
>
> Los catálogos (`tipos_espacio`, `tipos_malla`, `tramos_altura`, `tarifas`) **no** llevan `SoftDeletes`: ya tienen su propio mecanismo de desactivación (`activo`, `vigente_hasta`) y duplicar el concepto sería redundante. Cuando se implemente el panel admin (Sprint 5), cualquier acción de "eliminar" sobre cotizaciones/visitas debe ser un soft delete (`->delete()` normal); reservar `forceDelete()` solo para tareas de mantenimiento explícitas fuera del flujo normal de negocio, nunca expuesto en la UI del cliente.

---

## 6. API REST (solo para Etapa 2)

| Verbo | Ruta | Uso |
|---|---|---|
| `POST` | `/api/v1/auth/token` | Login instalador (Sanctum) |
| `GET` | `/api/v1/trabajos/asignados` | Agenda del día del equipo |
| `POST` | `/api/v1/trabajos/{id}/medidas` | Medidas reales → **recalcula con el mismo Service** → devuelve total final |
| `POST` | `/api/v1/trabajos/{id}/firma` | Firma digital del cliente |
| `POST` | `/api/v1/trabajos/{id}/fotos` | Sube el archivo directo al VPS (filesystem local, ver §1 y §3) |
| `POST` | `/api/v1/trabajos/{id}/finalizar` | Bloqueado hasta tener las 3 fotos obligatorias |

**Nota:** el recálculo en terreno usa metros lineales reales + altura medida, sobre las **tarifas del snapshot de la cotización**, no las vigentes. Evita que el cliente vea un precio distinto al que le mostraron.

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
| 5 | Panel admin: tarifas, leads, galería (Etapa 3 parcial) | El papá cambia un precio sin tocar código |
| 5b | *(Etapa CRM, posterior)* Editor de páginas por bloques (ver §11) | Página editada desde el panel se refleja en el sitio sin deploy |
| 6 | Deploy prod + monitoreo (Uptime Kuma) + 1 semana en paralelo con Wix | Corte de DNS |

> Sprint 1 antes que cualquier pixel. Si la fórmula de precio cambia después de tener UI, se rehace la UI.

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
- **Editor de páginas por bloques avanzado**, al estilo **WordPress + Otter Blocks** (o equivalente): permite componer/editar secciones de la landing (hero, qué protegemos, FAQ, etc.) desde el panel sin tocar código ni requerir deploy.

**Implicancias de diseño a tener en cuenta desde ya (sin implementar aún):**
- Las secciones estáticas del §4.1 deben construirse como **componentes Blade/Livewire desacoplados y con props claras**, para que cada una pueda convertirse más adelante en un "bloque" editable sin reescritura completa.
- El contenido "hardcodeado" en Sprint 2–4 (textos, orden de secciones, imágenes) es candidato a migrar a una tabla de tipo `paginas`/`bloques` (JSON de configuración por bloque) cuando se active el editor — no es necesario modelarla todavía, pero **evitar acoplar lógica de negocio a la maquetación estática** para que esa migración sea de datos, no de código.
- El editor de bloques es una funcionalidad de la Etapa 3 (admin), posterior al MVP; no bloquea ni forma parte del criterio de cierre de los Sprints 1–4.
