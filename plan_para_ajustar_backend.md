# Plan de implementación — SIGMA sobre datos compartidos

> Documento de implementación. Destinatario: instancia de Claude con acceso al repositorio `sigma` y al VPS.
> Objetivo: eliminar los contenedores MariaDB y Redis propios de SIGMA y consumir las instancias
> compartidas del VPS, con usuario, base prefijada y prefijo de claves propios.
>
> **PRERREQUISITO BLOQUEANTE:** repositorio `vpsa-infra` (privado, GitHub `KemosahbeAndres/vpsa-infra`,
> local en `~/proyectos-personales/vpsa-infra`, desplegado en `/opt/infra` del VPS) completo hasta su
> `CLAUDE.md` §9 paso 7. Este documento asume que `shared-mariadb` y `shared-redis` están arriba, que
> la red `backend-shared` existe y que `./scripts/provision-app.sh sigma prod` ya se ejecutó y sus
> credenciales están a mano. Toda referencia a `PLAN-VPS-INFRA.md` en este documento es al `CLAUDE.md`
> de ese repositorio (mismo contenido; se renombró al convertir la carpeta en repo versionado).

### Estado del prerrequisito (2026-08-16)

Avance real en el VPS a la fecha, para no repetir pasos ya hechos:

- [x] Repo `vpsa-infra` clonado en `/opt/infra` vía deploy key de solo lectura (`github-vpsinfra`).
- [x] Estado trasplantado a `/opt/infra/state/`: `acme.json`, `portainer-data/`, `uptime-kuma-data/`
      (copia directa desde `/home/andres/infraestructura`, no pasa por git).
- [x] `/opt/infra/.env` generado en el propio VPS (`MARIADB_ROOT_PASSWORD`, `BACKUP_DB_PASSWORD`,
      `BACKUP_REDIS_PASSWORD`) — nunca transitó por la máquina local.
- [x] Red `backend-shared` creada.
- [x] Stack `edge/` arriba y sano (`traefik`, `portainer`, `uptime-kuma`, `dozzle`).
      **Nota importante:** la imagen quedó pinneada en `traefik:v3.6.1`, no `v3.5` como indica el
      `CLAUDE.md` de `vpsa-infra` en su versión original. Causa: Docker Engine 29.x subió la versión
      mínima de API aceptada y el cliente Docker embebido en Traefik ≤3.6.0 trae una versión
      hardcodeada vieja (1.24) que el daemon nuevo rechaza en seco
      ([traefik/traefik#12253](https://github.com/traefik/traefik/issues/12253), fix en 3.6.1). Si
      `vpsa-infra` se reclona o se hace `pull` en otra máquina, confirmar que el compose de `edge/`
      siga en `v3.6.1` o superior antes de asumir que el error es de configuración de red.
- [x] Portainer mostró "New Portainer installation" tras el primer `up` — es el timeout de seguridad
      de Portainer por tardar en completar el asistente, no pérdida de datos. Se resolvió con
      `docker restart portainer` y entrando de inmediato.
- [ ] Stack `data/` (shared-mariadb + shared-redis) — **pendiente de levantar**. Sin esto, ningún
      comando de este documento que toque `shared-mariadb`/`shared-redis` funciona todavía.
- [ ] `./scripts/provision-app.sh --bootstrap` (usuarios de backup) — pendiente, depende de lo anterior.
- [ ] `./scripts/provision-app.sh sigma prod` — pendiente, depende de lo anterior. Es el paso que
      genera las credenciales que usa la §3 de este documento.

---

## 0. Alcance y decisiones

**Este plan afecta solo a producción.** En desarrollo local se sigue levantando MariaDB y Redis como
contenedores propios vía el `docker-compose.yml` de la raíz del repo (`sigma-mariadb-dev`,
`sigma-redis-dev`) — ese archivo **no se toca** en ninguna sección de este documento. Todas las
ediciones de §2–§4 aplican exclusivamente a `deploy/docker-compose.yml` y al `.env` de
`/opt/mallas-arica` en el VPS.

| Elemento | Antes | Después |
|---|---|---|
| Contenedores del stack | `app` + `mariadb` + `redis` | Solo `app` |
| Base de datos | `sigma_prod` en contenedor propio | `sigma_prod` en `shared-mariadb` |
| Usuario MariaDB | `sigma_prod` | `sigma`, con `GRANT` sobre `` `sigma\_%`.* `` y `MAX_USER_CONNECTIONS 20` |
| Redis | Contenedor propio, sin auth | `shared-redis`, usuario ACL `sigma`, claves `sigma:*` |
| Red de datos | `mallas-arica-backend` (interna) | `backend-shared` (externa) |
| `DB_HOST` / `REDIS_HOST` | `mariadb` / `redis` | **sin cambios** (aliases de red en el stack compartido) |
| Volúmenes | `...-mariadb-data`, `...-redis-data`, `...-storage` | Solo `mallas-arica-storage` |
| Backups | `backup-db.sh` propio de SIGMA | `/opt/infra/scripts/backup-app.sh sigma` |
| RAM del stack | ~1.6 GB (1 G + 512 M + 128 M) | 1 GB |

**Lo que NO cambia y no hay que tocar:**

- `docker/prod/Dockerfile` y todo `docker/prod/`.
- `docker/prod/entrypoint.sh`: sus bucles de espera de MariaDB y Redis usan `fsockopen`, que no
  autentica. Siguen siendo correctos y **ganan importancia**, porque el `depends_on` desaparece.
- El job `test` de `.github/workflows/deploy.yml`: levanta servicios efímeros propios de GitHub
  Actions, sin relación con el VPS.
- La lógica de la aplicación. Cero cambios de código PHP.

---

## 1. Verificaciones previas al primer cambio

Estas cuatro cosas se dan por supuestas en el resto del plan. Confirmar antes de editar nada.

```bash
# 1. La extensión phpredis está en la imagen. Si falta, `REDIS_CLIENT=phpredis`
#    revienta en runtime y el .env de provision-app.sh no sirve tal cual.
grep -n 'redis' docker/prod/Dockerfile
docker run --rm --entrypoint php ghcr.io/<owner>/sigma:latest -m | grep -i redis
# Si no aparece: o se añade `pecl install redis` al Dockerfile, o se usa
# REDIS_CLIENT=predis (más lento, sin extensión) y `composer require predis/predis`.

# 2. config/database.php acepta usuario y prefijo de Redis.
grep -n -A3 "'username'" config/database.php
grep -n "REDIS_PREFIX" config/database.php
# Laravel 12 trae ambos por defecto:
#   'username' => env('REDIS_USERNAME'),
#   'options' => ['prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME','laravel'),'_').'_database_')]
# Si el proyecto los eliminó, restaurarlos: sin `prefix` el ACL bloquea TODO.

# 3. La red compartida existe.
docker network inspect backend-shared --format '{{.Name}}'

# 4. Los aliases resuelven desde esa red.
docker run --rm --network backend-shared alpine sh -c \
  'nslookup mariadb 2>/dev/null | tail -3; nslookup redis 2>/dev/null | tail -3'
```

---

## 2. `deploy/docker-compose.yml` — ediciones puntuales

No reescribir el archivo. Cuatro ediciones sobre el existente.

### 2.1 Eliminar `depends_on` del servicio `app`

```diff
     env_file:
       - .env
 
-    depends_on:
-      mariadb:
-        condition: service_healthy
-      redis:
-        condition: service_healthy
-
     volumes:
```

Los servicios compartidos tienen su propio ciclo de vida; `depends_on` solo opera dentro del mismo
proyecto de Compose. La espera la cubre `entrypoint.sh` (30 intentos × 2 s = 60 s de margen).

### 2.2 Eliminar los servicios `mariadb` y `redis`

Borrar desde `  mariadb:` hasta el final del bloque de `redis`, justo antes de `volumes:`.

### 2.3 Reducir el bloque `volumes:`

```diff
 volumes:
   mallas-arica-storage:
     name: mallas-arica-storage
-  mallas-arica-mariadb-data:
-    name: mallas-arica-mariadb-data
-  mallas-arica-redis-data:
-    name: mallas-arica-redis-data
```

### 2.4 `backend` pasa a ser una red externa

```diff
 networks:
   proxy:
     external: true
     name: proxy
-  # Interna: la crea este mismo stack, no depende de otro proyecto del VPS.
-  backend:
-    name: mallas-arica-backend
+  # Externa: la crea el stack de infraestructura (/opt/infra).
+  # Da acceso a shared-mariadb (alias `mariadb`) y shared-redis (alias `redis`).
+  backend:
+    external: true
+    name: backend-shared
```

### 2.5 Eliminar el middleware de redirección redundante

Traefik ya aplica redirección HTTP→HTTPS global en el entrypoint `web`
(`--entrypoints.web.http.redirections.entryPoint.to=websecure`). El router y el middleware
por aplicación son código muerto que solo puede desincronizarse.

```diff
-      # --- Redirección HTTP → HTTPS ---
-      # Eliminar si ya existe redirección global en la config estática de Traefik.
-      - "traefik.http.routers.mallas-arica-http.rule=Host(`${APP_DOMAIN}`)"
-      - "traefik.http.routers.mallas-arica-http.entrypoints=web"
-      - "traefik.http.routers.mallas-arica-http.middlewares=mallas-arica-redirect"
-      - "traefik.http.middlewares.mallas-arica-redirect.redirectscheme.scheme=https"
-      - "traefik.http.middlewares.mallas-arica-redirect.redirectscheme.permanent=true"
-
       # --- Cabeceras de seguridad ---
```

### 2.6 Encabezado del archivo

```diff
 # SIGMA — producción.
-# MariaDB y Redis son EXCLUSIVOS de este stack (no compartidos con otros
-# proyectos del VPS): cada uno con su propio volumen, solo accesibles en
-# la red interna `backend`. Las imágenes de la app NUNCA se construyen
-# aquí: se traen de GHCR.
+# Este stack levanta ÚNICAMENTE el contenedor de la aplicación.
+# MariaDB y Redis son instancias COMPARTIDAS gestionadas por el repositorio
+# `vpsa-infra` en /opt/infra/data, alcanzables por la red externa
+# `backend-shared` con los aliases `mariadb` y `redis`.
+# Aislamiento: base `sigma_prod` + usuario `sigma` (MariaDB); usuario ACL
+# `sigma` limitado al patrón de claves `sigma:*` (Redis).
+# Las imágenes NUNCA se construyen aquí: se traen de GHCR.
```

### 2.7 Comprobación

```bash
docker compose -f deploy/docker-compose.yml config >/dev/null && echo "sintaxis OK"
grep -c 'container_name' deploy/docker-compose.yml   # esperado: 1
```

---

## 3. `deploy/.env.production.example`

```diff
-DB_ROOT_PASSWORD=
+# DB_ROOT_PASSWORD eliminada: la app nunca fue root y ahora MariaDB
+# lo gestiona el stack de infraestructura (/opt/infra/.env).

 DB_CONNECTION=mysql
-DB_HOST=mariadb
+DB_HOST=mariadb                 # alias de red de shared-mariadb
 DB_PORT=3306
 DB_DATABASE=sigma_prod
-DB_USERNAME=sigma_prod
+DB_USERNAME=sigma
 DB_PASSWORD=

+REDIS_CLIENT=phpredis
-REDIS_HOST=redis
+REDIS_HOST=redis                # alias de red de shared-redis
 REDIS_PORT=6379
+# Usuario ACL. Sin él, Redis rechaza la conexión (el usuario `default`
+# del servidor compartido solo puede PING).
+REDIS_USERNAME=sigma
+REDIS_PASSWORD=
+# OBLIGATORIO. El ACL del servidor limita este usuario al patrón `sigma:*`.
+# Sin este prefijo, Laravel usa `<app_name>_database_` y TODA operación
+# devuelve NOPERM. Es el fallo más probable de esta migración.
+REDIS_PREFIX=sigma:
+REDIS_DB=1                      # sesiones
+REDIS_CACHE_DB=0                # caché
```

> `REDIS_PREFIX` y `CACHE_PREFIX` son cosas distintas y se **componen**: la clave real que llega a
> Redis es `sigma:` + `CACHE_PREFIX` + clave. Por eso `~sigma:*` cubre también la caché sin tocar
> `CACHE_PREFIX`. Lo mismo aplica a las sesiones, que usan el prefijo de la conexión.

Actualizar el `.env` real del VPS (`/opt/mallas-arica/.env`, `chmod 600`) con el bloque que imprimió
`provision-app.sh`. **No dejar `DB_ROOT_PASSWORD` colgando**: si queda, el próximo que lea el archivo
asumirá que existe un MariaDB propio.

---

## 4. `.github/workflows/deploy.yml` — guard de red

Sin esto, si el stack de infraestructura está caído, `docker compose up` falla con
`network backend-shared declared as external, but could not be found`, el `trap` interpreta un
despliegue roto y hace rollback a la imagen anterior — que fallará por lo mismo. El diagnóstico se
vuelve confuso justo cuando menos conviene.

Insertar en el job `deploy`, **antes** de `PREV_TAG=$(...)`:

```bash
            # --- Guard: dependencias externas de infraestructura ---
            # Fallar aquí, con mensaje claro, en vez de disparar un rollback
            # que no arregla nada.
            for net in proxy backend-shared; do
              docker network inspect "$net" >/dev/null 2>&1 || {
                echo "::error::La red '$net' no existe. Levanta /opt/infra antes de desplegar."
                exit 1
              }
            done

            for svc in shared-mariadb shared-redis; do
              [ "$(docker inspect -f '{{.State.Running}}' "$svc" 2>/dev/null)" = "true" ] || {
                echo "::error::El contenedor '$svc' no está corriendo. Revisa /opt/infra/data."
                exit 1
              }
            done
```

Va antes del `trap rollback ERR`: son precondiciones, no fallos de despliegue.

---

## 5. Migración de datos

Ventana estimada: 3–8 minutos según el tamaño de `sigma_prod`.

```bash
set -euo pipefail

# ---- 5.1 Backup del estado actual, con el stack viejo aún arriba ----
cd /opt/mallas-arica
source .env
docker compose exec -T mariadb \
  mariadb-dump -uroot -p"${DB_ROOT_PASSWORD}" \
    --single-transaction --quick --routines --triggers --events \
    --default-character-set=utf8mb4 \
    sigma_prod | gzip -9 > /root/sigma_prod-premigracion-$(date +%F_%H%M).sql.gz

ls -lh /root/sigma_prod-premigracion-*.sql.gz
gunzip -c /root/sigma_prod-premigracion-*.sql.gz | head -5    # sanity check

# ---- 5.2 Congelar la aplicación (evita escrituras durante el traspaso) ----
docker compose stop app

# ---- 5.3 Restaurar en el MariaDB compartido ----
# La base sigma_prod ya la creó provision-app.sh, vacía.
source /opt/infra/.env
gunzip -c /root/sigma_prod-premigracion-*.sql.gz | \
  docker exec -i shared-mariadb \
    mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" sigma_prod

# ---- 5.4 Verificar el traspaso ----
docker exec -i shared-mariadb mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" -e "
  SELECT TABLE_NAME, TABLE_ROWS
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA='sigma_prod'
  ORDER BY TABLE_NAME;"

# Comparación contra el origen: los conteos deben coincidir.
for t in cotizaciones cotizacion_items tarifas tipos_espacio tipos_malla tramos_altura; do
  o=$(docker compose exec -T mariadb mariadb -uroot -p"${DB_ROOT_PASSWORD}" -N -B \
        -e "SELECT COUNT(*) FROM sigma_prod.$t" 2>/dev/null || echo "n/a")
  n=$(docker exec -i shared-mariadb mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" -N -B \
        -e "SELECT COUNT(*) FROM sigma_prod.$t" 2>/dev/null || echo "n/a")
  printf '%-20s origen=%-8s destino=%-8s %s\n' "$t" "$o" "$n" \
    "$([ "$o" = "$n" ] && echo OK || echo '*** DIFIERE ***')"
done
```

> **Redis no se migra.** Solo contiene caché y sesiones. Consecuencia práctica: los usuarios del panel
> admin tendrán que volver a iniciar sesión una vez. Aceptable.

```bash
# ---- 5.5 Aplicar los cambios ----
cd /opt/mallas-arica/repo && git pull --ff-only
cp deploy/docker-compose.yml /opt/mallas-arica/docker-compose.yml
cd /opt/mallas-arica

# Editar .env con las credenciales de provision-app.sh (§3)
nano .env && chmod 600 .env

# El stack viejo debe bajar COMPLETO para soltar mariadb, redis y la red interna.
docker compose down          # sin -v: los volúmenes sobreviven

docker compose up -d --wait --wait-timeout 120

# ---- 5.6 Verificar la aplicación ----
docker compose logs app --tail 50
docker compose exec -T app php artisan migrate:status
docker compose exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
docker compose exec -T app php artisan tinker --execute="
  Cache::put('probe', 'ok', 60);
  echo Cache::get('probe') === 'ok' ? 'REDIS OK' : 'REDIS FALLA';"

# La caché debe estar bajo el prefijo correcto, y solo ahí.
docker exec -i shared-redis redis-cli --user backup --pass "${BACKUP_REDIS_PASSWORD}" \
  --no-auth-warning -n 0 --scan --pattern 'sigma:*' | head

# ---- 5.7 Extremo a extremo ----
curl -sI https://mallas.tinorte.cl/up | head -3
# Abrir el cotizador en el navegador y completar un paso: Livewire ejerce
# sesión (Redis db1) y caché (Redis db0) a la vez. Es la prueba real.
```

### 5.8 Limpieza — solo tras 24–48 h de funcionamiento normal

```bash
docker volume rm mallas-arica-mariadb-data mallas-arica-redis-data
docker network rm mallas-arica-backend 2>/dev/null || true
docker image prune -f --filter "until=168h"
```

Conservar `/root/sigma_prod-premigracion-*.sql.gz` **un mes como mínimo**. Es la única copia del
estado anterior a la migración.

---

## 6. Rollback

### 6.1 Si el problema aparece antes de borrar los volúmenes (§5.8)

Revertir es directo: los datos viejos siguen intactos.

```bash
cd /opt/mallas-arica
docker compose down
git -C repo checkout <commit-anterior> -- deploy/docker-compose.yml
cp repo/deploy/docker-compose.yml docker-compose.yml
# Restaurar el .env anterior (guardar una copia ANTES de editarlo en §5.5)
cp .env.pre-shared .env
docker compose up -d --wait
```

**Guardar `cp .env .env.pre-shared` antes de tocar el `.env` en §5.5.** Sin esa copia el rollback
exige regenerar credenciales y el `DB_ROOT_PASSWORD` original, que ya no está en ningún sitio.

### 6.2 Si el problema aparece después

Ya no hay contenedores viejos: hay que restaurar desde el dump. Ver §7 de `PLAN-VPS-INFRA.md`
(`restore-app.sh`), o el `.sql.gz` de `/root/`.

---

## 7. Actualizar `CLAUDE.md`

`CLAUDE.md` §7 y §7.1 afirman hoy **exactamente lo contrario** de lo que hará este plan
(«MariaDB y Redis son contenedores propios… decisión descartada»). Si no se corrige, el próximo
agente que lea el proyecto reintroducirá los contenedores dedicados.

Sustituir en §7 el tercer bullet:

```diff
-- 3 contenedores por entorno, todos exclusivos de SIGMA (no compartidos con otros
-  proyectos del VPS): Laravel (Nginx + PHP-FPM vía supervisord) + MariaDB + Redis.
-  Cada uno con volumen propio; MariaDB/Redis solo alcanzables desde la red interna
-  `backend`, sin puertos publicados al host (ver §7.1).
+- **1 contenedor por entorno**: Laravel (Nginx + PHP-FPM vía supervisord). MariaDB y
+  Redis son **instancias compartidas del VPS**, gestionadas por el repositorio
+  `vpsa-infra` (`/opt/infra/data`), alcanzables por la red externa `backend-shared`
+  con los aliases `mariadb` y `redis`. Sin puertos publicados al host.
```

Sustituir el último bullet de §7:

```diff
-- `backup-db.sh` diario con rotación + `restore-db.sh` con dump previo de seguridad.
+- Backups gestionados por infraestructura: `/opt/infra/scripts/backup-app.sh sigma`
+  produce **un `.sql.gz` por base** (`sigma\_%`) y **un `.jsonl.gz` por índice de Redis**
+  (export lógico del prefijo `sigma:`), en `/var/backups/vps-apps/sigma/`. Cron diario a
+  las 03:15 con retención de 14 días y monitor Push en Uptime Kuma.
+  Restauración: `/opt/infra/scripts/restore-app.sh` (hace dump de seguridad previo).
```

Reemplazar los cuatro bullets de decisiones de §7.1 que hablan de contenedores propios:

```diff
-- **MariaDB y Redis son contenedores propios de `deploy/docker-compose.yml`, no instancias
-  compartidas...**
-- **Red `backend`:** ya no es externa...
-- **`DB_ROOT_PASSWORD`** es una variable nueva en `.env`...
+- **MariaDB y Redis son instancias compartidas multi-tenant del VPS**, definidas en
+  `vpsa-infra` (`/opt/infra/data/docker-compose.yml`). SIGMA solo aporta el contenedor
+  `mallas-arica-app`. Decisión tomada para alojar varias aplicaciones en 4 GB de RAM sin
+  duplicar motores de datos.
+- **Aislamiento por inquilino:** base `sigma_prod` con `GRANT` sobre `` `sigma\_%`.* ``
+  (el `\_` escapado es imprescindible: sin él `_` es comodín LIKE) y
+  `MAX_USER_CONNECTIONS 20`. En Redis, usuario ACL `sigma` restringido al patrón
+  `~sigma:*`, con `FLUSHALL` denegado y `FLUSHDB` permitido — este último lo necesita
+  `Illuminate\Cache\RedisStore::flush()`, que ignora el prefijo.
+- **`REDIS_PREFIX=sigma:` es obligatorio en el `.env`.** Sin él, Laravel usa
+  `<app_name>_database_`, ninguna clave coincide con el ACL y la aplicación falla entera
+  con `NOPERM`.
+- **Red `backend`:** externa, `backend-shared`, creada por el stack de infraestructura.
+  El despliegue aborta con mensaje explícito si no existe (guard en `deploy.yml`).
+- **`DB_ROOT_PASSWORD` eliminada** del `.env` de SIGMA: la contraseña de root vive ahora
+  solo en `/opt/infra/.env` y la app nunca la usó.
```

Y en la lista de archivos del repositorio:

```diff
 deploy/docker-compose.yml          # referencia para copiar a /opt/mallas-arica/ en el VPS
-                                    # servicios: app + mariadb + redis (todos propios de SIGMA)
+                                    # servicio único: app (datos en el stack compartido)
```

Añadir al final de §7.1:

```markdown
**Repositorio de infraestructura:** `vpsa-infra` (privado), desplegado en `/opt/infra`. Contiene el
stack de borde (Traefik, Portainer, Uptime Kuma, Dozzle) y el de datos compartidos (MariaDB, Redis),
más los scripts de provisión y backup por aplicación. SIGMA está registrada en `apps/sigma.conf`.
Cualquier cambio de credenciales, red o motor de datos se hace ahí, no aquí.
```

---

## 8. Checklist

**Antes de empezar**
- [ ] `PLAN-VPS-INFRA.md` completado y validado hasta su §9 paso 7
- [ ] Credenciales de `provision-app.sh sigma prod` guardadas
- [ ] Extensión `redis` confirmada en la imagen de producción (§1.1)
- [ ] `config/database.php` con `username` y `prefix` (§1.2)

**Repositorio**
- [ ] `deploy/docker-compose.yml`: 6 ediciones de §2
- [ ] `docker compose config` sin errores, `container_name` aparece 1 vez
- [ ] `deploy/.env.production.example` actualizado
- [ ] Guard de red en `.github/workflows/deploy.yml`
- [ ] `CLAUDE.md` §7 y §7.1 reescritos
- [ ] `./vendor/bin/pint --test` en verde
- [ ] Commit y push (dispara el pipeline: el job `test` debe pasar igual)

**VPS**
- [ ] `cp .env .env.pre-shared` **antes** de editar
- [ ] Dump pre-migración en `/root/`, verificado
- [ ] Restauración en `shared-mariadb` con conteos coincidentes
- [ ] `.env` nuevo, `chmod 600`, sin `DB_ROOT_PASSWORD`
- [ ] `docker compose down` completo antes del `up`
- [ ] `migrate:status` correcto
- [ ] Sondas de DB y Redis OK
- [ ] Claves visibles bajo `sigma:*` y en ningún otro prefijo
- [ ] `https://mallas.tinorte.cl/up` devuelve 200
- [ ] Cotizador completado a mano en el navegador (sesión + caché)
- [ ] `docker stats` muestra un único contenedor de SIGMA
- [ ] `/opt/infra/scripts/backup-app.sh sigma` produce ambos artefactos

**Después de 24–48 h**
- [ ] Volúmenes y red antiguos eliminados
- [ ] Dump pre-migración conservado (mínimo un mes)

---

## 9. Fallos probables y su causa

| Síntoma | Causa | Solución |
|---|---|---|
| `NOPERM ... no permissions to access one of the keys` | Falta `REDIS_PREFIX=sigma:` o no coincide con el ACL | Añadirlo al `.env` y `config:cache` (el entrypoint lo hace al reiniciar) |
| `NOAUTH Authentication required` | Falta `REDIS_USERNAME`/`REDIS_PASSWORD`; el `default` del servidor compartido solo hace PING | Completar el `.env` |
| `WRONGPASS invalid username-password pair` | La contraseña rotó al reejecutar `provision-app.sh` | Volver a ejecutarlo y pegar el bloque nuevo |
| `network backend-shared ... could not be found` | El stack `/opt/infra/data` está caído | `cd /opt/infra/data && docker compose --env-file ../.env up -d --wait` |
| `SQLSTATE[HY000] [1045] Access denied for user 'sigma'` | El `.env` conserva `DB_USERNAME=sigma_prod` | Cambiar a `sigma` |
| `Access denied for user 'sigma' to database 'X'` | La base no cae bajo `sigma\_%` | Renombrarla con el prefijo o ampliar el GRANT |
| `entrypoint: FATAL: MariaDB no respondió` tras 60 s | El stack compartido no está arriba, o `app` no está en `backend-shared` | Revisar el guard de §4; `docker inspect mallas-arica-app -f '{{json .NetworkSettings.Networks}}'` |
| 502 intermitente desde Traefik | Traefik resuelve la IP de `backend-shared` en vez de `proxy` | Verificar que la etiqueta `traefik.docker.network=proxy` sigue presente |
| `cache:clear` lanza `NOPERM ... 'flushdb'` | Alguien añadió `-flushdb` al ACL | Reejecutar `provision-app.sh`; `FLUSHDB` debe estar permitido |
| Sesiones que se pierden solas | Redis desalojando por `maxmemory` | `docker exec shared-redis redis-cli INFO stats \| grep evicted_keys`. Si crece, subir `maxmemory` en `/opt/infra/data/redis/redis.conf` y el límite del contenedor |
| `Too many connections` | Otra app agotó `max_connections` | `MAX_USER_CONNECTIONS 20` acota a cada inquilino; revisar cuántas apps hay contra `max_connections=100` |

---

## 10. Consecuencias que conviene tener presentes

- **El radio de daño creció.** Un OOM de `shared-mariadb` tumba todas las aplicaciones, no solo
  SIGMA. Los 2 GB de swap con `vm.swappiness=10` dan margen, pero conviene un monitor TCP en Uptime
  Kuma sobre `shared-mariadb:3306` y `shared-redis:6379`, no solo sobre `/up` de cada app.
- **El despliegue de SIGMA depende ahora de un repositorio externo.** El guard de §4 lo hace
  explícito en vez de dejarlo implícito.
- **`~600 MB de RAM liberados`** al eliminar los dos contenedores dedicados. Ese es el presupuesto
  real para la segunda y tercera aplicación del VPS.
- **La Etapa 2 no cambia.** Las fotos van a Cloudflare R2, no a MariaDB ni a Redis. El volumen
  `mallas-arica-storage` sigue siendo propio de SIGMA y sigue **sin estar en el backup automático**
  — es la deuda técnica pendiente de antes y este plan no la resuelve.
- **Si SIGMA suma clientes de pago**, se activan dos cosas ya documentadas: migrar a un VPS dedicado
  y subir `innodb_flush_log_at_trx_commit` a 1 en `/opt/infra/data/mariadb/conf.d/99-shared.cnf`.