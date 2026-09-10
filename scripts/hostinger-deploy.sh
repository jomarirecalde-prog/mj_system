#!/usr/bin/env bash
set -euo pipefail

APP="${APP_DIR:-/home/u899628465/juninventory}"
DOMAIN_PUBLIC="${DOMAIN_PUBLIC:-/home/u899628465/domains/juninventory.shop/public_html}"

cd "$APP"

if [[ ! -f .env ]]; then
  echo "Missing $APP/.env — production environment was not created on the server." >&2
  exit 1
fi

mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  storage/app/public \
  bootstrap/cache

chmod -R ug+rwx storage bootstrap/cache

# Hostinger disables PHP symlink(); create the storage link from the shell.
rm -rf public/storage
ln -sfn "$APP/storage/app/public" "$APP/public/storage"

if [[ -e "$DOMAIN_PUBLIC" && ! -L "$DOMAIN_PUBLIC" ]]; then
  mv "$DOMAIN_PUBLIC" "${DOMAIN_PUBLIC}.bak.$(date +%s)"
fi
ln -sfn "$APP/public" "$DOMAIN_PUBLIC"

php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Hostinger deploy complete."
