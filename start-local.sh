#!/bin/bash
# Start local WordPress dev server on 127.0.0.1:8000
# This uses PHP built-in server instead of Herd.

set -e

PORT="${1:-8000}"
HOST="127.0.0.1"
DOC_ROOT="$(cd "$(dirname "$0")" && pwd)"

echo "Starting PHP dev server at http://${HOST}:${PORT}/"
echo "Press Ctrl+C to stop"

php -S "${HOST}:${PORT}" -t "${DOC_ROOT}" "${DOC_ROOT}/router.php"
