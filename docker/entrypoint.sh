#!/bin/sh
set -e

# If setup is not skipped, perform automated initialization tasks
if [ "$SKIP_ENTRYPOINT_SETUP" != "true" ]; then
    echo "[entrypoint] Ensuring storage and cache directories exist..."
    mkdir -p /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache 2>/dev/null || true

    echo "[entrypoint] Verifying storage symlink..."
    php artisan storage:link --force || true

    # Wait for database if DB_HOST is specified
    if [ -n "$DB_HOST" ]; then
        echo "[entrypoint] Checking database connectivity at $DB_HOST:${DB_PORT:-3306}..."
        attempts=0
        max_attempts=30
        until php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT') ?: 3306), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (\Throwable \$e) { exit(1); }" 2>/dev/null; do
            attempts=$((attempts + 1))
            if [ "$attempts" -ge "$max_attempts" ]; then
                echo "[entrypoint] Database connection timed out after $max_attempts attempts."
                exit 1
            fi
            sleep 2
        done
        echo "[entrypoint] Database connected successfully."
    fi

    echo "[entrypoint] Running database migrations..."
    php artisan migrate --force

    # Synthetic demo seeding only via explicit flag
    if [ "$SEED_DEMO_DATA" = "true" ] || [ "$SEED_DEMO_DATA" = "1" ]; then
        echo "[entrypoint] Explicit SEED_DEMO_DATA flag enabled. Seeding synthetic data..."
        php artisan db:seed --force
    fi

    # Optimize and warm caches in production
    if [ "$APP_ENV" = "production" ]; then
        echo "[entrypoint] Production mode detected. Warming application caches..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
    fi
fi

echo "[entrypoint] Starting application process: $@"
exec "$@"

