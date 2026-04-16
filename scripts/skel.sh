#!/usr/bin/env bash

set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SKEL="$APP_DIR/resources/skel"

# ─────────────────────────────
# Validate action
# ─────────────────────────────
if [ -z "${1:-}" ]; then
    echo "[Blprnt] ❌ No action provided"
    echo "Usage: $0 {apply|commit|clean} [--dry-run]"
    exit 1
fi

ACTION="$1"
DRY_RUN=false

if [[ "${2:-}" == "--dry-run" ]]; then
    DRY_RUN=true
    echo "[Blprnt] ⚠️ DRY RUN MODE"
fi

DEBUG=false

for arg in "$@"; do
    if [[ "$arg" == "--debug" ]]; then
        DEBUG=true
        echo "[Blprnt] 🐛 DEBUG MODE ENABLED"
    fi
done

log_debug() {
    if $DEBUG; then
        echo "[Blprnt][DEBUG] $*"
    fi
}

ITEMS=("app" "bootstrap" "config" "public" "routes")


echo "[Blprnt] Action: $ACTION"
echo "[Blprnt][DEBUG] APP_DIR: $APP_DIR"
echo "[Blprnt][DEBUG] SKEL: $SKEL"
echo "[Blprnt][DEBUG] RSYNC_OPTS: ${RSYNC_OPTS[*]}"
echo "[Blprnt][DEBUG] EXCLUDES: ${EXCLUDES[*]}"
echo "[Blprnt][DEBUG] ITEMS: ${ITEMS[*]}"

# ─────────────────────────────
# Build rsync options
# ─────────────────────────────
RSYNC_OPTS=(-a --delete)

if $DRY_RUN; then
    RSYNC_OPTS+=(--dry-run --verbose)
fi

# ─────────────────────────────
# Build exclude list safely
# ─────────────────────────────
IGNORE_FILE="$APP_DIR/.gitignore"
EXCLUDES=()

if [ -f "$IGNORE_FILE" ]; then
    while IFS= read -r line; do
        [[ "$line" =~ ^#.*$ ]] && continue
        [[ -z "$line" ]] && continue
        EXCLUDES+=(--exclude="$line")
    done < "$IGNORE_FILE"
fi

# ─────────────────────────────
# Safety check
# ─────────────────────────────
assert_path_safe() {
    if [[ "$APP_DIR" == "/" || -z "$APP_DIR" ]]; then
        echo "[Blprnt] ❌ Unsafe APP_DIR"
        exit 1
    fi
}

# ─────────────────────────────
# Ensure .gitignore contains **
# ─────────────────────────────
ensure_gitignore() {
    local file="$APP_DIR/.gitignore"

    if [ ! -f "$file" ]; then
        echo "**" > "$file"
        echo "✔ created .gitignore with **"
    elif ! grep -qx "\*\*" "$file"; then
        echo "**" >> "$file"
        echo "✔ added ** to .gitignore"
    fi
}

# ─────────────────────────────
# APPLY (skel → root)
# ─────────────────────────────
apply() {
    for item in "${ITEMS[@]}"; do
        SRC="$SKEL/$item/"
        DEST="$APP_DIR/$item/"

        [ ! -d "$SRC" ] && echo "⚠️ skip missing $item" && continue


        echo "[Blprnt][DEBUG] rsync ${RSYNC_OPTS[*]} ${EXCLUDES[*]} $SRC $DEST"
        rsync "${RSYNC_OPTS[@]}" "${EXCLUDES[@]}" "$SRC" "$DEST"

        echo "✔ applied $item"
    done

    ensure_gitignore
}
commit() {
    RESTORE_DIR="$SKEL/.restore"

    mkdir -p "$RESTORE_DIR"

    for item in "${ITEMS[@]}"; do
        SRC="$APP_DIR/$item"
        DEST="$SKEL/$item"
        BACKUP="$RESTORE_DIR/$item"

        [ ! -d "$SRC" ] && echo "⚠️ skip missing $item" && continue

        log_debug "----------------------------------------"
        log_debug "COMMIT ITEM: $item"
        log_debug "SRC: $SRC"
        log_debug "DEST: $DEST"
        log_debug "BACKUP: $BACKUP"

        # ─────────────────────────────
        # 1. Backup current skel state (single restore slot)
        # ─────────────────────────────
        if [ -d "$BACKUP" ]; then
            rm -rf "$BACKUP"
            log_debug "Removed old backup: $BACKUP"
        fi

        if [ -d "$DEST" ]; then
            mv "$DEST" "$BACKUP"
            echo "📦 backed up $item → .restore"
        fi

        # ─────────────────────────────
        # 2. Copy ROOT → SKEL
        # ─────────────────────────────
        rsync -a --delete \
            --checksum \
            "${EXCLUDES[@]}" \
            "$SRC/" "$DEST/"

        echo "✔ copied $item → skel"

        # ─────────────────────────────
        # 3. VERIFY STEP (CRITICAL)
        # ─────────────────────────────
        echo "🔍 verifying $item..."

        DIFF=$(rsync -a --dry-run --itemize-changes \
            "${EXCLUDES[@]}" \
            "$SRC/" "$DEST/")

        if [[ -n "$DIFF" ]]; then
            echo "❌ VERIFY FAILED for $item"
            echo "$DIFF"
            echo "🚨 SKIPPING DELETE"
            continue
        fi

        echo "✅ verification passed for $item"

        # ─────────────────────────────
        # 4. DELETE ROOT AFTER VERIFY
        # ─────────────────────────────
        rm -rf "$SRC"
        echo "🗑 deleted source $item from root"

    done
}
# ─────────────────────────────
# CLEAN (dangerous)
# ─────────────────────────────
clean() {
    assert_path_safe

    if ! $DRY_RUN; then
        read -p "⚠️ This will DELETE project directories. Continue? (y/N): " confirm
        [[ "$confirm" != "y" ]] && exit 1
    fi

    for item in "${ITEMS[@]}"; do
        TARGET="$APP_DIR/$item"

        [ ! -d "$TARGET" ] && continue

        echo "[Blprnt][DEBUG] rm -rf $TARGET"
        rm -rf "$TARGET"
        echo "🗑 cleaned $item"
    done
}

# ─────────────────────────────
# Dispatcher
# ─────────────────────────────
case "$ACTION" in
    apply)
        apply
        ;;
    commit)
        commit
        ;;
    clean)
        clean
        ;;
    *)
        echo "[Blprnt] ❌ Invalid action: $ACTION"
        echo "Usage: $0 {apply|commit|clean} [--dry-run]"
        exit 1
        ;;
esac

echo "[Blprnt] Done."