#!/bin/sh
set -e

APP_UID="${APP_UID:-10001}"
APP_GID="${APP_GID:-10001}"

init_dirs() {
  mkdir -p \
    /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/bootstrap/cache \
    /config/caddy \
    /data/caddy/locks \
    /data/caddy \
    /data \
    /config
}

if [ "$(id -u)" = "0" ]; then
  init_dirs
  chown -R "${APP_UID}:${APP_GID}" /app/storage /app/bootstrap/cache /data /config /tmp
  exec setpriv --reuid="${APP_UID}" --regid="${APP_GID}" --clear-groups -- "$0" "$@"
fi

init_dirs
exec "$@"
