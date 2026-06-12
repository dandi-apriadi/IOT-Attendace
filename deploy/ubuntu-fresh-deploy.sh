#!/usr/bin/env bash
set -euo pipefail

APP_DOMAIN="${APP_DOMAIN:-elektropolimdo.com}"
APP_DIR="${APP_DIR:-/var/www/iot-attendance}"
REPO_URL="${REPO_URL:-https://github.com/dandi-apriadi/IOT-Attendace.git}"
DB_NAME="${DB_NAME:-absensi_iot}"
DB_USER="${DB_USER:-absensi_iot}"
DB_PASS="${DB_PASS:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@gmail.com}"
PHP_BIN="${PHP_BIN:-php}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run as root." >&2
  exit 1
fi

if [[ -z "$DB_PASS" ]]; then
  DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
fi

export APP_DOMAIN DB_NAME DB_USER DB_PASS
export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y \
  ca-certificates curl unzip git nginx mariadb-server supervisor cron certbot python3-certbot-nginx \
  php-cli php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-gd php-intl php-bcmath php-soap php-sqlite3

systemctl enable --now nginx mariadb cron

if ! command -v composer >/dev/null 2>&1; then
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

mkdir -p "$(dirname "$APP_DIR")"
if [[ ! -d "$APP_DIR/.git" ]]; then
  git clone "$REPO_URL" "$APP_DIR"
else
  git -C "$APP_DIR" fetch origin
fi

git -C "$APP_DIR" checkout main
git -C "$APP_DIR" pull --ff-only origin main

cd "$APP_DIR"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

php -r '
$path = ".env";
$env = file_exists($path) ? file_get_contents($path) : "";
$pairs = [
  "APP_ENV" => "production",
  "APP_DEBUG" => "false",
  "APP_URL" => "https://" . getenv("APP_DOMAIN"),
  "APP_ROLE" => "server",
  "LOG_CHANNEL" => "stack",
  "LOG_LEVEL" => "warning",
  "DB_CONNECTION" => "mysql",
  "DB_HOST" => "127.0.0.1",
  "DB_PORT" => "3306",
  "DB_DATABASE" => getenv("DB_NAME"),
  "DB_USERNAME" => getenv("DB_USER"),
  "DB_PASSWORD" => getenv("DB_PASS"),
  "SESSION_DRIVER" => "database",
  "QUEUE_CONNECTION" => "database",
  "CACHE_STORE" => "database",
];
foreach ($pairs as $key => $value) {
  $line = $key . "=" . $value;
  if (preg_match("/^" . preg_quote($key, "/") . "=.*/m", $env)) {
    $env = preg_replace("/^" . preg_quote($key, "/") . "=.*/m", $line, $env);
  } else {
    $env .= PHP_EOL . $line;
  }
}
file_put_contents($path, $env);
'

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan db:seed --class=TeacherUserSeeder --force
php artisan storage:link || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;

PHP_FPM_SOCK="$(find /run/php -maxdepth 1 -name 'php*-fpm.sock' | sort -V | tail -n 1)"
if [[ -z "$PHP_FPM_SOCK" ]]; then
  echo "PHP-FPM socket not found." >&2
  exit 1
fi

cat >/etc/nginx/sites-available/iot-attendance <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${APP_DOMAIN} www.${APP_DOMAIN};
    root ${APP_DIR}/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/iot-attendance /etc/nginx/sites-enabled/iot-attendance
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

cat >/etc/supervisor/conf.d/iot-attendance-worker.conf <<SUPERVISOR
[program:iot-attendance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl restart iot-attendance-worker:* || true

cat >/etc/cron.d/iot-attendance-scheduler <<CRON
* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON

if [[ "${ENABLE_CERTBOT:-1}" == "1" ]]; then
  certbot --nginx -d "$APP_DOMAIN" -d "www.$APP_DOMAIN" --non-interactive --agree-tos -m "$ADMIN_EMAIL" --redirect || true
fi

php artisan about || true

cat <<INFO

Deploy selesai.
APP_DIR=${APP_DIR}
APP_DOMAIN=${APP_DOMAIN}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

Login awal dari seeder:
email: admin@gmail.com
password: 123

Segera ganti password admin setelah login.
INFO
