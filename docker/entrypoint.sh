#!/bin/sh
# Entrypoint API: tunggu DB -> migrasi -> cache -> storage -> jalankan Apache.
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY kosong. Generate: docker compose run --rm api php artisan key:generate --show" >&2
  exit 1
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

echo "Menunggu database ${DB_HOST}:${DB_PORT:-3306} ..."
i=0
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT")?:"3306").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; do
  i=$((i+1))
  if [ "$i" -ge 60 ]; then echo "ERROR: database tidak siap." >&2; exit 1; fi
  sleep 2
done

php artisan package:discover --ansi
php artisan config:clear

if [ "$RUN_MIGRATE_FRESH" = "true" ]; then
  echo "PERINGATAN: RUN_MIGRATE_FRESH=true -> migrate:fresh --seed (SEMUA DATA DIHAPUS)"
  php artisan migrate:fresh --force --seed
else
  php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec docker-php-entrypoint "$@"
