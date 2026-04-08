#!/usr/bin/env bash

set -Eeuo pipefail

APP_SLUG="${APP_SLUG:-hrm}"
APP_ROOT="${APP_ROOT:-$(pwd)}"
APP_USER="${APP_USER:-}"
APP_GROUP="${APP_GROUP:-}"
BOOTSTRAP_OS="${BOOTSTRAP_OS:-auto}"
PHP_VERSION="${PHP_VERSION:-8.3}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"
PHP_BIN="${PHP_BIN:-}"
COMPOSER_BIN="${COMPOSER_BIN:-}"
NPM_BIN="${NPM_BIN:-}"
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
OS_ID=""
PLATFORM_FAMILY=""

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

lower() {
  printf '%s' "$1" | tr '[:upper:]' '[:lower:]'
}

resolve_bin() {
  local current_value="$1"
  local binary_name="$2"
  local candidate=""

  if [[ -n "${current_value}" ]]; then
    printf '%s' "${current_value}"
    return
  fi

  candidate="$(command -v "${binary_name}" 2>/dev/null || true)"
  if [[ -n "${candidate}" ]]; then
    printf '%s' "${candidate}"
    return
  fi

  for candidate in \
    "/usr/local/bin/${binary_name}" \
    "/usr/bin/${binary_name}" \
    "/bin/${binary_name}" \
    "/opt/homebrew/bin/${binary_name}"
  do
    if [[ -x "${candidate}" ]]; then
      printf '%s' "${candidate}"
      return
    fi
  done
}

detect_platform() {
  [[ -r /etc/os-release ]] || fail "/etc/os-release not found; cannot detect platform"

  OS_ID="$(. /etc/os-release && echo "${ID}")"

  case "$(lower "${BOOTSTRAP_OS}")" in
    ""|auto)
      ;;
    ubuntu|debian)
      OS_ID="$(lower "${BOOTSTRAP_OS}")"
      ;;
    almalinux|alma|rocky|rhel|centos)
      OS_ID="almalinux"
      ;;
    *)
      fail "Unsupported BOOTSTRAP_OS=${BOOTSTRAP_OS}. Supported: auto, ubuntu, debian, almalinux"
      ;;
  esac

  case "${OS_ID}" in
    ubuntu|debian)
      PLATFORM_FAMILY="debian"
      APP_USER="${APP_USER:-www-data}"
      APP_GROUP="${APP_GROUP:-www-data}"
      PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php${PHP_VERSION}-fpm}"
      ;;
    almalinux|rocky|rhel|centos|fedora)
      PLATFORM_FAMILY="redhat"
      APP_USER="${APP_USER:-nginx}"
      APP_GROUP="${APP_GROUP:-nginx}"
      PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php-fpm}"
      ;;
    *)
      fail "Unsupported platform ID=${OS_ID}"
      ;;
  esac
}

resolve_runtime_binaries() {
  PHP_BIN="$(resolve_bin "${PHP_BIN}" php)"
  COMPOSER_BIN="$(resolve_bin "${COMPOSER_BIN}" composer)"

  if [[ -z "${PHP_BIN}" ]]; then
    fail "php binary not found in PATH"
  fi

  if [[ "${RUN_COMPOSER}" == "1" && -z "${COMPOSER_BIN}" ]]; then
    fail "composer binary not found in PATH"
  fi

  if [[ "${RUN_NPM_BUILD}" == "1" && -f "${APP_ROOT}/package.json" ]]; then
    NPM_BIN="$(resolve_bin "${NPM_BIN}" npm)"

    if [[ -z "${NPM_BIN}" ]]; then
      fail "npm binary not found in PATH"
    fi
  fi
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
  run_as_app env COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR}" "${COMPOSER_BIN}" install \
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
    run_as_app env npm_config_cache="${NPM_CACHE_DIR}" "${NPM_BIN}" --prefix "${APP_ROOT}" ci
  else
    run_as_app env npm_config_cache="${NPM_CACHE_DIR}" "${NPM_BIN}" --prefix "${APP_ROOT}" install
  fi
  run_as_app env npm_config_cache="${NPM_CACHE_DIR}" "${NPM_BIN}" --prefix "${APP_ROOT}" run build
}

artisan_refresh() {
  log "Refreshing Laravel caches and runtime state"
  run_as_app "${PHP_BIN}" "${APP_ROOT}/artisan" optimize:clear

  if [[ "${RUN_MIGRATIONS}" == "1" ]]; then
    run_as_app "${PHP_BIN}" "${APP_ROOT}/artisan" migrate --force
  fi

  run_as_app "${PHP_BIN}" "${APP_ROOT}/artisan" config:cache
  run_as_app "${PHP_BIN}" "${APP_ROOT}/artisan" view:cache

  if [[ "${RUN_EVENT_CACHE}" == "1" ]]; then
    run_as_app "${PHP_BIN}" "${APP_ROOT}/artisan" event:cache
  fi

  if [[ "${RUN_ROUTE_CACHE}" == "1" ]]; then
    run_as_app "${PHP_BIN}" "${APP_ROOT}/artisan" route:cache
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
  detect_platform
  resolve_runtime_binaries
  assert_project_root
  git_pull
  composer_install
  npm_build
  artisan_refresh
  restart_services
}

main "$@"
