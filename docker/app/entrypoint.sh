#!/usr/bin/env sh
set -e

if [ ! -f /var/www/.env ] && [ -f /var/www/.env.example ]; then
  cp /var/www/.env.example /var/www/.env
  chown app:app /var/www/.env
fi

if [ -z "${APP_KEY:-}" ]; then
  if [ -f /var/www/.env ] && grep -q '^APP_KEY=base64:' /var/www/.env; then
    APP_KEY=$(grep '^APP_KEY=' /var/www/.env | cut -d= -f2-)
    export APP_KEY
  else
    su-exec app php artisan key:generate --force
    APP_KEY=$(grep '^APP_KEY=' /var/www/.env | cut -d= -f2-)
    export APP_KEY
  fi
fi

if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
  su-exec app php artisan migrate --force
fi

if [ "${AUTO_SEED:-false}" = "true" ]; then
  su-exec app php artisan db:seed --force
fi

exec su-exec app "$@"
