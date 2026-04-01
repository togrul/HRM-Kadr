#!/usr/bin/env bash

set -Eeuo pipefail

APP_SLUG="${APP_SLUG:-hrm}"
APP_ROOT="${APP_ROOT:-$(pwd)}"
APP_USER="${APP_USER:-www-data}"
APP_GROUP="${APP_GROUP:-www-data}"
PHP_VERSION="${PHP_VERSION:-8.3}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php${PHP_VERSION}-fpm}"
QUEUE_SERVICE_NAME="${QUEUE_SERVICE_NAME:-${APP_SLUG}-queue-worker.service}"
PULL_REMOTE="${PULL_REMOTE:-origin}"
PULL_REF="${PULL_REF:-main}"
GIT_REPOSITORY="${GIT_REPOSITORY:-}"
RUN_GIT_PULL="${RUN_GIT_PULL:-1}"
RUN_COMPOSER="${RUN_COMPOSER:-1}"
RUN_NPM_BUILD="${RUN_NPM_BUILD:-1}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-1}"
RUN_ROUTE_CACHE="${RUN_ROUTE_CACHE:-0}"
RUN_EVENT_CACHE="${RUN_EVENT_CACHE:-1}"
RESTART_QUEUE_WORKER="${RESTART_QUEUE_WORKER:-1}"
RESTART_PHP_FPM="${RESTART_PHP_FPM:-1}"
COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR:-/tmp/${APP_SLUG}-composer-cache}"
NPM_CACHE_DIR="${NPM_CACHE_DIR:-/tmp/${APP_SLUG}-npm-cache}"

require_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    echo "This script must run as root or via sudo." >&2
    exit 1
  fi
}

log() {
  printf '\n[%s] %s\n' "$(date '+%F %T')" "$*"
}

fail() {
  printf '\n[ERROR] %s\n' "$*" >&2
  exit 1
}

command_exists() {
  command -v "$1" >/dev/null 2>&1
}

run_as_app() {
  if command_exists sudo; then
    sudo -u "${APP_USER}" "$@"
  else
    runuser -u "${APP_USER}" -- "$@"
  fi
}

assert_project_root() {
  [[ -f "${APP_ROOT}/artisan" ]] || fail "artisan file not found in APP_ROOT=${APP_ROOT}"
  [[ -f "${APP_ROOT}/composer.json" ]] || fail "composer.json not found in APP_ROOT=${APP_ROOT}"
}

git_pull() {
  if [[ "${RUN_GIT_PULL}" != "1" ]]; then
    return
  fi

  log "Updating git working tree"
  if [[ -n "${GIT_REPOSITORY}" ]]; then
    run_as_app git -C "${APP_ROOT}" remote set-url "${PULL_REMOTE}" "${GIT_REPOSITORY}"
  fi
  run_as_app git -C "${APP_ROOT}" fetch "${PULL_REMOTE}" --prune
  run_as_app git -C "${APP_ROOT}" checkout "${PULL_REF}"
  run_as_app git -C "${APP_ROOT}" pull --ff-only "${PULL_REMOTE}" "${PULL_REF}"
}

composer_install() {
  if [[ "${RUN_COMPOSER}" != "1" ]]; then
    return
  fi

  log "Installing Composer dependencies"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${COMPOSER_CACHE_DIR}"
  run_as_app env COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR}" composer install \
    --working-dir="${APP_ROOT}" \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader
}

npm_build() {
  if [[ "${RUN_NPM_BUILD}" != "1" || ! -f "${APP_ROOT}/package.json" ]]; then
    return
  fi

  log "Building frontend assets"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${NPM_CACHE_DIR}"
  if [[ -f "${APP_ROOT}/package-lock.json" ]]; then
    run_as_app env npm_config_cache="${NPM_CACHE_DIR}" npm --prefix "${APP_ROOT}" ci
  else
    run_as_app env npm_config_cache="${NPM_CACHE_DIR}" npm --prefix "${APP_ROOT}" install
  fi
  run_as_app env npm_config_cache="${NPM_CACHE_DIR}" npm --prefix "${APP_ROOT}" run build
}

artisan_refresh() {
  log "Refreshing Laravel caches and runtime state"
  run_as_app php "${APP_ROOT}/artisan" optimize:clear

  if [[ "${RUN_MIGRATIONS}" == "1" ]]; then
    run_as_app php "${APP_ROOT}/artisan" migrate --force
  fi

  run_as_app php "${APP_ROOT}/artisan" config:cache
  run_as_app php "${APP_ROOT}/artisan" view:cache

  if [[ "${RUN_EVENT_CACHE}" == "1" ]]; then
    run_as_app php "${APP_ROOT}/artisan" event:cache
  fi

  if [[ "${RUN_ROUTE_CACHE}" == "1" ]]; then
    run_as_app php "${APP_ROOT}/artisan" route:cache
  fi
}

restart_services() {
  if [[ "${RESTART_PHP_FPM}" == "1" ]]; then
    log "Restarting PHP-FPM"
    systemctl restart "${PHP_FPM_SERVICE}"
  fi

  if [[ "${RESTART_QUEUE_WORKER}" == "1" ]]; then
    log "Restarting queue worker if present"
    systemctl restart "${QUEUE_SERVICE_NAME}" >/dev/null 2>&1 || true
  fi
}

main() {
  require_root
  assert_project_root
  git_pull
  composer_install
  npm_build
  artisan_refresh
  restart_services
}

main "$@"
