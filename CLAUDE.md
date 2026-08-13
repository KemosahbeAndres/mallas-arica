# SIGMA v2 — Plan de Ejecución
**Sistema Integral de Gestión Mallas Arica** · Reemplaza a `plan_para_claude.md` (v1)

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
| 8 | Galería estática | Galería administrable desde Etapa 3, imágenes en R2 | Bajo |

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
              └─► sigma-app  (Nginx + PHP-FPM vía supervisord, límite 1 GB)
                    ├─► MariaDB compartida (DB lógica propia, límite ~1.28 GB)
                    ├─► Redis compartido (índices 0–1)
                    └─► Cloudflare R2 (galería pública + fotos de instaladores)

App móvil (Etapa 2) ──► /api/v1/* (Sanctum) ──► mismos Services
```

**Regla no negociable:** toda la lógica de precio vive en `CotizacionCalculatorService`. Livewire lo llama como PHP plano; los controllers de API lo llaman igual. **Cero duplicación de fórmula.**

**Almacenamiento de imágenes (cambio):** las fotografías y evidencias de los trabajos (`trabajo_fotos`) se almacenan en el **mismo contenedor de `sigma-app`** (filesystem local), no en Cloudflare R2. R2 queda reservado solo para la galería pública (`galeria_items`). Esto afecta el endpoint `POST /api/v1/trabajos/{id}/fotos`: ya no se emite presigned URL de R2, el archivo se sube directo al VPS.

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
3. **Barra de atributos** — Transparente · 200 kg/m² · Filtro UV · Mismo día
4. **Qué protegemos** — grid 3×2 de los 6 tipos
5. **Cómo trabajamos** — 4 pasos numerados
6. **Cotizador** — split: formulario (izq, crema) + panel de precio (der, `--ink`, sticky)
7. **Galería** — grid mosaico + "Ver más en WhatsApp →"
8. **Nosotros** — texto + dirección Av. Diego Portales #1333 + teléfono
9. **FAQ** — acordeón, 6 preguntas
10. **CTA final + Footer** — `--ink`

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
│   ├── CotizadorWizard.php      # estado, ítems, wire:model.live.debounce.400ms
│   ├── ItemEspacio.php          # una fila: tipo + ml + altura + malla
│   └── PanelPrecio.php          # rango, desglose, CTA WhatsApp (sticky)
├── GaleriaMosaico.php           # + lightbox Alpine
└── LeadForm.php                 # honeypot + throttle
```

- El acordeón de FAQ y el menú móvil son **Alpine puro**, sin roundtrip a servidor.
- Tarifas vigentes cacheadas en Redis (`sigma:tarifas:v{n}`), invalidadas al guardar desde el admin → el cotizador **no toca la BD por tecla**.

### 4.5 Handoff a WhatsApp (conversión principal)
Al pulsar `Crear por WhatsApp`: se persiste la cotización (estado `borrador`), se genera `codigo` corto (`MA-7K3Q`) y se abre `wa.me` con mensaje prellenado que incluye el código. **El lead se guarda aunque el usuario nunca envíe el mensaje.**

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

// Transaccional
cotizaciones:    id, uuid, codigo, nombre, telefono, email(null), comuna,
                 canal(enum: web|whatsapp|telefono), estado(enum: borrador|contactado|
                 agendado|cerrado|perdido), total_min(int), total_max(int),
                 requiere_visita(bool), utm_source, ip_hash, timestamps

cotizacion_items: id, cotizacion_id, tipo_espacio_id, tipo_malla_id, tramo_altura_id,
                  metros_lineales(decimal 6,2),
                  precio_ml_min_snapshot(int), precio_ml_max_snapshot(int),
                  multiplicador_snapshot(decimal 4,2),
                  subtotal_min(int), subtotal_max(int)

visitas:         id, cotizacion_id, equipo_id(null), fecha_agendada, ventana_horaria,
                 direccion, estado, notas

// Etapas 2–3
trabajos:        id, cotizacion_id, equipo_id, medidas_finales(json), total_final(int),
                 firma_path, estado, finalizado_at
trabajo_fotos:   id, trabajo_id, tipo(enum: anclaje|tension|panoramica), r2_key,
                 tomada_at, lat, lng, aprobada(bool), revisada_por
consumos:        id, trabajo_id, malla_ml, cable_acero_ml, fijaciones(int), costo_total(int)
galeria_items:   id, r2_key, titulo, tipo_espacio_id, orden, publicado(bool)
```

**Índices:** `cotizaciones(estado, created_at)`, `cotizaciones(codigo)` UNIQUE, `trabajo_fotos(trabajo_id, tipo)`.

---

## 6. API REST (solo para Etapa 2)

| Verbo | Ruta | Uso |
|---|---|---|
| `POST` | `/api/v1/auth/token` | Login instalador (Sanctum) |
| `GET` | `/api/v1/trabajos/asignados` | Agenda del día del equipo |
| `POST` | `/api/v1/trabajos/{id}/medidas` | Medidas reales → **recalcula con el mismo Service** → devuelve total final |
| `POST` | `/api/v1/trabajos/{id}/firma` | Firma digital del cliente |
| `POST` | `/api/v1/trabajos/{id}/fotos` | Presigned URL de R2; **el archivo nunca pasa por el VPS** |
| `POST` | `/api/v1/trabajos/{id}/finalizar` | Bloqueado hasta tener las 3 fotos obligatorias |

**Nota:** el recálculo en terreno usa metros lineales reales + altura medida, sobre las **tarifas del snapshot de la cotización**, no las vigentes. Evita que el cliente vea un precio distinto al que le mostraron.

---

## 7. Infraestructura y despliegue (ya operativo)

- VPS OpenCloud 4 GB / 2 vCPU. **Los builds Docker ocurren solo en GitHub Actions → GHCR.** Nunca en el VPS.
- 2 contenedores por entorno: MariaDB + Laravel (Nginx + PHP-FPM vía supervisord).
- R2 con dominio público para galería, detrás de Cloudflare CDN. Servir **WebP/AVIF responsivo**; el peso de imágenes es el único riesgo real de performance de esta landing.
- Fotografías de trabajos (`trabajo_fotos`) se guardan en disco **dentro del contenedor `sigma-app`**, no en R2 (ver §3). Requiere volumen persistente y considerar en `backup-db.sh`/estrategia de backup un backup de archivos aparte.
- `backup-db.sh` diario con rotación + `restore-db.sh` con dump previo de seguridad.

**Migración desde Wix**
1. Wix vivo hasta 1 semana de SIGMA estable en producción.
2. Mapa de 301 (incluye `/page4` y los ítems de nav rotos).
3. `LocalBusiness` + `FAQPage` schema.org, `sitemap.xml`, OG images por sección.

---

## 8. Orden de ejecución

| Sprint | Entregable | Criterio de cierre |
|---|---|---|
| 1 | Migraciones + seeders de catálogos + `CotizacionCalculatorService` + **tests unitarios de los 8 edge cases** | `php artisan test` verde, sin UI |
| 2 | Layout, tokens Tailwind, secciones estáticas 1–5 y 8–10 | Lighthouse ≥ 95 mobile |
| 3 | `CotizadorWizard` + `PanelPrecio` + persistencia de lead + handoff WhatsApp | Lead guardado aunque no se envíe el WhatsApp |
| 4 | Galería (R2) + FAQ + SEO/schema + 301 | Sitemap indexable |
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
