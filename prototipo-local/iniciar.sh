#!/bin/sh
set -eu
cd "$(dirname "$0")"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker não foi encontrado. Instale Docker Engine com Docker Compose v2." >&2
  exit 1
fi

if [ ! -f .env ]; then
  if ! command -v openssl >/dev/null 2>&1; then
    echo "OpenSSL é necessário para gerar a chave local APP_KEY." >&2
    exit 1
  fi
  printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32 | tr -d '\n')" > .env
  echo "Chave local criada em prototipo-local/.env (arquivo ignorado pelo Git)."
fi

if ! grep -q '^INERTIA_DEVTOOLS_ENABLED=' .env; then
  printf 'INERTIA_DEVTOOLS_ENABLED=false\n' >> .env
fi

# Docker needs an existing mount target inside the read-only Core bind mount.
if [ ! -f core/.env ]; then
  : > core/.env
fi

docker compose up --build
