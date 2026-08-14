# SIGMA — Infraestructura Docker y CI/CD

> Documento de implementación. Anexo al `CLAUDE.md` (§7 Infraestructura y despliegue).
> Cubre el pipeline completo: build en GitHub Actions → GHCR → despliegue por SSH en VPS OpenCloud.

---

## 0. Contexto y decisiones congeladas

| Decisión | Valor | Razón |
|---|---|---|
| Proyecto (código / repo) | `sigma` | Nombre del repositorio y de la imagen en GHCR |
| Directorio en el VPS | `/opt/mallas-arica/` | Convención de nombres del servidor |
| Contenedor de la app | `mallas-arica-app` | Un único contenedor nuevo |
| MariaDB | **Compartida** (instancia preexistente) | DB lógica propia `sigma_prod` |
| Redis | **Compartido** (instancia preexistente) | Índices 0 (cache) y 1 (sesiones) |
| Red de Traefik | `proxy` | Externa, ya existente |
| Red de datos | `backend-shared` | Externa — **⚠ VERIFICAR, ver §0.1** |
| Certresolver TLS | `myle` | Config estática de Traefik |
| Dominio actual | `mallas.tinorte.cl` | Migración a `mallasarica.cl` posterior |
| Colas | **Sin worker** | `QUEUE_CONNECTION=sync` en el MVP |
| Scheduler | `schedule:work` en supervisord | Reemplaza al cron del host |
| Builds Docker | **Solo en GitHub Actions** | 2 vCPU compartidos: nunca construir en el VPS |
| Disparador de deploy | SSH desde Actions | `appleboy/ssh-action` |

### 0.1 Dato a verificar antes de implementar

El nombre `backend-shared` es **provisional**. Confirmar el real:

```bash
docker inspect mariadb --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}'
docker inspect redis   --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}'
```

- Si devuelve `backend-shared` → todo el documento aplica sin cambios.
- Si devuelve otro nombre → ajustar `BACKEND_NETWORK` en `/opt/mallas-arica/.env`.
- Si devuelve **solo `proxy`** → aplicar la variante de red única (§3.4).

---

## 1. Arquitectura del pipeline

```
git push origin main
        │
        ▼
┌─────────────────────── GitHub Actions ───────────────────────┐
│                                                              │
│  job: test                                                   │
│    services: mariadb:11 + redis:7-alpine                     │
│    → composer install (con dev)                              │
│    → pint --test                                             │
│    → php artisan test                                        │
│                                                              │
│  job: build          (needs: test)                           │
│    → buildx multi-stage: node → composer → php-fpm-alpine    │
│    → push a GHCR: :latest y :sha-<commit>                    │
│    → cache type=gha                                          │
│                                                              │
│  job: deploy         (needs: build)                          │
│    → SSH al VPS                                              │
│    → sed IMAGE_TAG en .env  (rollback = revertir esta línea) │
│    → docker compose pull && up -d --wait                     │
│    → php artisan migrate --force                             │
│                                                              │
└──────────────────────────────────────────────────────────────┘
        │
        ▼
┌────────────────────── VPS OpenCloud ─────────────────────────┐
│                                                              │
│  Traefik (red: proxy, certresolver: myle)                    │
│      └─► mallas-arica-app  [límite 1 GB]                     │
│            nginx + php-fpm + scheduler (supervisord)         │
│              ├─► mariadb  (red: backend-shared) sigma_prod   │
│              ├─► redis    (red: backend-shared) db 0-1       │
│              └─► volumen: mallas-arica-storage               │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 2. Edge cases resueltos por este diseño

| # | Problema | Resolución |
|---|---|---|
| 1 | `npm run build` satura los 2 vCPU del VPS | Stage `node` en el Dockerfile; `public/build` viaja en la imagen |
| 2 | `config:cache` en build-time congela un entorno vacío | Cachés generadas en el **entrypoint**, en runtime |
| 3 | `APP_KEY` regenerada invalida sesiones y datos cifrados | El entrypoint **aborta** si falta; nunca la genera |
| 4 | Rebuild de imagen borraría las fotos de `trabajo_fotos` | Volumen nombrado `mallas-arica-storage` |
| 5 | `migrate` fallando en silencio dentro del entrypoint | Paso explícito post-`up`; el job de deploy falla visible |
| 6 | Deploys solapados (dos push seguidos) | `concurrency: group: deploy-prod, cancel-in-progress: false` |
| 7 | Rollback | Tag inmutable `sha-<commit>` en `.env`; revertir y `up -d` |
| 8 | Downtime al recrear el contenedor | Healthcheck `/up` + `up -d --wait`; ~5 s. Blue/green es sobreingeniería aquí |
| 9 | Secretos filtrados en la imagen | `.dockerignore` excluye `.env*`; el `.env` vive solo en el VPS |
| 10 | MariaDB/Redis expuestos a internet | Sin `ports:` en prod; solo redes internas |
| 11 | Traefik devuelve 502 intermitente | `traefik.docker.network=proxy` — el contenedor está en 2 redes |
| 12 | Livewire genera URLs `http://` (mixed content) | `trustProxies` en `bootstrap/app.php` (§4.4) |
| 13 | Colisión de nombres de router entre stacks | Routers/middlewares prefijados `mallas-arica-*` |
| 14 | Logs llenando los 80 GB de disco | `LOG_CHANNEL=stderr` + rotación json-file 10m×3 |
| 15 | Código stale por OPcache | `validate_timestamps=0` es seguro: cada deploy recrea el contenedor |

---

## 3. Archivos a crear

```
sigma/                                  (repositorio)
├── .dockerignore
├── .github/
│   └── workflows/
│       └── deploy.yml
└── docker/
    └── prod/
        ├── Dockerfile
        ├── nginx.conf
        ├── php.ini
        ├── php-fpm-pool.conf
        ├── supervisord.conf
        └── entrypoint.sh

/opt/mallas-arica/                      (VPS, fuera de git)
├── docker-compose.yml
├── .env                                (chmod 600)
└── backups/
```

---

### 3.1 `docker/prod/Dockerfile`

```dockerfile
# syntax=docker/dockerfile:1.7

# ---------- Stage 1: dependencias PHP (sin dev) ----------
FROM composer:2 AS vendor

WORKDIR /app

# Solo los manifiestos primero: maximiza el cache hit de esta capa.
COPY composer.json composer.lock ./

# --no-scripts evita que artisan corra sin el código fuente completo.
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ---------- Stage 2: assets front (Vite) ----------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Tailwind v4 / Livewire escanean blades y PHP: se necesita el fuente completo.
COPY . .
RUN npm run build

# ---------- Stage 3: runtime ----------
FROM php:8.4-fpm-alpine AS runtime

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

# Instalador oficial de extensiones: resuelve deps de compilación y limpia solo.
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        gd \
        zip \
        intl \
        opcache \
        redis \
    && apk add --no-cache \
        nginx \
        supervisor \
        curl \
        tzdata \
    && rm -rf /var/cache/apk/*

ENV TZ=America/Santiago
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

WORKDIR /var/www/html

# --- Configuración de servicios ---
COPY docker/prod/nginx.conf        /etc/nginx/nginx.conf
COPY docker/prod/php.ini           /usr/local/etc/php/conf.d/99-sigma.ini
COPY docker/prod/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-sigma.conf
COPY docker/prod/supervisord.conf  /etc/supervisor/supervisord.conf
COPY docker/prod/entrypoint.sh     /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# --- Código de la aplicación ---
# Orden: vendor primero (cambia poco), luego fuente, luego assets compilados.
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Binario de composer, temporal: el stage runtime no lo trae.
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# composer.json cambió tras el COPY completo: regenerar autoload y correr
# los scripts de paquetes (package:discover) omitidos en el stage vendor.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && rm -f /usr/bin/composer \
    && rm -rf /root/.composer

# storage/ y bootstrap/cache/ deben ser escribibles por php-fpm.
RUN mkdir -p storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
```

---

### 3.2 `docker/prod/nginx.conf`

```nginx
user www-data;
worker_processes auto;
error_log /dev/stderr warn;
pid /run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    # Traefik es el único upstream posible: confiar en su X-Forwarded-For.
    # Restringir a la CIDR real de la red `proxy` si difiere del rango Docker.
    set_real_ip_from 172.16.0.0/12;
    real_ip_header   X-Forwarded-For;
    real_ip_recursive on;

    log_format main '$remote_addr - $status "$request" $body_bytes_sent $request_time';
    access_log /dev/stdout main;

    sendfile           on;
    tcp_nopush         on;
    keepalive_timeout  65;
    server_tokens      off;

    # Subida de fotos de instaladores (Etapa 2). Debe coincidir con php.ini.
    client_max_body_size 20M;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript
               text/xml application/xml image/svg+xml;

    server {
        listen 80 default_server;
        server_name _;
        root /var/www/html/public;
        index index.php;

        charset utf-8;

        # Assets con hash de Vite: inmutables, cache agresivo.
        location /build/ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            access_log off;
            try_files $uri =404;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS $http_x_forwarded_proto;
            fastcgi_hide_header X-Powered-By;
            fastcgi_read_timeout 60s;
            fastcgi_buffers 16 16k;
            fastcgi_buffer_size 32k;
        }

        # Bloquea dotfiles salvo el challenge de ACME.
        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
```

---

### 3.3 `docker/prod/php.ini`

```ini
; --- Límites de request ---
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 21M
max_execution_time = 60

; --- Seguridad ---
expose_php = Off
display_errors = Off
log_errors = On
error_log = /dev/stderr

; --- OPcache ---
; validate_timestamps=0: el código nunca cambia dentro de un contenedor vivo.
; Cada deploy crea un contenedor nuevo, así que no hay riesgo de código stale.
; COROLARIO: nunca editar archivos dentro del contenedor en producción.
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1

; JIT desactivado: en apps web PHP el beneficio es marginal y consume RAM
; que en este VPS (4 GB compartidos) vale más para php-fpm.
opcache.jit = disable

date.timezone = America/Santiago
```

---

### 3.4 `docker/prod/php-fpm-pool.conf`

```ini
[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000

; Presupuesto contra el límite de 1 GB del contenedor:
; nginx ~15 MB + master ~30 MB + scheduler ~40 MB + 10 workers × ~65 MB ≈ 735 MB.
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 2
pm.max_spare_servers = 4

; Recicla workers para acotar fugas de memoria en procesos largos.
pm.max_requests = 500

; Logs a stdout para que Dozzle los capture.
catch_workers_output = yes
decorate_workers_output = no
clear_env = no

; El healthcheck a /up no debe inundar el log de acceso.
access.log = /dev/null
```

---

### 3.5 `docker/prod/supervisord.conf`

```ini
[supervisord]
nodaemon=true
user=root
logfile=/dev/stdout
logfile_maxbytes=0
pidfile=/run/supervisord.pid

[program:php-fpm]
command=php-fpm --nodaemonize
autostart=true
autorestart=true
priority=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
priority=20
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

; schedule:work reemplaza al cron del host. Un solo proceso, tick cada minuto.
[program:scheduler]
command=php /var/www/html/artisan schedule:work
user=www-data
autostart=true
autorestart=true
priority=30
stopwaitsecs=70
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

; Etapa 2 — cuando entren las colas, añadir aquí (numprocs=1, no más):
; [program:queue]
; command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
; user=www-data
; numprocs=1
; stopwaitsecs=3600
```

---

### 3.6 `docker/prod/entrypoint.sh`

```sh
#!/bin/sh
# Entrypoint de producción de SIGMA.
# Responsabilidades: validar entorno, esperar dependencias, cachear.
# NO corre migraciones: eso es un paso explícito del pipeline de deploy,
# para que un fallo de migración sea visible y no un reinicio en bucle.

set -e

echo "[entrypoint] SIGMA ${APP_VERSION:-dev} — arrancando"

# --- 1. Validación de APP_KEY (aborto explícito, nunca regeneración) ---
# Regenerar la clave invalidaría sesiones y cualquier campo cifrado en BD.
if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "base64:" ]; then
    echo "[entrypoint] FATAL: APP_KEY no definida en el entorno." >&2
    echo "[entrypoint] Genera una con 'php artisan key:generate --show' y" >&2
    echo "[entrypoint] añádela a /opt/mallas-arica/.env. Abortando." >&2
    exit 1
fi

if [ "${APP_ENV}" != "production" ]; then
    echo "[entrypoint] AVISO: APP_ENV='${APP_ENV}', se esperaba 'production'."
fi

if [ "${APP_DEBUG}" = "true" ]; then
    echo "[entrypoint] FATAL: APP_DEBUG=true en producción. Abortando." >&2
    exit 1
fi

# --- 2. Espera de MariaDB (instancia compartida) ---
DB_HOST="${DB_HOST:-mariadb}"
DB_PORT="${DB_PORT:-3306}"
MAX_ATTEMPTS=30
ATTEMPTS=0

echo "[entrypoint] Esperando MariaDB en ${DB_HOST}:${DB_PORT}..."
until php -r "
    \$c = @fsockopen('${DB_HOST}', ${DB_PORT}, \$e, \$s, 2);
    exit(\$c ? 0 : 1);
" 2>/dev/null; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "${ATTEMPTS}" -ge "${MAX_ATTEMPTS}" ]; then
        echo "[entrypoint] FATAL: MariaDB no respondió tras ${MAX_ATTEMPTS} intentos." >&2
        exit 1
    fi
    sleep 2
done
echo "[entrypoint] MariaDB disponible."

# --- 3. Espera de Redis (compartido, índices 0-1) ---
REDIS_HOST="${REDIS_HOST:-redis}"
REDIS_PORT="${REDIS_PORT:-6379}"
ATTEMPTS=0

echo "[entrypoint] Esperando Redis en ${REDIS_HOST}:${REDIS_PORT}..."
until php -r "
    \$c = @fsockopen('${REDIS_HOST}', ${REDIS_PORT}, \$e, \$s, 2);
    exit(\$c ? 0 : 1);
" 2>/dev/null; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "${ATTEMPTS}" -ge "${MAX_ATTEMPTS}" ]; then
        echo "[entrypoint] FATAL: Redis no respondió tras ${MAX_ATTEMPTS} intentos." >&2
        exit 1
    fi
    sleep 2
done
echo "[entrypoint] Redis disponible."

# --- 4. Symlink de storage (idempotente; el volumen persiste entre deploys) ---
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link --quiet || true
fi

# El volumen montado puede traer permisos ajenos.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# --- 5. Cachés (en runtime: en build-time el entorno aún no existe) ---
echo "[entrypoint] Generando cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "[entrypoint] Listo. Cediendo control a supervisord."
exec "$@"
```

---

### 3.7 `.dockerignore`

```gitignore
# Secretos — crítico: nunca deben entrar a la imagen
.env
.env.*
!.env.example

# Artefactos regenerados dentro del build
/vendor
/node_modules
/public/build
/public/hot
/public/storage

# Estado local
/storage/*.key
/storage/pail
/storage/framework/cache/*
/storage/framework/sessions/*
/storage/framework/views/*
/storage/logs/*
/storage/app/public/*
/bootstrap/cache/*

# Herramientas y control de versiones
.git
.github
.gitignore
.idea
.vscode
.fleet
.phpunit.result.cache
.phpunit.cache
docker-compose.yml
docker-compose.override.yml
*.md
!composer.json

# Tests: no se necesitan en la imagen de producción
/tests
```

---

## 4. Orquestación en el VPS

### 4.1 `/opt/mallas-arica/docker-compose.yml`

```yaml
# SIGMA — producción.
# MariaDB y Redis son instancias COMPARTIDAS preexistentes en el VPS.
# Este stack levanta únicamente el contenedor de la aplicación.
# Las imágenes NUNCA se construyen aquí: se traen de GHCR.

# Nombre de proyecto explícito: no depender del nombre del directorio.
name: mallas-arica

services:
  app:
    image: ghcr.io/${GITHUB_OWNER}/${IMAGE_NAME}:${IMAGE_TAG:-latest}
    container_name: mallas-arica-app
    restart: unless-stopped
    pull_policy: always

    # Todo el entorno vive fuera de la imagen.
    env_file:
      - .env

    volumes:
      # Único estado persistente: fotos de trabajos y adjuntos.
      # Sobrevive a cada recreación del contenedor.
      - mallas-arica-storage:/var/www/html/storage/app/public

    networks:
      - proxy
      - backend

    # El healthcheck viene definido en la imagen (curl a /up).
    # `docker compose up -d --wait` bloquea hasta que pase.

    deploy:
      resources:
        limits:
          memory: 1G
        reservations:
          memory: 256M

    # Evita que un bucle de logs llene los 80 GB del disco.
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"

    labels:
      - "traefik.enable=true"

      # CRÍTICO: el contenedor está en 2 redes. Sin esto Traefik puede
      # elegir la IP de `backend` y devolver 502 intermitente.
      - "traefik.docker.network=proxy"

      # --- Router HTTPS ---
      - "traefik.http.routers.mallas-arica.rule=Host(`${APP_DOMAIN}`)"
      - "traefik.http.routers.mallas-arica.entrypoints=websecure"
      - "traefik.http.routers.mallas-arica.tls=true"
      - "traefik.http.routers.mallas-arica.tls.certresolver=myle"
      - "traefik.http.routers.mallas-arica.service=mallas-arica"
      - "traefik.http.routers.mallas-arica.middlewares=mallas-arica-headers"

      # --- Redirección HTTP → HTTPS ---
      # Eliminar si ya existe redirección global en la config estática de Traefik.
      - "traefik.http.routers.mallas-arica-http.rule=Host(`${APP_DOMAIN}`)"
      - "traefik.http.routers.mallas-arica-http.entrypoints=web"
      - "traefik.http.routers.mallas-arica-http.middlewares=mallas-arica-redirect"
      - "traefik.http.middlewares.mallas-arica-redirect.redirectscheme.scheme=https"
      - "traefik.http.middlewares.mallas-arica-redirect.redirectscheme.permanent=true"

      # --- Cabeceras de seguridad ---
      # NO añadir stsPreload hasta estar en el dominio definitivo.
      - "traefik.http.middlewares.mallas-arica-headers.headers.stsSeconds=31536000"
      - "traefik.http.middlewares.mallas-arica-headers.headers.stsIncludeSubdomains=true"
      - "traefik.http.middlewares.mallas-arica-headers.headers.contentTypeNosniff=true"
      - "traefik.http.middlewares.mallas-arica-headers.headers.browserXssFilter=true"
      - "traefik.http.middlewares.mallas-arica-headers.headers.referrerPolicy=strict-origin-when-cross-origin"
      - "traefik.http.middlewares.mallas-arica-headers.headers.customRequestHeaders.X-Forwarded-Proto=https"

      # --- Servicio ---
      - "traefik.http.services.mallas-arica.loadbalancer.server.port=80"

      # Watchtower no debe tocar este contenedor: el deploy es explícito.
      - "com.centurylinklabs.watchtower.enable=false"

volumes:
  mallas-arica-storage:
    name: mallas-arica-storage

networks:
  proxy:
    external: true
    name: proxy
  backend:
    external: true
    name: ${BACKEND_NETWORK}
```

#### Variante: si MariaDB y Redis también están en `proxy`

Reemplazar el bloque `networks:` del servicio y el bloque `networks:` raíz:

```yaml
    networks:
      - proxy
```

```yaml
networks:
  proxy:
    external: true
    name: proxy
```

Y eliminar `BACKEND_NETWORK` del `.env`. Menos superficie de red, un fallo menos que diagnosticar.

---

### 4.2 `/opt/mallas-arica/.env`

```bash
# ==========================================
# SIGMA — producción. NUNCA versionar.
# chmod 600 /opt/mallas-arica/.env
# ==========================================

# --- Despliegue ---
COMPOSE_PROJECT_NAME=mallas-arica
GITHUB_OWNER=tu-usuario-github
IMAGE_NAME=sigma
IMAGE_TAG=latest                    # el pipeline lo sobrescribe con sha-<commit>
APP_DOMAIN=mallas.tinorte.cl
BACKEND_NETWORK=backend-shared      # ← VERIFICAR (§0.1)

# --- Aplicación ---
APP_NAME=SIGMA
APP_ENV=production
APP_KEY=                            # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://mallas.tinorte.cl
APP_TIMEZONE=America/Santiago
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

# --- Logs ---
# stderr: los recoge Docker y los muestra Dozzle. No escribir a disco.
LOG_CHANNEL=stderr
LOG_STACK=stderr
LOG_LEVEL=warning

# --- Base de datos (instancia MariaDB compartida) ---
# Usuario dedicado, jamás root.
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sigma_prod
DB_USERNAME=sigma_prod
DB_PASSWORD=

# --- Redis (instancia compartida, índices 0-1 reservados a SIGMA) ---
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CACHE_DB=0
REDIS_DB=1

# --- Drivers ---
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true          # obligatorio tras el TLS de Traefik
SESSION_SAME_SITE=lax
SESSION_DOMAIN=mallas.tinorte.cl
QUEUE_CONNECTION=sync               # sin worker en el MVP
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public

# --- Vite ---
VITE_APP_NAME="${APP_NAME}"

# --- WhatsApp (handoff de conversión) ---
WHATSAPP_NUMERO=56900000000

# --- Cloudflare R2 (galería pública) ---
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=
R2_PUBLIC_URL=
```

---

### 4.3 Preparación del VPS (una sola vez)

```bash
# 1. Estructura de directorios
sudo mkdir -p /opt/mallas-arica/backups
sudo chown -R deploy:deploy /opt/mallas-arica

# 2. DB y usuario dedicados en la MariaDB compartida
docker exec -it mariadb mariadb -uroot -p -e "
  CREATE DATABASE sigma_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'sigma_prod'@'%' IDENTIFIED BY 'CAMBIAR_POR_PASSWORD_FUERTE';
  GRANT ALL PRIVILEGES ON sigma_prod.* TO 'sigma_prod'@'%';
  FLUSH PRIVILEGES;"

# 3. Autenticar Docker contra GHCR (PAT clásico con scope read:packages)
echo "$GHCR_PAT" | docker login ghcr.io -u TU_USUARIO --password-stdin

# 4. Copiar el .env de §4.2, generar APP_KEY y pegarla
docker run --rm ghcr.io/TU_USUARIO/sigma:latest php artisan key:generate --show

# 5. Proteger el archivo
chmod 600 /opt/mallas-arica/.env

# 6. Confirmar que la red proxy existe
docker network inspect proxy --format '{{range .Containers}}{{.Name}}{{"\n"}}{{end}}'
```

---

### 4.4 Ajuste obligatorio en la app: `bootstrap/app.php`

Sin esto, Livewire genera URLs `http://` detrás de Traefik y el navegador bloquea las peticiones por mixed content.

```php
use Illuminate\Http\Request;

// ...

->withMiddleware(function (Middleware $middleware) {
    // Traefik termina el TLS. Sin esta línea, url() y Livewire
    // generan http:// y rompen el sitio en producción.
    // at:'*' es seguro porque el contenedor no expone puertos:
    // el único origen posible de tráfico es Traefik en la red Docker.
    $middleware->trustProxies(
        at: '*',
        headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
    );
})
```

---

## 5. Pipeline de CI/CD

### 5.1 Secrets a configurar en GitHub

`Settings → Secrets and variables → Actions`

| Secret | Contenido |
|---|---|
| `VPS_HOST` | IP o hostname del VPS |
| `VPS_USER` | `deploy` |
| `VPS_PORT` | Puerto SSH (por defecto `22`) |
| `VPS_SSH_KEY` | Clave privada ed25519 completa, con cabecera y pie |

`GITHUB_TOKEN` es automático: no se crea. Solo requiere `permissions: packages: write` en el job.

Generar el par de claves para el deploy:

```bash
ssh-keygen -t ed25519 -C "gh-actions-sigma" -f ~/.ssh/sigma_deploy -N ""
ssh-copy-id -i ~/.ssh/sigma_deploy.pub deploy@VPS_HOST
cat ~/.ssh/sigma_deploy     # → pegar en el secret VPS_SSH_KEY
```

---

### 5.2 `.github/workflows/deploy.yml`

```yaml
name: CI/CD

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

env:
  REGISTRY: ghcr.io

# Serializa los deploys. cancel-in-progress=false: nunca abortar un deploy
# a medias, podría dejar migraciones aplicadas sin la imagen correspondiente.
concurrency:
  group: deploy-prod
  cancel-in-progress: false

jobs:
  # ==========================================================
  # 1. TESTS
  # ==========================================================
  test:
    name: Tests
    runs-on: ubuntu-latest

    services:
      mariadb:
        image: mariadb:11
        env:
          MARIADB_DATABASE: sigma_test
          MARIADB_USER: sigma
          MARIADB_PASSWORD: secret
          MARIADB_ROOT_PASSWORD: root
        ports:
          - 3306:3306
        options: >-
          --health-cmd="healthcheck.sh --connect --innodb_initialized"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Configurar PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_mysql, mbstring, bcmath, exif, pcntl, gd, zip, intl, redis
          coverage: none

      - name: Cachear dependencias de Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Instalar dependencias
        run: composer install --no-interaction --prefer-dist --no-progress

      - name: Preparar entorno de test
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Verificar estilo (Pint)
        run: ./vendor/bin/pint --test

      - name: Ejecutar tests
        env:
          APP_ENV: testing
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: sigma_test
          DB_USERNAME: sigma
          DB_PASSWORD: secret
          REDIS_HOST: 127.0.0.1
          REDIS_PORT: 6379
          CACHE_STORE: redis
          SESSION_DRIVER: redis
        run: php artisan test

  # ==========================================================
  # 2. BUILD Y PUSH A GHCR
  # ==========================================================
  build:
    name: Build y push
    runs-on: ubuntu-latest
    needs: test
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'

    permissions:
      contents: read
      packages: write

    outputs:
      image_tag: ${{ steps.meta.outputs.short_sha }}

    steps:
      - uses: actions/checkout@v4

      - name: Calcular tag e imagen
        id: meta
        run: |
          # GHCR exige minúsculas en el nombre del repositorio.
          echo "image=${REGISTRY}/$(echo '${{ github.repository }}' | tr '[:upper:]' '[:lower:]')" >> $GITHUB_OUTPUT
          echo "short_sha=sha-$(git rev-parse --short HEAD)" >> $GITHUB_OUTPUT

      - uses: docker/setup-buildx-action@v3

      - name: Login en GHCR
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build y push
        uses: docker/build-push-action@v6
        with:
          context: .
          file: docker/prod/Dockerfile
          push: true
          # Tag inmutable por SHA + latest. El SHA permite rollback exacto.
          tags: |
            ${{ steps.meta.outputs.image }}:${{ steps.meta.outputs.short_sha }}
            ${{ steps.meta.outputs.image }}:latest
          build-args: |
            APP_VERSION=${{ steps.meta.outputs.short_sha }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          provenance: false

  # ==========================================================
  # 3. DEPLOY AL VPS
  # ==========================================================
  deploy:
    name: Deploy a producción
    runs-on: ubuntu-latest
    needs: build
    environment: production

    steps:
      - name: Desplegar por SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          port: ${{ secrets.VPS_PORT }}
          script_stop: true
          envs: IMAGE_TAG
          script: |
            set -euo pipefail
            cd /opt/mallas-arica

            # Guardar el tag actual para poder revertir.
            PREV_TAG=$(grep '^IMAGE_TAG=' .env | cut -d= -f2)
            echo "Tag anterior: ${PREV_TAG} → nuevo: ${IMAGE_TAG}"

            rollback() {
              echo "::error::Deploy fallido. Revirtiendo a ${PREV_TAG}"
              sed -i "s|^IMAGE_TAG=.*|IMAGE_TAG=${PREV_TAG}|" .env
              docker compose up -d --wait || true
              exit 1
            }
            trap rollback ERR

            # Apuntar al nuevo tag y traer la imagen.
            sed -i "s|^IMAGE_TAG=.*|IMAGE_TAG=${IMAGE_TAG}|" .env
            docker compose pull

            # --wait bloquea hasta que el healthcheck /up pase.
            docker compose up -d --wait --wait-timeout 120

            # Migraciones: paso explícito y visible, no en el entrypoint.
            docker compose exec -T app php artisan migrate --force

            trap - ERR

            # Liberar capas huérfanas: el disco son 80 GB compartidos.
            docker image prune -f --filter "until=168h"

            echo "Deploy OK: ${IMAGE_TAG}"
        env:
          IMAGE_TAG: ${{ needs.build.outputs.image_tag }}
```

---

## 6. Procedimientos operativos

### 6.1 Primer despliegue manual

```bash
cd /opt/mallas-arica
docker compose config | grep -E 'image:|container_name:|name:'   # verificar variables
docker compose up -d --wait
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --force           # catálogos de Sprint 1
```

### 6.2 Rollback manual

```bash
cd /opt/mallas-arica

# Ver tags disponibles
docker images ghcr.io/TU_USUARIO/sigma --format '{{.Tag}}\t{{.CreatedSince}}'

sed -i "s|^IMAGE_TAG=.*|IMAGE_TAG=sha-abc1234|" .env
docker compose up -d --wait
```

> **Atención:** el rollback de imagen **no revierte migraciones**. Si el deploy fallido incluía una migración destructiva, restaurar con `restore-db.sh` antes de revertir la imagen.

### 6.3 Diagnóstico

```bash
docker compose logs -f app --tail 100
docker compose exec -T app php artisan about --only=environment,drivers
docker stats mallas-arica-app --no-stream

# Conectividad con los servicios compartidos
docker compose exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
docker compose exec -T app php artisan tinker --execute="echo Cache::store('redis')->getStore()->connection()->ping();"

# Extremo a extremo
curl -sI https://mallas.tinorte.cl/up | head -3
```

**Interpretación de fallos de Traefik:**

| Síntoma | Causa probable |
|---|---|
| 404 desde Traefik | La etiqueta `traefik.docker.network` no coincide, o el contenedor no está en `proxy` |
| 502 intermitente | Traefik resuelve la IP de `backend` en vez de `proxy` |
| Certificado inválido | El DNS de `mallas.tinorte.cl` no apunta al VPS, o el certresolver `myle` falló el challenge |
| Mixed content / Livewire roto | Falta `trustProxies` en `bootstrap/app.php` (§4.4) |

### 6.4 Backup del volumen de storage

`backup-db.sh` cubre MariaDB, **no** el volumen de archivos. Añadir al cron cuando entre la Etapa 2:

```bash
docker run --rm \
  -v mallas-arica-storage:/data:ro \
  -v /opt/mallas-arica/backups:/out \
  alpine tar czf /out/storage-$(date +%F).tgz -C /data .
```

---

## 7. Validación local antes del primer push

```bash
# 1. route:cache falla si hay closures en las rutas
php artisan route:cache && php artisan route:clear

# 2. Build de la imagen (en Fedora, nunca en el VPS)
docker build -f docker/prod/Dockerfile -t sigma-app:test .

# 3. El entrypoint debe abortar sin APP_KEY (código de salida 1)
docker run --rm -e APP_ENV=production sigma-app:test; echo "exit=$?"

# 4. Auditar que .env no entró a la imagen
docker run --rm --entrypoint sh sigma-app:test -c 'ls -la /var/www/html/.env 2>&1'
# Esperado: "No such file or directory"

# 5. Tamaño resultante (objetivo: < 250 MB)
docker images sigma-app:test --format '{{.Size}}'
```

---

## 8. Checklist de implementación

**Repositorio**
- [ ] Crear los 6 archivos en `docker/prod/`
- [ ] Crear `.dockerignore`
- [ ] Crear `.github/workflows/deploy.yml`
- [ ] Añadir `trustProxies` a `bootstrap/app.php`
- [ ] Verificar `php artisan route:cache` sin errores
- [ ] Verificar `./vendor/bin/pint --test` en verde

**GitHub**
- [ ] Secrets: `VPS_HOST`, `VPS_USER`, `VPS_PORT`, `VPS_SSH_KEY`
- [ ] Environment `production` creado

**VPS**
- [ ] Confirmar nombre real de la red de datos (§0.1) → ajustar `BACKEND_NETWORK`
- [ ] `/opt/mallas-arica/` creado y con owner `deploy`
- [ ] Usuario SSH `deploy` con clave pública instalada y acceso al grupo `docker`
- [ ] `docker login ghcr.io` con PAT (`read:packages`)
- [ ] DB `sigma_prod` + usuario dedicado creados
- [ ] `.env` completo, con `APP_KEY`, en `chmod 600`
- [ ] `docker-compose.yml` copiado
- [ ] DNS de `mallas.tinorte.cl` apuntando al VPS
- [ ] `docker compose up -d --wait` + `migrate --force` manual
- [ ] `https://mallas.tinorte.cl/up` devuelve 200
- [ ] Monitor de Uptime Kuma apuntando a `/up`

---

## 9. Deuda técnica reconocida

| Tema | Estado actual | Cuándo abordarlo |
|---|---|---|
| Aislamiento de Redis | Separación por índice (convención, no seguridad) | Solo si `tinorte.cl` aloja un cliente que paga → ACLs de Redis 6+ |
| `set_real_ip_from 172.16.0.0/12` | Rango Docker genérico | Restringir a la CIDR real de `proxy` si se endurece el VPS |
| Secretos vía `env_file` | Visibles con `docker inspect` | Estándar; mantener mínimos los miembros del grupo `docker` |
| Downtime de ~5 s por deploy | Aceptado | Blue/green solo si el sitio pasa a ser crítico |
| Backup del volumen de storage | No automatizado | Antes de la Etapa 2 (fotos de instaladores) |
| Migración a `mallasarica.cl` | Pendiente | Sprint 6 — cambiar `APP_DOMAIN`, `APP_URL`, `SESSION_DOMAIN` y regenerar cert |
| `stsPreload` | Desactivado a propósito | Solo tras consolidar el dominio definitivo |
| Worker de colas | Sin implementar | Etapa 2 — bloque comentado en `supervisord.conf` |