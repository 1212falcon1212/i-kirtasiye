#!/bin/bash
# UCES Session Memory
# Persists learnings across sessions

set -e

MEMORY_DIR="$HOME/.claude/memory"
mkdir -p "$MEMORY_DIR"

# Generate project-specific memory file
PROJECT_HASH=$(pwd | md5sum 2>/dev/null | cut -d' ' -f1 || echo "default")
MEMORY_FILE="$MEMORY_DIR/$PROJECT_HASH.json"

EVENT="${UCES_EVENT:-}"

case "$EVENT" in
    "SessionStart")
        # Load previous learnings
        if [ -f "$MEMORY_FILE" ]; then
            echo "[UCES] Loading session memory..."
            cat "$MEMORY_FILE" | jq -r '.learnings[]? | "  - \(.message)"' 2>/dev/null || true
        fi
        ;;
    "Stop")
        # Persist any pending learnings
        if [ -f "$MEMORY_FILE.pending" ]; then
            if [ -f "$MEMORY_FILE" ]; then
                # Merge pending into existing
                jq -s '.[0].learnings += .[1].learnings | .[0]' "$MEMORY_FILE" "$MEMORY_FILE.pending" > "$MEMORY_FILE.tmp"
                mv "$MEMORY_FILE.tmp" "$MEMORY_FILE"
            else
                mv "$MEMORY_FILE.pending" "$MEMORY_FILE"
            fi
            rm -f "$MEMORY_FILE.pending"
        fi
        ;;
    *)
        # Add learning (called via stdin)
        if [ -t 0 ]; then
            exit 0
        fi

        INPUT=$(cat)
        if [ -n "$INPUT" ]; then
            ENTRY=$(echo "$INPUT" | jq -c '{ timestamp: now | todate, category: .category, message: .content }' 2>/dev/null)
            if [ -n "$ENTRY" ]; then
                if [ ! -f "$MEMORY_FILE.pending" ]; then
                    echo '{"learnings":[]}' > "$MEMORY_FILE.pending"
                fi
                jq ".learnings += [$ENTRY]" "$MEMORY_FILE.pending" > "$MEMORY_FILE.pending.tmp"
                mv "$MEMORY_FILE.pending.tmp" "$MEMORY_FILE.pending"
            fi
        fi
        ;;
esac

exit 0
