#!/usr/bin/env sh
set -e

cd /var/www

# Ensure required directories exist and are writable.
mkdir -p storage bootstrap/cache

# In local dev, the bind mount may bring host permissions. Make a best-effort attempt.
# (If this fails due to filesystem restrictions, you can fix on host or adjust UID/GID.)
chmod -R ug+rwX storage bootstrap/cache || true

if [ ! -d "vendor" ]; then
  echo "[entrypoint] vendor/ not found. Run: docker compose exec app composer install"
fi

# Optional: clear caches (safe, keeps container behavior predictable)
php artisan optimize:clear >/dev/null 2>&1 || true

exec "$@"

