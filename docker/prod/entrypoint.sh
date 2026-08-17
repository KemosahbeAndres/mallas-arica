#!/bin/sh
# Entrypoint de producción de Mallas Arica.
# Responsabilidades: validar entorno, esperar dependencias, cachear.
# NO corre migraciones: eso es un paso explícito del pipeline de deploy,
# para que un fallo de migración sea visible y no un reinicio en bucle.

set -e

echo "[entrypoint] Mallas Arica ${APP_VERSION:-dev} — arrancando"

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
