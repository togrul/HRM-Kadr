#!/usr/bin/env bash

set -Eeuo pipefail

SSL_DOMAINS="${SSL_DOMAINS:-}"
SSL_CONTACT_EMAIL="${SSL_CONTACT_EMAIL:-}"
CERTBOT_STAGING="${CERTBOT_STAGING:-0}"
CERTBOT_EXPAND="${CERTBOT_EXPAND:-0}"
CERTBOT_REDIRECT="${CERTBOT_REDIRECT:-1}"
NGINX_BIN="${NGINX_BIN:-nginx}"
BOOTSTRAP_OS="${BOOTSTRAP_OS:-auto}"
OS_ID=""
PACKAGE_MANAGER=""

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

lower() {
  printf '%s' "$1" | tr '[:upper:]' '[:lower:]'
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
      PACKAGE_MANAGER="apt"
      ;;
    almalinux|rocky|rhel|centos|fedora)
      PACKAGE_MANAGER="dnf"
      ;;
    *)
      fail "Unsupported platform ID=${OS_ID}"
      ;;
  esac
}

install_certbot() {
  log "Installing Certbot"

  case "${PACKAGE_MANAGER}" in
    apt)
      export DEBIAN_FRONTEND=noninteractive
      apt-get update -y
      apt-get install -y certbot python3-certbot-nginx
      ;;
    dnf)
      dnf install -y dnf-plugins-core epel-release
      dnf config-manager --set-enabled crb >/dev/null 2>&1 || true
      dnf install -y certbot python3-certbot-nginx
      ;;
    *)
      fail "Unsupported package manager: ${PACKAGE_MANAGER}"
      ;;
  esac
}

build_domain_args() {
  local domains_csv="$1"
  local IFS=','
  local domains=()
  read -r -a domains <<< "${domains_csv}"

  [[ "${#domains[@]}" -gt 0 ]] || fail "SSL_DOMAINS is empty"

  DOMAIN_ARGS=()
  local domain
  for domain in "${domains[@]}"; do
    domain="$(printf '%s' "${domain}" | xargs)"
    [[ -n "${domain}" ]] || continue
    DOMAIN_ARGS+=("-d" "${domain}")
  done

  [[ "${#DOMAIN_ARGS[@]}" -gt 0 ]] || fail "No valid domains found in SSL_DOMAINS"
}

run_certbot() {
  [[ -n "${SSL_CONTACT_EMAIL}" ]] || fail "SSL_CONTACT_EMAIL is required"
  build_domain_args "${SSL_DOMAINS}"

  "${NGINX_BIN}" -t

  local args=(
    --nginx
    --non-interactive
    --agree-tos
    -m "${SSL_CONTACT_EMAIL}"
  )

  if [[ "${CERTBOT_STAGING}" == "1" ]]; then
    args+=(--staging)
  fi

  if [[ "${CERTBOT_EXPAND}" == "1" ]]; then
    args+=(--expand)
  fi

  if [[ "${CERTBOT_REDIRECT}" == "1" ]]; then
    args+=(--redirect)
  else
    args+=(--no-redirect)
  fi

  log "Requesting TLS certificate"
  certbot "${args[@]}" "${DOMAIN_ARGS[@]}"
}

main() {
  require_root
  detect_platform
  install_certbot
  run_certbot
}

main "$@"
