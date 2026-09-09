#!/data/data/com.termux/files/usr/bin/bash
# Works in Termux and regular Linux when PHP is installed.
set -e
DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
HOST="${HOST:-0.0.0.0}"
PORT="${PORT:-8080}"
if ! command -v php >/dev/null 2>&1; then echo "PHP is not installed."; exit 1; fi
mkdir -p "$DIR/data" "$DIR/servers" "$DIR/logs"
printf '\nMiloPanel: http://%s:%s\n\n' "$HOST" "$PORT"
exec php -S "$HOST:$PORT" -t "$DIR" "$DIR/router.php"
