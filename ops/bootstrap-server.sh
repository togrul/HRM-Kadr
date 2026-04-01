#!/usr/bin/env bash

set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
TEMPLATE_DIR="${SCRIPT_DIR}/templates"

APP_SLUG="${APP_SLUG:-hrm}"
APP_NAME="${APP_NAME:-HRM}"
APP_ROOT="${APP_ROOT:-$PROJECT_ROOT}"
APP_DOMAIN="${APP_DOMAIN:-_}"
APP_SCHEME="${APP_SCHEME:-http}"
DEFAULT_APP_HOST="${APP_DOMAIN}"
if [[ "${DEFAULT_APP_HOST}" == "_" ]]; then
  DEFAULT_APP_HOST="127.0.0.1"
fi
APP_URL="${APP_URL:-${APP_SCHEME}://${DEFAULT_APP_HOST}}"
APP_ENV="${APP_ENV:-production}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_PORT="${APP_PORT:-80}"
APP_USER="${APP_USER:-www-data}"
APP_GROUP="${APP_GROUP:-www-data}"
HRM_DEPLOYMENT_PRESET="${HRM_DEPLOYMENT_PRESET:-custom}"
APP_TYPE="${APP_TYPE:-default}"
APP_CANDIDATE_MODE="${APP_CANDIDATE_MODE:-auto}"
APP_CANDIDATE_WORKFLOW_PACK="${APP_CANDIDATE_WORKFLOW_PACK:-auto}"
APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS="${APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS:-auto}"
GIT_REPOSITORY="${GIT_REPOSITORY:-}"
GIT_REF="${GIT_REF:-main}"
PHP_VERSION="${PHP_VERSION:-8.3}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php${PHP_VERSION}-fpm}"
PHP_FPM_SOCKET="${PHP_FPM_SOCKET:-/run/php/php${PHP_VERSION}-fpm.sock}"
INSTALL_NODE="${INSTALL_NODE:-1}"
INSTALL_MYSQL_SERVER="${INSTALL_MYSQL_SERVER:-0}"
SETUP_LOCAL_MYSQL="${SETUP_LOCAL_MYSQL:-0}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
ENABLE_QUEUE_WORKER="${ENABLE_QUEUE_WORKER:-0}"
ENABLE_SCHEDULER_TIMER="${ENABLE_SCHEDULER_TIMER:-1}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-1}"
RUN_SEEDERS="${RUN_SEEDERS:-0}"
SKIP_NPM_BUILD="${SKIP_NPM_BUILD:-0}"
RUN_ROUTE_CACHE="${RUN_ROUTE_CACHE:-0}"
RUN_EVENT_CACHE="${RUN_EVENT_CACHE:-1}"
CLIENT_MAX_BODY_SIZE="${CLIENT_MAX_BODY_SIZE:-20m}"
COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR:-/tmp/${APP_SLUG}-composer-cache}"
NPM_CACHE_DIR="${NPM_CACHE_DIR:-/tmp/${APP_SLUG}-npm-cache}"
DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-hrm}"
DB_USERNAME="${DB_USERNAME:-hrm}"
DB_PASSWORD="${DB_PASSWORD:-change-me}"
MAIL_MAILER="${MAIL_MAILER:-smtp}"
MAIL_HOST="${MAIL_HOST:-127.0.0.1}"
MAIL_PORT="${MAIL_PORT:-1025}"
MAIL_USERNAME="${MAIL_USERNAME:-}"
MAIL_PASSWORD="${MAIL_PASSWORD:-}"
MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-noreply@example.com}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-$APP_NAME}"
NGINX_SITE_PATH="/etc/nginx/sites-available/${APP_SLUG}.conf"
NGINX_SITE_LINK="/etc/nginx/sites-enabled/${APP_SLUG}.conf"
QUEUE_SERVICE_NAME="${APP_SLUG}-queue-worker.service"
SCHEDULER_SERVICE_NAME="${APP_SLUG}-scheduler.service"
SCHEDULER_TIMER_NAME="${APP_SLUG}-scheduler.timer"

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

clone_project_if_needed() {
  if [[ -f "${APP_ROOT}/artisan" && -f "${APP_ROOT}/composer.json" ]]; then
    return
  fi

  [[ -n "${GIT_REPOSITORY}" ]] || fail "Project files not found in APP_ROOT=${APP_ROOT}. Set GIT_REPOSITORY to clone automatically."

  log "Cloning project from ${GIT_REPOSITORY}"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "$(dirname "${APP_ROOT}")"

  if [[ ! -d "${APP_ROOT}" || -z "$(ls -A "${APP_ROOT}" 2>/dev/null)" ]]; then
    rm -rf "${APP_ROOT}"
    run_as_app git clone --branch "${GIT_REF}" --depth 1 "${GIT_REPOSITORY}" "${APP_ROOT}"
    return
  fi

  if [[ -d "${APP_ROOT}/.git" ]]; then
    log "Existing git repository found; updating checkout"
    run_as_app git -C "${APP_ROOT}" fetch origin --prune
    run_as_app git -C "${APP_ROOT}" checkout "${GIT_REF}"
    run_as_app git -C "${APP_ROOT}" pull --ff-only origin "${GIT_REF}"
    return
  fi

  fail "APP_ROOT exists but is not a valid project or git repository: ${APP_ROOT}"
}

apply_deployment_preset() {
  local preset
  preset="$(lower "${HRM_DEPLOYMENT_PRESET}")"

  case "${preset}" in
    ""|custom)
      return
      ;;
    private|public|military)
      APP_TYPE="${preset}"
      APP_CANDIDATE_MODE="auto"
      APP_CANDIDATE_WORKFLOW_PACK="${preset}"
      APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS="${preset}"
      ;;
    *)
      fail "Unsupported HRM_DEPLOYMENT_PRESET=${HRM_DEPLOYMENT_PRESET}. Supported: custom, private, public, military"
      ;;
  esac
}

install_base_packages() {
  log "Installing base packages"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y software-properties-common ca-certificates curl unzip git gnupg2 lsb-release apt-transport-https nginx
}

ensure_php_repo() {
  local os_id
  local os_codename
  os_id="$(. /etc/os-release && echo "${ID}")"
  os_codename="$(. /etc/os-release && echo "${VERSION_CODENAME:-}")"

  if dpkg -s "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
    return
  fi

  case "${os_id}" in
    ubuntu)
      add-apt-repository -y ppa:ondrej/php
      apt-get update -y
      ;;
    debian)
      curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg
      echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${os_codename} main" \
        > /etc/apt/sources.list.d/sury-php.list
      apt-get update -y
      ;;
    *)
      fail "Unsupported OS for automatic PHP repo setup: ${os_id}"
      ;;
  esac
}

ensure_php() {
  if ! dpkg -s "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
    log "Installing PHP ${PHP_VERSION} packages"
    ensure_php_repo
  fi

  apt-get install -y \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-sqlite3"
}

ensure_composer() {
  if command_exists composer; then
    return
  fi

  log "Installing Composer"
  curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
}

ensure_node() {
  if [[ "${INSTALL_NODE}" != "1" ]]; then
    return
  fi

  if command_exists node && command_exists npm; then
    return
  fi

  log "Installing Node.js 20"
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
}

ensure_mysql() {
  if [[ "${INSTALL_MYSQL_SERVER}" == "1" ]]; then
    log "Installing MySQL server"
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y mysql-server
    systemctl enable mysql
    systemctl restart mysql
  else
    apt-get install -y default-mysql-client
  fi

  if [[ "${SETUP_LOCAL_MYSQL}" != "1" ]]; then
    return
  fi

  log "Creating local MySQL database and user"
  mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
FLUSH PRIVILEGES;
SQL
}

ensure_app_user_dirs() {
  log "Preparing application directories"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${APP_ROOT}"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${APP_ROOT}/storage"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${APP_ROOT}/bootstrap/cache"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${COMPOSER_CACHE_DIR}"
  install -d -o "${APP_USER}" -g "${APP_GROUP}" "${NPM_CACHE_DIR}"
  chown -R "${APP_USER}:${APP_GROUP}" "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache"
  chmod -R ug+rwx "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache"
}

copy_env_if_missing() {
  if [[ ! -f "${APP_ROOT}/.env" ]]; then
    log "Creating .env from .env.example"
    cp "${APP_ROOT}/.env.example" "${APP_ROOT}/.env"
    chown "${APP_USER}:${APP_GROUP}" "${APP_ROOT}/.env"
  fi
}

set_env_value() {
  local key="$1"
  local value="$2"
  local env_file="${APP_ROOT}/.env"
  local escaped
  escaped="$(printf '%s' "${value}" | sed -e 's/[\\/&]/\\&/g')"

  if grep -Eq "^${key}=" "${env_file}"; then
    sed -i "s/^${key}=.*/${key}=${escaped}/" "${env_file}"
  else
    printf '%s=%s\n' "${key}" "${value}" >> "${env_file}"
  fi
}

configure_env() {
  log "Writing production environment values"
  copy_env_if_missing

  set_env_value "APP_NAME" "${APP_NAME}"
  set_env_value "APP_ENV" "${APP_ENV}"
  set_env_value "APP_DEBUG" "${APP_DEBUG}"
  set_env_value "APP_URL" "${APP_URL}"
  set_env_value "LOG_CHANNEL" "stack"
  set_env_value "LOG_LEVEL" "warning"
  set_env_value "DB_CONNECTION" "${DB_CONNECTION}"
  set_env_value "DB_HOST" "${DB_HOST}"
  set_env_value "DB_PORT" "${DB_PORT}"
  set_env_value "DB_DATABASE" "${DB_DATABASE}"
  set_env_value "DB_USERNAME" "${DB_USERNAME}"
  set_env_value "DB_PASSWORD" "${DB_PASSWORD}"
  set_env_value "CACHE_DRIVER" "file"
  set_env_value "FILESYSTEM_DISK" "local"
  set_env_value "QUEUE_CONNECTION" "${QUEUE_CONNECTION}"
  set_env_value "SESSION_DRIVER" "file"
  set_env_value "MAIL_MAILER" "${MAIL_MAILER}"
  set_env_value "MAIL_HOST" "${MAIL_HOST}"
  set_env_value "MAIL_PORT" "${MAIL_PORT}"
  set_env_value "MAIL_USERNAME" "${MAIL_USERNAME}"
  set_env_value "MAIL_PASSWORD" "${MAIL_PASSWORD}"
  set_env_value "MAIL_ENCRYPTION" "${MAIL_ENCRYPTION}"
  set_env_value "MAIL_FROM_ADDRESS" "\"${MAIL_FROM_ADDRESS}\""
  set_env_value "MAIL_FROM_NAME" "\"${MAIL_FROM_NAME}\""
  set_env_value "APP_TYPE" "${APP_TYPE}"
  set_env_value "APP_CANDIDATE_MODE" "${APP_CANDIDATE_MODE}"
  set_env_value "APP_CANDIDATE_WORKFLOW_PACK" "${APP_CANDIDATE_WORKFLOW_PACK}"
  set_env_value "APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS" "${APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS}"
}

ensure_app_key() {
  if grep -Eq '^APP_KEY=base64:' "${APP_ROOT}/.env"; then
    return
  fi

  log "Generating APP_KEY"
    run_as_app php "${APP_ROOT}/artisan" key:generate --force --no-interaction
}

install_php_dependencies() {
  log "Installing PHP dependencies"
  run_as_app env COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR}" composer install \
    --working-dir="${APP_ROOT}" \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader
}

install_node_dependencies() {
  if [[ "${INSTALL_NODE}" != "1" || "${SKIP_NPM_BUILD}" == "1" || ! -f "${APP_ROOT}/package.json" ]]; then
    return
  fi

  log "Installing Node dependencies and building assets"
  if [[ -f "${APP_ROOT}/package-lock.json" ]]; then
    run_as_app env npm_config_cache="${NPM_CACHE_DIR}" npm --prefix "${APP_ROOT}" ci
  else
    run_as_app env npm_config_cache="${NPM_CACHE_DIR}" npm --prefix "${APP_ROOT}" install
  fi
  run_as_app env npm_config_cache="${NPM_CACHE_DIR}" npm --prefix "${APP_ROOT}" run build
}

run_artisan_bootstrap() {
  log "Running Laravel bootstrap commands"
  run_as_app php "${APP_ROOT}/artisan" optimize:clear
  run_as_app php "${APP_ROOT}/artisan" storage:link || true

  if [[ "${RUN_MIGRATIONS}" == "1" ]]; then
    run_as_app php "${APP_ROOT}/artisan" migrate --force
  fi

  if [[ "${RUN_SEEDERS}" == "1" ]]; then
    run_as_app php "${APP_ROOT}/artisan" db:seed --force
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

render_template() {
  local input="$1"
  local output="$2"
  sed \
    -e "s|__APP_SLUG__|${APP_SLUG}|g" \
    -e "s|__APP_DOMAIN__|${APP_DOMAIN}|g" \
    -e "s|__APP_PORT__|${APP_PORT}|g" \
    -e "s|__APP_ROOT__|${APP_ROOT}|g" \
    -e "s|__PHP_FPM_SOCKET__|${PHP_FPM_SOCKET}|g" \
    -e "s|__CLIENT_MAX_BODY_SIZE__|${CLIENT_MAX_BODY_SIZE}|g" \
    -e "s|__APP_USER__|${APP_USER}|g" \
    -e "s|__APP_GROUP__|${APP_GROUP}|g" \
    < "${input}" > "${output}"
}

configure_nginx() {
  log "Configuring Nginx site"
  render_template \
    "${TEMPLATE_DIR}/nginx-site.conf.tpl" \
    "${NGINX_SITE_PATH}"

  ln -sf "${NGINX_SITE_PATH}" "${NGINX_SITE_LINK}"
  rm -f /etc/nginx/sites-enabled/default
  nginx -t
  systemctl enable nginx
  systemctl restart nginx
}

configure_queue_worker() {
  if [[ "${ENABLE_QUEUE_WORKER}" != "1" || "${QUEUE_CONNECTION}" == "sync" ]]; then
    systemctl disable --now "${QUEUE_SERVICE_NAME}" >/dev/null 2>&1 || true
    rm -f "/etc/systemd/system/${QUEUE_SERVICE_NAME}"
    return
  fi

  log "Configuring queue worker service"
  render_template \
    "${TEMPLATE_DIR}/queue-worker.service.tpl" \
    "/etc/systemd/system/${QUEUE_SERVICE_NAME}"

  systemctl daemon-reload
  systemctl enable "${QUEUE_SERVICE_NAME}"
  systemctl restart "${QUEUE_SERVICE_NAME}"
}

configure_scheduler() {
  if [[ "${ENABLE_SCHEDULER_TIMER}" != "1" ]]; then
    systemctl disable --now "${SCHEDULER_TIMER_NAME}" >/dev/null 2>&1 || true
    rm -f "/etc/systemd/system/${SCHEDULER_TIMER_NAME}" "/etc/systemd/system/${SCHEDULER_SERVICE_NAME}"
    return
  fi

  log "Configuring scheduler timer"
  render_template \
    "${TEMPLATE_DIR}/scheduler.service.tpl" \
    "/etc/systemd/system/${SCHEDULER_SERVICE_NAME}"
  render_template \
    "${TEMPLATE_DIR}/scheduler.timer.tpl" \
    "/etc/systemd/system/${SCHEDULER_TIMER_NAME}"

  systemctl daemon-reload
  systemctl enable "${SCHEDULER_TIMER_NAME}"
  systemctl restart "${SCHEDULER_TIMER_NAME}"
}

print_summary() {
  cat <<EOF

Bootstrap completed.

Project root: ${APP_ROOT}
App URL:      ${APP_URL}
Nginx site:   ${NGINX_SITE_PATH}
PHP-FPM:      ${PHP_FPM_SERVICE}
Queue:        ${QUEUE_CONNECTION} (worker enabled=${ENABLE_QUEUE_WORKER})
Scheduler:    timer enabled=${ENABLE_SCHEDULER_TIMER}
Preset:       ${HRM_DEPLOYMENT_PRESET}
App type:     ${APP_TYPE}
Candidate:    mode=${APP_CANDIDATE_MODE}, workflow=${APP_CANDIDATE_WORKFLOW_PACK}, visible=${APP_CANDIDATE_WORKFLOW_VISIBLE_PACKS}
Git repo:     ${GIT_REPOSITORY:-already present}
Git ref:      ${GIT_REF}

Recommended checks:
  systemctl status nginx ${PHP_FPM_SERVICE}
  nginx -t
  php ${APP_ROOT}/artisan about
EOF
}

main() {
  require_root
  apply_deployment_preset
  install_base_packages
  ensure_php
  ensure_composer
  ensure_node
  ensure_mysql
  clone_project_if_needed
  assert_project_root
  ensure_app_user_dirs
  configure_env
  install_php_dependencies
  install_node_dependencies
  ensure_app_key
  run_artisan_bootstrap
  configure_nginx
  configure_queue_worker
  configure_scheduler
  systemctl enable "${PHP_FPM_SERVICE}"
  systemctl restart "${PHP_FPM_SERVICE}"
  print_summary
}

main "$@"
