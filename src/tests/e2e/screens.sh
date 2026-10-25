#!/usr/bin/env bash
#
# Screenshot every main screen (guest, member, expert, admin; desktop and
# phone) on a throw-away database.
#
# Usage (Git Bash, from src/):   bash tests/e2e/screens.sh [out-dir] [light|dark]

set -u
cd "$(dirname "$0")/../.."

OUT="${1:-storage/framework/testing/e2e/screens}"
THEME="${2:-light}"

source tests/e2e/server.sh
start_server

export PATH="/c/Program Files/nodejs:$PATH"
node tests/e2e/screens.mjs "$B" "$OUT" "$THEME"
