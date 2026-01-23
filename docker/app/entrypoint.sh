#!/usr/bin/env sh
set -e

if [ ! -f /var/www/.env ] && [ -f /var/www/.env.example ]; then
  cp /var/www/.env.example /var/www/.env
  chown app:app /var/www/.env
fi

if [ -z "${APP_KEY:-}" ]; then
  su-exec app php artisan key:generate --force
fi

if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
  su-exec app php artisan migrate --force
fi

if [ "${AUTO_SEED:-false}" = "true" ]; then
  su-exec app php artisan db:seed --force
fi

exec su-exec app "$@"
