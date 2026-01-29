#!/bin/bash

# cPanel/VPS Laravel Deployment Script
# Optimized for DigitalOcean (flow.watxio.com)

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_header() {
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Function to ensure app is brought back online even on error
cleanup() {
    if [ -f "storage/framework/down" ]; then
        echo ""
        print_warning "Ensuring application is brought back online..."
        sudo php artisan up || true
    fi
}

# Set trap to run cleanup on exit (success or failure)
trap cleanup EXIT

echo "🚀 Starting Laravel Deployment..."
echo ""

# Step 1: Fetch latest changes to compare
print_header "📡 FETCHING LATEST CHANGES"
git fetch origin main 2>/dev/null || git fetch origin master 2>/dev/null

# Step 2: Show what will change
print_header "📊 CHANGES PREVIEW"

# Get current commit
CURRENT_COMMIT=$(git rev-parse HEAD)
CURRENT_BRANCH=$(git branch --show-current)

# Get remote commit
REMOTE_COMMIT=$(git rev-parse origin/main 2>/dev/null || git rev-parse origin/master 2>/dev/null)

echo ""
print_info "Current Production Version:"
echo "  Branch: $CURRENT_BRANCH"
echo "  Commit: ${CURRENT_COMMIT:0:8}"
git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" HEAD
echo ""

print_info "Latest Git Version:"
echo "  Commit: ${REMOTE_COMMIT:0:8}"
git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" origin/main 2>/dev/null || git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" origin/master 2>/dev/null
echo ""

# Check if there are changes
if [ "$CURRENT_COMMIT" = "$REMOTE_COMMIT" ]; then
    print_warning "No new changes to deploy. Production is up to date!"
    echo ""
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Deployment cancelled."
        exit 0
    fi
else
    echo ""
    print_header "📝 FILES THAT WILL CHANGE"
    echo ""
    
    # Show files that will change
    git diff --name-status HEAD..origin/main 2>/dev/null || git diff --name-status HEAD..origin/master 2>/dev/null | head -20
    
    echo ""
    print_info "Commits to be deployed:"
    git log --oneline HEAD..origin/main 2>/dev/null || git log --oneline HEAD..origin/master 2>/dev/null | head -10
    
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    read -p "Deploy these changes? (Y/n): " -n 1 -r
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        print_info "Deployment cancelled by user."
        exit 0
    fi
fi

echo ""
print_header "🚀 STARTING DEPLOYMENT"

# Step 0: Pre-deployment permission fix (prevents composer/artisan errors)
echo "🔐 Preparing permissions..."
sudo chown -R $USER:www-data .
sudo chmod -R 775 storage bootstrap/cache
print_success "Permissions prepared"

# Step 3: Put application in maintenance mode
echo "📦 Putting application in maintenance mode..."
sudo php artisan down || print_warning "Could not enable maintenance mode"
print_success "Maintenance mode enabled"

# Step 4: Handle Git conflicts and pull latest changes
echo "📥 Pulling latest changes from repository..."

# Check for local changes
if ! git diff-index --quiet HEAD --; then
    print_warning "Local changes detected, stashing them..."
    git stash push -m "Auto-stash before deployment $(date +%Y-%m-%d_%H-%M-%S)"
    print_success "Local changes stashed"
fi

# Pull latest changes
if git pull origin main 2>/dev/null; then
    print_success "Code updated from main branch"
elif git pull origin master 2>/dev/null; then
    print_success "Code updated from master branch"
else
    print_error "Failed to pull from repository"
    exit 1
fi

# Step 5: Install/Update Composer dependencies
echo "📚 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction || {
    print_error "Composer install failed"
    exit 1
}
print_success "Composer dependencies installed"

# Step 6: Install/Update NPM dependencies and build assets
echo "🎨 Building frontend assets..."
if command -v npm &> /dev/null; then
    npm install || print_warning "NPM install had warnings"
    npm run build || print_warning "Asset build had warnings"
    print_success "Frontend assets built"
else
    print_warning "NPM not found, skipping asset build"
fi

# Step 7: Run database migrations
echo "🗄️  Running database migrations..."
sudo php artisan migrate --force || {
    print_error "Database migrations failed"
    exit 1
}
print_success "Database migrations completed"

# Step 8: Clear all caches
echo "🧹 Clearing all caches..."
sudo php artisan config:clear || true
sudo php artisan cache:clear || true
sudo php artisan route:clear || true
sudo php artisan view:clear || true
print_success "Cache cleared"

# Step 9: Cache configuration for performance
echo "💾 Caching configuration..."
sudo php artisan config:cache || print_warning "Config cache failed"
sudo php artisan route:cache || print_warning "Route cache failed"
sudo php artisan view:cache || print_warning "View cache failed"
print_success "Configuration cached"

# Step 10: Optimize application
echo "⚡ Optimizing application..."
sudo php artisan optimize || print_warning "Optimization had warnings"
print_success "Application optimized"

# Step 11: Restart queue workers and services
echo "🔄 Restarting queue workers and services..."
sudo php artisan queue:restart 2>/dev/null || print_warning "Queue workers not running"
sudo supervisorctl restart all 2>/dev/null || print_warning "Supervisor not found or access denied"

# Step 12: Set final permissions (Ensures web server ownership)
echo "🔐 Setting final permissions..."
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
print_success "Permissions set"

# Step 13: Bring application back online
echo "🌐 Bringing application back online..."
sudo php artisan up
print_success "Application is now live"

# Get new commit info
NEW_COMMIT=$(git rev-parse HEAD)

echo ""
print_header "✅ DEPLOYMENT COMPLETED SUCCESSFULLY"
echo ""
print_info "Deployed Version:"
echo "  Commit: ${NEW_COMMIT:0:8}"
git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" HEAD
echo ""
print_info "📊 Quick health check:"
echo "   • Check site: https://flow.watxio.com"
echo "   • View logs: tail -f storage/logs/laravel.log"
echo ""
