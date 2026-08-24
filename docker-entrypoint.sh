#!/bin/sh
set -e
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
exec "$@"
