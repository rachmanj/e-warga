#!/bin/bash
set -e

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

wait_for_mysql() {
    local attempt=0
    local max_attempts=60
    until php -r "
        try {
            new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                [PDO::ATTR_TIMEOUT => 2]
            );
            exit(0);
        } catch (Throwable \$e) {
            exit(1);
        }
    "; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge "$max_attempts" ]; then
            echo "MySQL tidak siap setelah ${max_attempts} detik." >&2
            exit 1
        fi
        sleep 1
    done
}

wait_for_mysql

php artisan migrate --force

if [ "${APP_ENV:-}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
fi

if [ ! -e public/storage ]; then
    php artisan storage:link
fi

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
