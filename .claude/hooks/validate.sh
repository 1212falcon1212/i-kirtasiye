#!/bin/bash
# UCES Pre-edit Validation
# Checks for potentially dangerous patterns before file edits

set -e

# Get file path from environment (set by Claude Code)
FILE_PATH="${CLAUDE_FILE_PATH:-}"

if [ -z "$FILE_PATH" ]; then
    exit 0
fi

# Check for sensitive files
case "$FILE_PATH" in
    *.env|*.env.*|.env*)
        echo "[UCES] Warning: Editing environment file" >&2
        ;;
    *credentials*|*secrets*|*private*)
        echo "[UCES] Warning: Editing potentially sensitive file" >&2
        ;;
esac

exit 0
