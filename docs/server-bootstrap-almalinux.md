# AlmaLinux Server Install Guide

This guide is the AlmaLinux / Rocky / RHEL-compatible version of the bootstrap flow.

Target stack:

- AlmaLinux 9+
- `dnf`
- Nginx
- PHP 8.3 via Remi
- MariaDB packages for local database hosting
- Laravel 12 / Livewire 4

## What the scripts already handle

`bootstrap-server.sh` now automatically handles AlmaLinux-specific behavior:

- installs packages with `dnf`
- installs PHP 8.3 via Remi
- uses `nginx` user/group by default
- uses `php-fpm` service and `/run/php-fpm/www.sock`
- writes Nginx config into `/etc/nginx/conf.d`
- can install local database packages and provision DB/user

## Fresh install

Run this from the project root, or point `APP_ROOT` at the final target path.

### Public preset, local IP, local database

```bash
sudo BOOTSTRAP_OS=almalinux \
APP_ROOT=/var/www/hrm \
APP_USER=nginx \
APP_GROUP=nginx \
HRM_DEPLOYMENT_PRESET=public \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_NAME=HRM \
APP_DOMAIN=192.168.4.25 \
APP_URL=http://192.168.4.25 \
INSTALL_MYSQL_SERVER=1 \
SETUP_LOCAL_MYSQL=1 \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='CHANGE_ME' \
MAIL_HOST=127.0.0.1 \
MAIL_PORT=1025 \
MAIL_USERNAME='' \
MAIL_PASSWORD='' \
MAIL_ENCRYPTION=tls \
MAIL_FROM_ADDRESS='noreply@example.com' \
MAIL_FROM_NAME='HRM' \
bash ops/bootstrap-server.sh
```

### Private preset

```bash
sudo BOOTSTRAP_OS=almalinux \
APP_ROOT=/var/www/hrm \
APP_USER=nginx \
APP_GROUP=nginx \
HRM_DEPLOYMENT_PRESET=private \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=192.168.4.25 \
APP_URL=http://192.168.4.25 \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='CHANGE_ME' \
bash ops/bootstrap-server.sh
```

### Military preset

```bash
sudo BOOTSTRAP_OS=almalinux \
APP_ROOT=/var/www/hrm \
APP_USER=nginx \
APP_GROUP=nginx \
HRM_DEPLOYMENT_PRESET=military \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=192.168.4.25 \
APP_URL=http://192.168.4.25 \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='CHANGE_ME' \
bash ops/bootstrap-server.sh
```

## What you must edit before running

- `GIT_REPOSITORY`
- `GIT_REF`
- `APP_ROOT`
- `APP_DOMAIN`
- `APP_URL`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `MAIL_HOST`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

## Useful AlmaLinux notes

- If you open the app by IP only, set:
  - `APP_DOMAIN=192.168.x.x`
  - `APP_URL=http://192.168.x.x`
- If the app should only be reachable on the local network, keep HTTP and use the LAN IP.
- If you do not want local database packages installed, remove:
  - `INSTALL_MYSQL_SERVER=1`
  - `SETUP_LOCAL_MYSQL=1`

## Later updates

Use the same deploy script after first setup:

```bash
sudo BOOTSTRAP_OS=almalinux \
APP_ROOT=/var/www/hrm \
APP_USER=nginx \
APP_GROUP=nginx \
PULL_REF=main \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
RUN_GIT_PULL=1 \
RUN_MIGRATIONS=1 \
RUN_NPM_BUILD=1 \
bash ops/deploy-update.sh
```

## Recommended checks

```bash
sudo systemctl status nginx php-fpm
sudo systemctl status hrm-scheduler.timer
sudo nginx -t
php /var/www/hrm/artisan about
php /var/www/hrm/artisan migrate:status
```
