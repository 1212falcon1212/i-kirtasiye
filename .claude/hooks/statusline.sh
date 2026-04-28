#!/bin/bash
# UCES Status Line
# Shows contextual information in the CLI status bar

# Git branch
if [ -d ".git" ]; then
    BRANCH=$(git branch --show-current 2>/dev/null || echo "")
    if [ -n "$BRANCH" ]; then
        # Check for unpushed commits
        UNPUSHED=$(git log @{u}.. --oneline 2>/dev/null | wc -l | tr -d ' ')
        if [ "$UNPUSHED" -gt 0 ]; then
            echo -n "[$BRANCH ↑$UNPUSHED] "
        else
            echo -n "[$BRANCH] "
        fi
    fi
fi

# Context window usage (if available)
if [ -n "$CLAUDE_CONTEXT_USED" ] && [ -n "$CLAUDE_CONTEXT_MAX" ]; then
    PCT=$((CLAUDE_CONTEXT_USED * 100 / CLAUDE_CONTEXT_MAX))
    echo -n "ctx:${PCT}% "
fi
