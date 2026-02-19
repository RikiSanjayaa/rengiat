#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-$HOME/rengiat}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DOCKER_COMPOSE_CMD="${DOCKER_COMPOSE_CMD:-docker compose}"
FRONTEND_CHANGED="${FRONTEND_CHANGED:-false}"
BACKEND_CHANGED="${BACKEND_CHANGED:-false}"
FORCE_REBUILD="${FORCE_REBUILD:-false}"

log() {
    printf '[deploy-homeserver] %s\n' "$*"
}

to_bool() {
    case "${1,,}" in
    1 | true | yes | y | on)
        echo "true"
        ;;
    *)
        echo "false"
        ;;
    esac
}

compose() {
    # shellcheck disable=SC2086
    ${DOCKER_COMPOSE_CMD} "$@"
}

FRONTEND_CHANGED="$(to_bool "${FRONTEND_CHANGED}")"
BACKEND_CHANGED="$(to_bool "${BACKEND_CHANGED}")"
FORCE_REBUILD="$(to_bool "${FORCE_REBUILD}")"

if [[ ! -d "${APP_DIR}" ]]; then
    log "APP_DIR does not exist: ${APP_DIR}"
    exit 1
fi

cd "${APP_DIR}"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    log "APP_DIR is not a git repository: ${APP_DIR}"
    exit 1
fi

log "Deploying branch=${DEPLOY_BRANCH} frontend_changed=${FRONTEND_CHANGED} backend_changed=${BACKEND_CHANGED} force_rebuild=${FORCE_REBUILD}"

git fetch --prune origin "${DEPLOY_BRANCH}"
git checkout "${DEPLOY_BRANCH}"
git pull --ff-only origin "${DEPLOY_BRANCH}"

build_frontend="false"
build_backend="false"
run_migrations="false"

if [[ "${FORCE_REBUILD}" == "true" ]]; then
    build_frontend="true"
    build_backend="true"
    run_migrations="true"
else
    if [[ "${FRONTEND_CHANGED}" == "true" ]]; then
        build_frontend="true"
        build_backend="true"
    fi

    if [[ "${BACKEND_CHANGED}" == "true" ]]; then
        build_backend="true"
        run_migrations="true"
    fi
fi

if [[ "${build_backend}" == "true" ]]; then
    if [[ "${build_frontend}" == "true" ]]; then
        log "Rebuilding app and web images (frontend touched)."
        compose build --pull app web
    else
        log "Rebuilding app image (backend only)."
        compose build --pull app
    fi
else
    log "No frontend/backend changes detected; skipping image build."
fi

log "Starting containers."
compose up -d app web

if [[ "${run_migrations}" == "true" ]]; then
    log "Running migrations and cache optimize."
    compose exec -T app php artisan migrate --force
    compose exec -T app php artisan optimize
else
    log "Skipping migrations."
fi

log "Container status:"
compose ps
