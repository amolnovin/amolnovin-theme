#!/usr/bin/env bash
# Throw-away WordPress test site with the theme installed from the production zip.
# Needs: php >= 8.4 (composer.lock pins Symfony 8) (+ mysqli, mbstring, xml, zip, gd, intl), MariaDB/MySQL (root via sudo), WP-CLI.
#   ./setup_lab.sh [/path/for/wordpress] [port] [theme-zip]
# Serve it:  WP_ROOT=/path/for/wordpress PHP_CLI_SERVER_WORKERS=6 php -S 0.0.0.0:PORT router.php
# The database and admin passwords are random (printed at the end) — override with DB_PASS / ADMIN_PASS.
set -euo pipefail
ROOT=${1:-/tmp/wp-lab-site}; PORT=${2:-8081}; ZIP=${3:-"$(cd "$(dirname "$0")/.." && pwd)/dist/amolnovin-sage-theme.zip"}
DB=amolwp_$PORT
rnd() { head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 16; }
DB_PASS=${DB_PASS:-$(rnd)}; ADMIN_PASS=${ADMIN_PASS:-$(rnd)}

sudo mariadb -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'amol'@'localhost' IDENTIFIED BY '$DB_PASS'; ALTER USER 'amol'@'localhost' IDENTIFIED BY '$DB_PASS'; GRANT ALL ON $DB.* TO 'amol'@'localhost'; FLUSH PRIVILEGES;"
rm -rf "$ROOT" && wp core download --path="$ROOT" --allow-root
cd "$ROOT"
wp config create --dbname=$DB --dbuser=amol --dbpass="$DB_PASS" --dbhost=127.0.0.1 --dbcharset=utf8mb4 --allow-root --extra-php <<'PHP'
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_ENVIRONMENT_TYPE', 'local');
if (isset($_SERVER['HTTP_HOST'])) { $s = !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'http'; define('WP_HOME', $s . '://' . $_SERVER['HTTP_HOST']); define('WP_SITEURL', WP_HOME); }
PHP
wp core install --url=http://localhost:$PORT --title="آمل نوین" --admin_user=admin --admin_password="$ADMIN_PASS" --admin_email=admin@example.test --skip-email --allow-root
wp language core install fa_IR --activate --allow-root
wp option update timezone_string Asia/Tehran --allow-root
wp theme install "$ZIP" --activate --allow-root
wp amol import --reset --allow-root
wp rewrite structure '/%postname%/' --hard --allow-root
echo
echo "ready.  serve:  WP_ROOT=$ROOT php -S 0.0.0.0:$PORT $(cd "$(dirname "$0")" && pwd)/router.php"
echo "admin login:    admin / $ADMIN_PASS      (export QA_ADMIN_PASS='$ADMIN_PASS' for wp_admin_test.py)"
