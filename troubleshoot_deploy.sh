#!/bin/bash

# Enhanced Deployment Troubleshooting Script
# Run this to see detailed Git status and fix issues

echo "🔍 Deployment Troubleshooting..."
echo ""

# Check Git status
echo "1️⃣ Git Status:"
git status
echo ""

# Check for uncommitted changes
echo "2️⃣ Uncommitted Changes:"
git diff --stat
echo ""

# Check stash list
echo "3️⃣ Stashed Changes:"
git stash list
echo ""

# Check remote connection
echo "4️⃣ Testing Remote Connection:"
git remote -v
echo ""

# Try to fetch (without merging)
echo "5️⃣ Fetching Latest Changes:"
git fetch origin main 2>&1
echo ""

# Check what would be pulled
echo "6️⃣ Changes to Pull:"
git log HEAD..origin/main --oneline 2>&1 || echo "No changes to pull or branch mismatch"
echo ""

# Check current branch
echo "7️⃣ Current Branch:"
git branch --show-current
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 RECOMMENDED ACTIONS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "If you see merge conflicts or errors above:"
echo ""
echo "Option 1 - Force pull (CAUTION: Discards local changes):"
echo "  git reset --hard origin/main"
echo "  ./cpanel_deploy.sh"
echo ""
echo "Option 2 - Manual merge:"
echo "  git pull origin main"
echo "  # Resolve any conflicts"
echo "  git add ."
echo "  git commit -m 'Merge conflicts resolved'"
echo "  ./cpanel_deploy.sh"
echo ""
echo "Option 3 - Keep local changes and skip pull:"
echo "  # Just run migrations and cache clear"
echo "  php artisan migrate --force"
echo "  php artisan config:clear && php artisan cache:clear"
echo "  php artisan optimize"
echo ""
