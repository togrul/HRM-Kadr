#!/usr/bin/env bash

set -Eeuo pipefail

SSL_DOMAINS="${SSL_DOMAINS:-}"
SSL_CONTACT_EMAIL="${SSL_CONTACT_EMAIL:-}"
CERTBOT_STAGING="${CERTBOT_STAGING:-0}"
CERTBOT_EXPAND="${CERTBOT_EXPAND:-0}"
CERTBOT_REDIRECT="${CERTBOT_REDIRECT:-1}"
NGINX_BIN="${NGINX_BIN:-nginx}"

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

install_certbot() {
  log "Installing Certbot"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y certbot python3-certbot-nginx
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
  install_certbot
  run_certbot
}

main "$@"
