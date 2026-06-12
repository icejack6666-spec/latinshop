#!/bin/sh
# ============================================================
# entrypoint.sh — Latin Shop Docker
# Ejecuta migraciones pendientes y arranca Apache
# ============================================================
set -e

echo "────────────────────────────────────────"
echo " Latin Shop — Container Starting"
echo " ENV: ${APP_ENV:-production}"
echo "────────────────────────────────────────"

# ─── Crear directorios de logs si no existen ─────────────────
mkdir -p /var/www/html/logs
chown -R www-data:www-data /var/www/html/logs 2>/dev/null || true

# ─── Crear directorios de storage ────────────────────────────
mkdir -p /var/www/storage/uploads \
         /var/www/storage/logs \
         /var/www/storage/cache \
         /var/www/storage/backups
chown -R www-data:www-data /var/www/storage

# ─── Esperar a que MySQL esté listo ──────────────────────────
if [ -n "${DB_HOST}" ]; then
    echo "⏳ Esperando MySQL en ${DB_HOST}:3306..."
    MAX_TRIES=30
    TRIES=0
    until php -r "
        \$pdo = new PDO(
            'mysql:host=${DB_HOST};dbname=${DB_NAME};charset=utf8mb4',
            '${DB_USER}',
            '${DB_PASS}'
        );
        echo 'OK';
    " 2>/dev/null | grep -q OK; do
        TRIES=$((TRIES+1))
        if [ $TRIES -ge $MAX_TRIES ]; then
            echo "❌ No se pudo conectar a MySQL después de ${MAX_TRIES} intentos."
            exit 1
        fi
        echo "   Intento ${TRIES}/${MAX_TRIES}..."
        sleep 2
    done
    echo "✅ MySQL listo."
fi

# ─── Esperar a que Redis esté listo ──────────────────────────
if [ -n "${REDIS_HOST}" ]; then
    echo "⏳ Esperando Redis en ${REDIS_HOST}:${REDIS_PORT:-6379}..."
    MAX_TRIES=15
    TRIES=0
    until php -r "
        \$r = new Redis();
        \$r->connect('${REDIS_HOST}', ${REDIS_PORT:-6379});
        echo 'OK';
    " 2>/dev/null | grep -q OK; do
        TRIES=$((TRIES+1))
        if [ $TRIES -ge $MAX_TRIES ]; then
            echo "⚠️  Redis no disponible. Continuando sin Redis..."
            break
        fi
        sleep 1
    done
    echo "✅ Redis listo."
fi

# ─── Warm up OPcache en producción ───────────────────────────
if [ "${APP_ENV}" = "production" ]; then
    echo "🔥 Warming OPcache..."
    find /var/www/html -name "*.php" -not -path "*/vendor/*" | \
        xargs -I{} php -l {} > /dev/null 2>&1 || true
    echo "✅ OPcache warmed."
fi

echo "🚀 Iniciando Apache..."
exec "$@"
