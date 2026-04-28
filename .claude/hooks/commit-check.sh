#!/bin/bash
# UCES Pre-commit Validation
# Runs checks before git commits

set -e

echo "[UCES] Running pre-commit checks..."

# TypeScript check — handle monorepo (frontend/) and standard layouts
if [ -f "frontend/tsconfig.json" ]; then
    echo "[UCES] Checking TypeScript (frontend/)..."
    if command -v npx &> /dev/null; then
        (cd frontend && npx tsc --noEmit 2>&1) || {
            echo "[UCES] TypeScript errors found. Fix before committing." >&2
            exit 1
        }
    fi
elif [ -f "tsconfig.json" ]; then
    echo "[UCES] Checking TypeScript..."
    if command -v npx &> /dev/null; then
        npx tsc --noEmit 2>&1 || {
            echo "[UCES] TypeScript errors found. Fix before committing." >&2
            exit 1
        }
    fi
fi

# Check for secrets in staged files
echo "[UCES] Scanning for secrets..."
STAGED_FILES=$(git diff --cached --name-only 2>/dev/null || echo "")

for file in $STAGED_FILES; do
    if [ -f "$file" ]; then
        # Check for common secret patterns
        if grep -E "(api[_-]?key|secret|password|token)\s*[:=]\s*['\"][^'\"]+['\"]" "$file" 2>/dev/null | grep -v "process\.env\|import\.meta\.env\|env(" > /dev/null; then
            echo "[UCES] Warning: Potential secret in $file" >&2
        fi
    fi
done

# Check for large files
for file in $STAGED_FILES; do
    if [ -f "$file" ]; then
        SIZE=$(wc -c < "$file" 2>/dev/null || echo "0")
        if [ "$SIZE" -gt 1000000 ]; then
            echo "[UCES] Warning: Large file detected: $file (${SIZE} bytes)" >&2
        fi
    fi
done

echo "[UCES] Pre-commit checks passed"
exit 0
