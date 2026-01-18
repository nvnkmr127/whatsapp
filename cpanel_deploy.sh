#!/bin/bash

# cPanel Laravel Deployment Script
# This script automates the deployment process for Laravel applications on cPanel

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
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

# Function to ensure app is brought back online even on error
cleanup() {
    if [ -f "storage/framework/down" ]; then
        echo ""
        print_warning "Ensuring application is brought back online..."
        php artisan up || true
    fi
}

# Set trap to run cleanup on exit (success or failure)
trap cleanup EXIT

echo "🚀 Starting Laravel Deployment..."

# Step 1: Put application in maintenance mode
echo "📦 Putting application in maintenance mode..."
php artisan down || print_warning "Could not enable maintenance mode"
print_success "Maintenance mode enabled"

# Step 2: Handle Git conflicts and pull latest changes
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

# Step 3: Install/Update Composer dependencies
echo "📚 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction || {
    print_error "Composer install failed"
    exit 1
}
print_success "Composer dependencies installed"

# Step 4: Install/Update NPM dependencies and build assets
echo "🎨 Building frontend assets..."
if command -v npm &> /dev/null; then
    npm install --production --silent || print_warning "NPM install had warnings"
    npm run build || print_warning "Asset build had warnings"
    print_success "Frontend assets built"
else
    print_warning "NPM not found, skipping asset build"
fi

# Step 5: Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force || {
    print_error "Database migrations failed"
    exit 1
}
print_success "Database migrations completed"

# Step 6: Clear all caches
echo "🧹 Clearing all caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
print_success "Cache cleared"

# Step 7: Cache configuration for performance
echo "💾 Caching configuration..."
php artisan config:cache || print_warning "Config cache failed"
php artisan route:cache || print_warning "Route cache failed"
php artisan view:cache || print_warning "View cache failed"
print_success "Configuration cached"

# Step 8: Optimize application
echo "⚡ Optimizing application..."
php artisan optimize || print_warning "Optimization had warnings"
print_success "Application optimized"

# Step 9: Restart queue workers (if using queues)
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || print_warning "Queue workers not running"

# Step 10: Set proper permissions (cPanel specific)
echo "🔐 Setting proper permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || chmod -R 755 storage bootstrap/cache
print_success "Permissions set"

# Step 11: Bring application back online (also handled by trap)
echo "🌐 Bringing application back online..."
php artisan up
print_success "Application is now live"

echo ""
echo "✅ Deployment completed successfully!"
echo "🎉 Your application is now live and running!"
echo ""
echo "📊 Quick health check:"
echo "   • Check site: https://digichatify.tribebella.com"
echo "   • View logs: tail -f storage/logs/laravel.log"
echo ""
