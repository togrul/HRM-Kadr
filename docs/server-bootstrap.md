# Server Bootstrap Guide

This repository includes production helper scripts for:

- Ubuntu / Debian
- AlmaLinux / Rocky / RHEL-compatible hosts

- [ops/bootstrap-server.sh](/Users/togruljalalli/Desktop/projects/HRM/ops/bootstrap-server.sh)
- [ops/bootstrap-certbot.sh](/Users/togruljalalli/Desktop/projects/HRM/ops/bootstrap-certbot.sh)
- [ops/deploy-update.sh](/Users/togruljalalli/Desktop/projects/HRM/ops/deploy-update.sh)

Primary scripts:

- [ops/bootstrap-server.sh](/Users/togruljalalli/Desktop/projects/HRM/ops/bootstrap-server.sh)
- [ops/bootstrap-certbot.sh](/Users/togruljalalli/Desktop/projects/HRM/ops/bootstrap-certbot.sh)
- [ops/deploy-update.sh](/Users/togruljalalli/Desktop/projects/HRM/ops/deploy-update.sh)

AlmaLinux-specific step-by-step guide:

- [server-bootstrap-almalinux.md](/Users/togruljalalli/Desktop/projects/HRM/docs/server-bootstrap-almalinux.md)

It is designed for this stack:

- Laravel 12
- Livewire 4
- PHP 8.3
- MySQL-compatible database
- Nginx

## Script split

Use the scripts in this order:

1. `bootstrap-server.sh`
Fresh server preparation. Installs packages, writes `.env`, prepares Nginx, PHP-FPM, scheduler, optional queue worker, and runs the first deploy bootstrap.

2. `bootstrap-certbot.sh`
Enables HTTPS on top of an already working Nginx site.

3. `deploy-update.sh`
Used for later deployments after the server is already provisioned.

## What `bootstrap-server.sh` does

The script:

- installs missing OS packages
- installs PHP 8.3 and required extensions
- installs Composer
- optionally installs Node.js and builds Vite assets
- optionally installs MySQL server and creates a local database/user
- writes production-safe values into `.env`
- runs `composer install`
- runs `npm ci && npm run build` when enabled
- runs `php artisan migrate --force`
- creates `storage:link`
- caches config/views/events
- writes an Nginx site config for the project
- optionally creates a queue worker `systemd` service
- creates a Laravel scheduler `systemd` timer
- applies an HRM deployment preset when requested

## Important assumption

Run the script from the project root on the target server, or set `APP_ROOT` to the full project path.

Example project root:

```bash
/var/www/hrm
```

## Quick usage

Example for a standard Ubuntu/Debian install:

```bash
sudo APP_ROOT=/var/www/hrm \
HRM_DEPLOYMENT_PRESET=private \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=hrm.example.com \
APP_URL=https://hrm.example.com \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='strong-password' \
bash ops/bootstrap-server.sh
```

Example when the same server also hosts MySQL:

```bash
sudo APP_ROOT=/var/www/hrm \
HRM_DEPLOYMENT_PRESET=public \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=hrm.example.com \
APP_URL=https://hrm.example.com \
INSTALL_MYSQL_SERVER=1 \
SETUP_LOCAL_MYSQL=1 \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='strong-password' \
bash ops/bootstrap-server.sh
```

Example for military deployment:

```bash
sudo APP_ROOT=/var/www/hrm \
HRM_DEPLOYMENT_PRESET=military \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=hrm.example.com \
APP_URL=https://hrm.example.com \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='strong-password' \
bash ops/bootstrap-server.sh
```

Example for AlmaLinux with local LAN IP and `public` preset:

```bash
sudo BOOTSTRAP_OS=almalinux \
APP_ROOT=/var/www/hrm \
HRM_DEPLOYMENT_PRESET=public \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=192.168.4.25 \
APP_URL=http://192.168.4.25 \
INSTALL_MYSQL_SERVER=1 \
SETUP_LOCAL_MYSQL=1 \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm \
DB_USERNAME=hrm \
DB_PASSWORD='strong-password' \
bash ops/bootstrap-server.sh
```

## Useful variables

### App and server

- `APP_SLUG`
- `APP_NAME`
- `APP_ROOT`
- `BOOTSTRAP_OS`
- `HRM_DEPLOYMENT_PRESET`
- `GIT_REPOSITORY`
- `GIT_REF`
- `APP_DOMAIN`
- `APP_SCHEME`
- `APP_URL`
- `APP_ENV`
- `APP_DEBUG`
- `APP_PORT`
- `APP_USER`
- `APP_GROUP`

### PHP

- `PHP_VERSION`
- `PHP_FPM_SERVICE`
- `PHP_FPM_SOCKET`

### Database

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `INSTALL_MYSQL_SERVER=1`
- `SETUP_LOCAL_MYSQL=1`

### Build and deploy behavior

- `INSTALL_NODE=1`
- `SKIP_NPM_BUILD=1`
- `RUN_MIGRATIONS=1`
- `RUN_SEEDERS=0`
- `RUN_ROUTE_CACHE=0`
- `RUN_EVENT_CACHE=1`

### Queue and scheduler

- `QUEUE_CONNECTION=sync`
- `ENABLE_QUEUE_WORKER=0`
- `ENABLE_SCHEDULER_TIMER=1`

### Mail

- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

### HRM-specific deployment flags

- `APP_TYPE`
- `APP_CANDIDATE_MODE`
- `APP_CANDIDATE_WORKFLOW_PACK`
- `APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS`

## Deployment preset logic

`HRM_DEPLOYMENT_PRESET` is the cleanest way to configure environment-specific behavior.

Supported values:

- `private`
- `public`
- `military`
- `custom`

When you set `HRM_DEPLOYMENT_PRESET=private|public|military`, the bootstrap script automatically writes:

- `APP_TYPE=<preset>`
- `APP_CANDIDATE_MODE=auto`
- `APP_CANDIDATE_WORKFLOW_PACK=<preset>`
- `APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS=<preset>`

Use `custom` only if you want to control these variables manually.

## GitHub repository bootstrap

If the project is not yet copied to the server, you can let the bootstrap script clone it.

Use:

- `GIT_REPOSITORY`
- `GIT_REF`

Example:

```bash
sudo APP_ROOT=/var/www/hrm \
HRM_DEPLOYMENT_PRESET=private \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
GIT_REF=main \
APP_DOMAIN=private.example.com \
APP_URL=http://private.example.com \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=hrm_private \
DB_USERNAME=hrm_private \
DB_PASSWORD='CHANGE_ME' \
bash ops/bootstrap-server.sh
```

If the repository is private, the server must already have access:

- SSH deploy key, or
- HTTPS repository URL with token access

## Recommended production defaults

For a simple first deployment:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=sync`
- `ENABLE_QUEUE_WORKER=0`
- `ENABLE_SCHEDULER_TIMER=1`

If you later add a proper async queue backend, enable:

- `QUEUE_CONNECTION=database` or `redis`
- `ENABLE_QUEUE_WORKER=1`

## HTTPS with Certbot

After Nginx is working on HTTP, run:

```bash
sudo SSL_DOMAINS=hrm.example.com,www.hrm.example.com \
SSL_CONTACT_EMAIL=ops@example.com \
bash ops/bootstrap-certbot.sh
```

Useful optional flags:

- `CERTBOT_STAGING=1`
- `CERTBOT_EXPAND=1`
- `CERTBOT_REDIRECT=1`

## Later deployments

After the server is already prepared, use:

```bash
sudo APP_ROOT=/var/www/hrm \
PULL_REF=main \
GIT_REPOSITORY=git@github.com:your-org/HRM.git \
RUN_GIT_PULL=1 \
RUN_MIGRATIONS=1 \
RUN_NPM_BUILD=1 \
bash ops/deploy-update.sh
```

Useful optional flags:

- `RUN_GIT_PULL=0`
- `RUN_NPM_BUILD=0`
- `RUN_MIGRATIONS=0`
- `RUN_ROUTE_CACHE=1`
- `RUN_EVENT_CACHE=1`
- `RESTART_QUEUE_WORKER=1`

## What you need to edit before running

These are the values you must set for almost every new server:

### Fresh install

- `APP_ROOT`
- `HRM_DEPLOYMENT_PRESET`
- `GIT_REPOSITORY`
- `GIT_REF`
- `APP_DOMAIN`
- `APP_URL`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Usually you should also review:

- `APP_NAME`
- `APP_USER`
- `APP_GROUP`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Only when needed:

- `INSTALL_MYSQL_SERVER=1`
- `SETUP_LOCAL_MYSQL=1`
- `ENABLE_QUEUE_WORKER=1`
- `QUEUE_CONNECTION=database` or `redis`
- `SKIP_NPM_BUILD=1`

### SSL bootstrap

- `SSL_DOMAINS`
- `SSL_CONTACT_EMAIL`

### Later deploy/update

- `APP_ROOT`
- `PULL_REF`

Optional depending on your release flow:

- `RUN_GIT_PULL`
- `RUN_NPM_BUILD`
- `RUN_MIGRATIONS`

## Notes

- `bootstrap-server.sh` auto-detects Debian/Ubuntu vs AlmaLinux/RHEL family from `/etc/os-release`. Use `BOOTSTRAP_OS=almalinux` only if you want to force the AlmaLinux path.
- On AlmaLinux/RHEL family:
  - default runtime user/group becomes `nginx`
  - default PHP-FPM service becomes `php-fpm`
  - default socket becomes `/run/php-fpm/www.sock`
  - local DB install uses MariaDB packages via `dnf`, but Laravel still uses `DB_CONNECTION=mysql`
- If you want `QUEUE_CONNECTION=database`, your codebase also needs queue tables and queue strategy aligned with production.
- `bootstrap-server.sh` is intended to be idempotent. Re-running it should update config rather than destroy the server state.
- `deploy-update.sh` should be your normal post-setup deployment path.

## Recommended post-run checks

```bash
systemctl status nginx php8.3-fpm
systemctl status hrm-scheduler.timer
php artisan about
php artisan migrate:status
```
