#!/usr/bin/env bash

set -e

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"

# ─────────────────────────────
# LOAD ENV
# ─────────────────────────────

if [ -f "$ENV_FILE" ]; then
    export $(grep -v '^#' "$ENV_FILE" | xargs)
else
    echo "[Blprnt] ❌ Missing .env file"
    exit 1
fi

APP_ENV=${APP_ENV:-local}
WEB_USER=${WEB_USER:-www-data}
WEB_GROUP=${WEB_GROUP:-www-data}

SECURE_DIR="$APP_DIR/storage/secure"
STORAGE_DIR="$APP_DIR/storage"

echo "[Blprnt] Environment: $APP_ENV"

# ─────────────────────────────
# MODE HANDLING
# ─────────────────────────────

if [ "$APP_ENV" = "production" ]; then
    echo "[Blprnt] Production mode detected (sudo required)"

    if [ "$EUID" -ne 0 ]; then
        echo "[Blprnt] ❌ Must run with sudo in production"
        exit 1
    fi
else
    echo "[Blprnt] Local mode detected (no sudo required)"
fi

# ─────────────────────────────
# SAFETY FUNCTION
# ─────────────────────────────

rollback() {
    echo "[Blprnt] ❌ Failure detected. Restoring safe state..."

    if [ "$APP_ENV" = "production" ]; then
        chown -R $WEB_USER:$WEB_GROUP "$STORAGE_DIR" || true
    fi

    chmod -R 700 "$SECURE_DIR" || true

    echo "[Blprnt] ✔ Safe state restored"
    exit 1
}

trap rollback ERR

# ─────────────────────────────
# APPLY SECURITY
# ─────────────────────────────

mkdir -p "$SECURE_DIR"

if [ "$APP_ENV" = "production" ]; then
    echo "[Blprnt] Applying production ownership..."
    chown -R $WEB_USER:$WEB_GROUP "$STORAGE_DIR"
fi

echo "[Blprnt] Applying permissions..."
chmod -R 700 "$SECURE_DIR"

trap - ERR

echo "[Blprnt] ✔ Security applied successfully"