#!/bin/bash
# UCES Post-edit Formatting
# Auto-formats files after edits and checks for anti-patterns

set -e

FILE_PATH="${CLAUDE_FILE_PATH:-}"

if [ -z "$FILE_PATH" ]; then
    exit 0
fi

# Get file extension
EXT="${FILE_PATH##*.}"

# Format with Prettier if available — handle monorepo
PRETTIER_BIN=""
if [ -f "frontend/node_modules/.bin/prettier" ]; then
    PRETTIER_BIN="frontend/node_modules/.bin/prettier"
elif [ -f "node_modules/.bin/prettier" ]; then
    PRETTIER_BIN="node_modules/.bin/prettier"
fi

if [ -n "$PRETTIER_BIN" ]; then
    case "$EXT" in
        ts|tsx|js|jsx|json|css|scss|md)
            $PRETTIER_BIN --write "$FILE_PATH" 2>/dev/null || true
            ;;
    esac
fi

# Check for anti-patterns in TypeScript/JavaScript
if [[ "$EXT" =~ ^(ts|tsx|js|jsx)$ ]]; then
    # Check for TODO comments
    if grep -n "TODO\|FIXME\|XXX" "$FILE_PATH" 2>/dev/null; then
        echo "[UCES] Warning: TODO/FIXME comments detected" >&2
    fi

    # Check for any type
    if grep -n ": any" "$FILE_PATH" 2>/dev/null; then
        echo "[UCES] Warning: 'any' type detected" >&2
    fi

    # Check for console.log
    if grep -n "console\.log" "$FILE_PATH" 2>/dev/null; then
        echo "[UCES] Note: console.log detected (remove before production)" >&2
    fi
fi

exit 0
