#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-$HOME/rengiat}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DOCKER_COMPOSE_CMD="${DOCKER_COMPOSE_CMD:-docker compose}"

log() {
    printf '[deploy-homeserver] %s\n' "$*"
}

compose() {
    # shellcheck disable=SC2086
    ${DOCKER_COMPOSE_CMD} "$@"
}

if [[ ! -d "${APP_DIR}" ]]; then
    log "APP_DIR does not exist: ${APP_DIR}"
    exit 1
fi

cd "${APP_DIR}"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    log "APP_DIR is not a git repository: ${APP_DIR}"
    exit 1
fi

log "Deploying branch=${DEPLOY_BRANCH}"

git fetch --prune origin "${DEPLOY_BRANCH}"
git checkout "${DEPLOY_BRANCH}"
git pull --ff-only origin "${DEPLOY_BRANCH}"

log "Rebuilding app and web images."
compose build --pull app web

log "Starting containers."
compose up -d --remove-orphans app web

log "Running post-deploy Artisan commands."
compose exec -T app php artisan optimize:clear
compose exec -T app php artisan migrate --force --no-interaction
compose exec -T app php artisan optimize

log "Container status:"
compose ps
