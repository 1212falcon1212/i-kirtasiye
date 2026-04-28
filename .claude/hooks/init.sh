#!/bin/bash
# UCES Session Initialization
# Detects project context and loads relevant information

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}[UCES]${NC} Initializing session..."

# Detect project type
detect_project() {
    # Monorepo detection (Laravel + Next.js)
    if [ -d "backend" ] && [ -d "frontend" ]; then
        if [ -f "backend/artisan" ] && [ -f "frontend/package.json" ]; then
            echo "laravel-nextjs"
            return
        fi
    fi

    if [ -f "app.json" ] && grep -q "expo" "app.json" 2>/dev/null; then
        echo "expo"
    elif [ -f "next.config.js" ] || [ -f "next.config.ts" ] || [ -f "next.config.mjs" ]; then
        echo "nextjs"
    elif [ -f "artisan" ]; then
        echo "laravel"
    elif [ -f "package.json" ]; then
        if grep -q '"react-native"' package.json 2>/dev/null; then
            echo "react-native"
        elif grep -q '"react"' package.json 2>/dev/null; then
            echo "react"
        else
            echo "node"
        fi
    elif [ -f "composer.json" ]; then
        echo "php"
    else
        echo "unknown"
    fi
}

PROJECT_TYPE=$(detect_project)

# Display project info
echo -e "${GREEN}Project:${NC} $PROJECT_TYPE"

# Git status
if [ -d ".git" ]; then
    BRANCH=$(git branch --show-current 2>/dev/null || echo "detached")
    CHANGES=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')

    echo -e "${GREEN}Branch:${NC} $BRANCH"

    if [ "$CHANGES" -gt 0 ]; then
        echo -e "${YELLOW}Changes:${NC} $CHANGES uncommitted"
    fi

    # Recent commits
    echo -e "${GREEN}Recent:${NC}"
    git log --oneline -3 2>/dev/null | sed 's/^/  /'
fi

# Load session memory if exists
MEMORY_FILE="$HOME/.claude/memory/$(echo -n "$(pwd)" | md5 2>/dev/null || echo -n "$(pwd)" | md5sum 2>/dev/null | cut -d' ' -f1).json"
if [ -f "$MEMORY_FILE" ]; then
    echo -e "${BLUE}[UCES]${NC} Previous session learnings loaded"
fi

echo ""
