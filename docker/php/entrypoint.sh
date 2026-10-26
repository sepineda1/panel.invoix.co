#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /var/lib/invoix/tenants \
    /var/lib/invoix/caddy

if [ ! -f /var/lib/invoix/caddy/tenants.caddy ]; then
    echo "# Generado por el panel." > /var/lib/invoix/caddy/tenants.caddy
fi

if [ ! -f .env ]; then
    touch .env
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "Esperando a MySQL..."
i=0
while [ "$i" -lt 60 ]; do
    if php -r 'try { new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE")), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; then
        break
    fi
    i=$((i + 1))
    sleep 2
done

if [ "$i" -ge 60 ]; then
    echo "MySQL no respondió a tiempo." >&2
    exit 1
fi

exec 9>/tmp/panel-migrate.lock
flock 9
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
flock -u 9

if [ "${1:-web}" = "web" ]; then
    php-fpm -D
    exec nginx -g 'daemon off;'
fi

exec "$@"
