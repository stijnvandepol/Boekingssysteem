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

wait_for_db() {
  echo "Waiting for database connection..."
  tries=30
  while [ $tries -gt 0 ]; do
    if php -r "try { new PDO('mysql:host='.(getenv('DB_HOST') ?: 'db').';port='.(getenv('DB_PORT') ?: '3306').';dbname='.(getenv('DB_DATABASE') ?: 'rens'), getenv('DB_USERNAME') ?: 'rens_app', getenv('DB_PASSWORD') ?: '', [PDO::ATTR_TIMEOUT => 2]); exit(0); } catch (Throwable \$e) { exit(1); }"; then
      echo "Database connection ok."
      return 0
    fi
    tries=$((tries-1))
    sleep 2
  done

  echo "Database connection failed after retries."
  return 1
}

if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
  wait_for_db
  su-exec app php artisan migrate --force
fi

if [ "${AUTO_SEED:-false}" = "true" ]; then
  wait_for_db
  su-exec app php artisan db:seed --force
fi

exec su-exec app "$@"
