#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${PROD_UP_ENV_FILE:-$ROOT_DIR/deploy/prod-up.env}"

if [[ -f "$ENV_FILE" ]]; then
    # shellcheck disable=SC1090
    source "$ENV_FILE"
fi

FRONT_DIR="${FRONT_DIR:-$ROOT_DIR}"
FRONT_NODE_INSTALL_CMD="${FRONT_NODE_INSTALL_CMD:-npm ci}"
FRONT_BUILD_CMD="${FRONT_BUILD_CMD:-npx gulp pack_web}"
FRONT_BUILD_OUTPUT="${FRONT_BUILD_OUTPUT:-$FRONT_DIR/build/web}"
FRONT_PUBLISH_DIR="${FRONT_PUBLISH_DIR:-}"

BACK_DIR="${BACK_DIR:-$ROOT_DIR/cub}"
BACK_ENV_FILE="${BACK_ENV_FILE:-$BACK_DIR/.env}"
BACK_PHP_BIN="${BACK_PHP_BIN:-php}"
BACK_COMPOSER_BIN="${BACK_COMPOSER_BIN:-composer}"
BACK_COMPOSER_INSTALL_CMD="${BACK_COMPOSER_INSTALL_CMD:-composer install --no-dev --optimize-autoloader}"
BACK_NODE_INSTALL_CMD="${BACK_NODE_INSTALL_CMD:-npm ci}"
BACK_BUILD_CMD="${BACK_BUILD_CMD:-npm run build}"
BACK_RUN_MIGRATIONS="${BACK_RUN_MIGRATIONS:-true}"
BACK_RUN_CACHE="${BACK_RUN_CACHE:-true}"
BACK_RUN_STORAGE_LINK="${BACK_RUN_STORAGE_LINK:-true}"

DEPLOY_USER="${DEPLOY_USER:-www-data}"
DEPLOY_GROUP="${DEPLOY_GROUP:-www-data}"
SET_PERMISSIONS="${SET_PERMISSIONS:-false}"

INSTALL_SOCKET_SERVICE="${INSTALL_SOCKET_SERVICE:-false}"
SOCKET_SERVICE_NAME="${SOCKET_SERVICE_NAME:-timeline-socket}"
SOCKET_SERVICE_USER="${SOCKET_SERVICE_USER:-$DEPLOY_USER}"
SOCKET_SERVICE_GROUP="${SOCKET_SERVICE_GROUP:-$DEPLOY_GROUP}"
SOCKET_HOST="${SOCKET_HOST:-127.0.0.1}"
SOCKET_PORT="${SOCKET_PORT:-9001}"
SUPERVISOR_CONF_FILE="${SUPERVISOR_CONF_FILE:-$ROOT_DIR/supervisor.conf}"
SUPERVISORCTL_BIN="${SUPERVISORCTL_BIN:-supervisorctl}"
SUPERVISOR_PROGRAM_NAME="${SUPERVISOR_PROGRAM_NAME:-timeline-socket}"
SUDO_BIN="${SUDO_BIN:-sudo}"

log() {
    printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

die() {
    printf '\n[ERROR] %s\n' "$*" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || die "Не найдено: $1"
}

run_in_dir() {
    local dir="$1"
    local cmd="$2"

    log "$cmd"
    (
        cd "$dir"
        bash -lc "$cmd"
    )
}

truthy() {
    case "${1,,}" in
        1|true|yes|y|on) return 0 ;;
        *) return 1 ;;
    esac
}

read_env_value() {
    local key="$1"
    local file="$2"

    [[ -f "$file" ]] || return 1

    local line
    line="$(grep -E "^${key}=" "$file" | tail -n 1 || true)"
    [[ -n "$line" ]] || return 1

    line="${line#*=}"
    line="${line%\"}"
    line="${line#\"}"
    line="${line%\'}"
    line="${line#\'}"
    printf '%s' "$line"
}

ensure_backend_env() {
    [[ -f "$BACK_ENV_FILE" ]] || die "Не найден backend env: $BACK_ENV_FILE"

    local app_key
    app_key="$(read_env_value APP_KEY "$BACK_ENV_FILE" || true)"

    if [[ -z "$app_key" ]]; then
        log "APP_KEY пустой, генерирую ключ Laravel"
        run_in_dir "$BACK_DIR" "$BACK_PHP_BIN artisan key:generate --force"
    fi
}

ensure_sqlite_file() {
    local db_connection
    local db_database

    db_connection="$(read_env_value DB_CONNECTION "$BACK_ENV_FILE" || true)"
    db_database="$(read_env_value DB_DATABASE "$BACK_ENV_FILE" || true)"

    if [[ "$db_connection" != "sqlite" || -z "$db_database" ]]; then
        return 0
    fi

    if [[ "$db_database" != /* ]]; then
        db_database="$BACK_DIR/$db_database"
    fi

    mkdir -p "$(dirname "$db_database")"

    if [[ ! -f "$db_database" ]]; then
        log "Создаю SQLite файл: $db_database"
        touch "$db_database"
    fi
}

publish_front() {
    [[ -n "$FRONT_PUBLISH_DIR" ]] || return 0
    require_cmd rsync

    if [[ "$FRONT_PUBLISH_DIR" == "$ROOT_DIR" || "$FRONT_PUBLISH_DIR" == "$FRONT_DIR" ]]; then
        die "FRONT_PUBLISH_DIR не должен совпадать с корнем репозитория. Направь сайт либо на build/web, либо на отдельную publish-папку."
    fi

    mkdir -p "$FRONT_PUBLISH_DIR"
    log "Публикую фронт в $FRONT_PUBLISH_DIR"
    rsync -av --delete "$FRONT_BUILD_OUTPUT"/ "$FRONT_PUBLISH_DIR"/
}

set_permissions() {
    truthy "$SET_PERMISSIONS" || return 0

    local targets=(
        "$BACK_DIR/storage"
        "$BACK_DIR/bootstrap/cache"
    )

    if [[ -n "$FRONT_PUBLISH_DIR" ]]; then
        targets+=("$FRONT_PUBLISH_DIR")
    fi

    log "Выставляю права для ${DEPLOY_USER}:${DEPLOY_GROUP}"
    "$SUDO_BIN" chown -R "${DEPLOY_USER}:${DEPLOY_GROUP}" "$BACK_DIR" "${targets[@]}"
    "$SUDO_BIN" chmod -R ug+rwx "$BACK_DIR/storage" "$BACK_DIR/bootstrap/cache"
}

install_socket_service() {
    truthy "$INSTALL_SOCKET_SERVICE" || return 0

    local service_path="/etc/systemd/system/${SOCKET_SERVICE_NAME}.service"
    local unit

    unit="[Unit]
Description=Lampa timeline WebSocket server
After=network.target php8.3-fpm.service

[Service]
Type=simple
User=${SOCKET_SERVICE_USER}
Group=${SOCKET_SERVICE_GROUP}
WorkingDirectory=${BACK_DIR}
ExecStart=$(command -v "$BACK_PHP_BIN") artisan timeline:socket-serve --host=${SOCKET_HOST} --port=${SOCKET_PORT}
Restart=always
RestartSec=3
KillSignal=SIGTERM
TimeoutStopSec=10
Environment=APP_ENV=production
Environment=LOG_CHANNEL=stack

[Install]
WantedBy=multi-user.target
"

    log "Устанавливаю systemd сервис ${SOCKET_SERVICE_NAME}"
    printf '%s' "$unit" | "$SUDO_BIN" tee "$service_path" >/dev/null
    "$SUDO_BIN" systemctl daemon-reload
    "$SUDO_BIN" systemctl enable --now "${SOCKET_SERVICE_NAME}.service"
    "$SUDO_BIN" systemctl restart "${SOCKET_SERVICE_NAME}.service"
}

restart_socket_via_supervisor() {
    [[ -f "$SUPERVISOR_CONF_FILE" ]] || return 1

    require_cmd "$SUPERVISORCTL_BIN"

    log "Найден $SUPERVISOR_CONF_FILE, перезапускаю websocket через Supervisor"
    "$SUPERVISORCTL_BIN" reread || true
    "$SUPERVISORCTL_BIN" update || true
    "$SUPERVISORCTL_BIN" restart "$SUPERVISOR_PROGRAM_NAME"
}

main() {
    require_cmd bash
    require_cmd npm
    require_cmd npx
    require_cmd "$BACK_PHP_BIN"
    require_cmd "$BACK_COMPOSER_BIN"

    [[ -d "$FRONT_DIR" ]] || die "Не найдена папка фронта: $FRONT_DIR"
    [[ -d "$BACK_DIR" ]] || die "Не найдена папка backend: $BACK_DIR"

    log "1/6 Устанавливаю зависимости фронта"
    run_in_dir "$FRONT_DIR" "$FRONT_NODE_INSTALL_CMD"

    log "2/6 Собираю фронт Lampa"
    run_in_dir "$FRONT_DIR" "$FRONT_BUILD_CMD"
    [[ -f "$FRONT_BUILD_OUTPUT/index.html" ]] || die "Не найдена фронтовая сборка: $FRONT_BUILD_OUTPUT/index.html"
    publish_front

    log "3/6 Устанавливаю зависимости Laravel backend"
    run_in_dir "$BACK_DIR" "$BACK_COMPOSER_INSTALL_CMD"
    run_in_dir "$BACK_DIR" "$BACK_NODE_INSTALL_CMD"

    log "4/6 Готовлю Laravel env и собираю assets"
    ensure_backend_env
    ensure_sqlite_file
    run_in_dir "$BACK_DIR" "$BACK_BUILD_CMD"

    log "5/6 Применяю миграции и кэши Laravel"
    if truthy "$BACK_RUN_MIGRATIONS"; then
        run_in_dir "$BACK_DIR" "$BACK_PHP_BIN artisan migrate --force"
    fi

    if truthy "$BACK_RUN_STORAGE_LINK"; then
        run_in_dir "$BACK_DIR" "$BACK_PHP_BIN artisan storage:link || true"
    fi

    if truthy "$BACK_RUN_CACHE"; then
        run_in_dir "$BACK_DIR" "$BACK_PHP_BIN artisan optimize:clear"
    fi

    log "6/6 Права и websocket"
    set_permissions
    if ! restart_socket_via_supervisor; then
        install_socket_service
    fi

    log "Готово"
    log "Фронт: $FRONT_BUILD_OUTPUT"
    if [[ -n "$FRONT_PUBLISH_DIR" ]]; then
        log "Фронт опубликован в: $FRONT_PUBLISH_DIR"
    fi
    log "Backend: $BACK_DIR"
    log "Laravel public: $BACK_DIR/public"
}

main "$@"
