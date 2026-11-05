#!/bin/sh
set -e

cd /app

if [ ! -f package.json ]; then
    if [ "$1" = "quasar" ]; then
        echo "В ./mobile ещё нет Quasar-проекта."
        echo "Создайте его:"
        echo "  docker compose run --rm quasar npm create quasar@latest"
        exec sleep infinity
    fi

    exec "$@"
fi

if [ ! -x node_modules/.bin/quasar ]; then
    echo "Устанавливаю зависимости Quasar..."

    if [ -f pnpm-lock.yaml ]; then
        if ! command -v pnpm >/dev/null 2>&1; then
            npm install -g pnpm
        fi
        pnpm install
    elif [ -f yarn.lock ]; then
        if ! command -v yarn >/dev/null 2>&1; then
            npm install -g yarn
        fi
        yarn install
    else
        npm install
    fi
fi

exec "$@"
