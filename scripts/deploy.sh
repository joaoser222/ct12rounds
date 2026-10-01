#!/usr/bin/env bash
# Publica o working tree no stack de producao.
#
# Uso:
#   ./scripts/deploy.sh
#
# Sempre usa o par compose.yaml + compose.production.yaml. Usar apenas o
# compose.yaml sobe o container sem env_file, sem SSR e sem o Caddy na porta 80.

set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE=(docker compose -f compose.yaml -f compose.production.yaml)

echo "==> Building app image"
"${COMPOSE[@]}" build app

echo "==> Starting app"
"${COMPOSE[@]}" up -d app

echo "==> Waiting for app to become healthy"
for attempt in $(seq 1 60); do
    status=$(docker inspect -f '{{.State.Health.Status}}' ct12rounds_app 2>/dev/null || echo starting)

    if [ "$status" = healthy ]; then
        break
    fi

    if [ "$attempt" -eq 60 ]; then
        echo "app did not become healthy (last status: $status)" >&2
        docker logs --tail 50 ct12rounds_app >&2
        exit 1
    fi

    sleep 2
done

echo "==> Done. Landing: http://localhost/"
