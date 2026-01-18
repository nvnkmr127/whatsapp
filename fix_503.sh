#!/bin/bash

# 503 Error Diagnostic and Fix Script
# Run this on the server to diagnose and fix 503 errors

echo "🔍 Diagnosing 503 Service Unavailable Error..."
echo ""

# Check 1: Maintenance Mode
echo "1️⃣ Checking maintenance mode..."
if [ -f "storage/framework/down" ]; then
    echo "   ⚠️  Application is in MAINTENANCE MODE"
    echo "   Disabling maintenance mode..."
    php artisan up
    echo "   ✅ Maintenance mode disabled"
else
    echo "   ✅ Not in maintenance mode"
fi
echo ""

# Check 2: Storage permissions
echo "2️⃣ Checking storage permissions..."
if [ ! -w "storage" ]; then
    echo "   ⚠️  Storage directory not writable"
    echo "   Fixing permissions..."
    chmod -R 775 storage bootstrap/cache
    echo "   ✅ Permissions fixed"
else
    echo "   ✅ Storage is writable"
fi
echo ""

# Check 3: Check for Laravel errors in logs
echo "3️⃣ Checking recent errors in logs..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "   Last 10 errors:"
    tail -n 20 storage/logs/laravel.log | grep -i "error\|exception\|fatal" || echo "   ✅ No recent errors found"
else
    echo "   ⚠️  No log file found"
fi
echo ""

# Check 4: Check .env file exists
echo "4️⃣ Checking .env file..."
if [ -f ".env" ]; then
    echo "   ✅ .env file exists"
else
    echo "   ❌ .env file MISSING!"
    echo "   Creating from .env.example..."
    cp .env.example .env
    echo "   ⚠️  Please edit .env with your production settings!"
fi
echo ""

# Check 5: Check APP_KEY
echo "5️⃣ Checking APP_KEY..."
if grep -q "APP_KEY=base64:" .env; then
    echo "   ✅ APP_KEY is set"
else
    echo "   ⚠️  APP_KEY not set"
    echo "   Generating APP_KEY..."
    php artisan key:generate
    echo "   ✅ APP_KEY generated"
fi
echo ""

# Check 6: Clear all caches
echo "6️⃣ Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "   ✅ All caches cleared"
echo ""

# Check 7: Optimize autoloader
echo "7️⃣ Optimizing autoloader..."
composer dump-autoload --optimize
echo "   ✅ Autoloader optimized"
echo ""

# Check 8: Check database connection
echo "8️⃣ Testing database connection..."
php artisan migrate:status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✅ Database connection OK"
else
    echo "   ⚠️  Database connection failed"
    echo "   Please check your .env database settings"
fi
echo ""

# Check 9: Verify public directory
echo "9️⃣ Checking public directory..."
if [ -f "public/index.php" ]; then
    echo "   ✅ public/index.php exists"
else
    echo "   ❌ public/index.php MISSING!"
fi
echo ""

# Check 10: Check PHP version
echo "🔟 Checking PHP version..."
php -v | head -n 1
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 SUMMARY & NEXT STEPS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "If still getting 503 error, check:"
echo "1. cPanel Error Logs (in cPanel → Errors)"
echo "2. PHP Error Logs (in cPanel → PHP Error Log)"
echo "3. Laravel logs: storage/logs/laravel.log"
echo ""
echo "Common fixes:"
echo "• Make sure document root points to 'public' folder"
echo "• Check PHP version is 8.2 or higher"
echo "• Verify all required PHP extensions are enabled"
echo "• Check file ownership (should be your cPanel user)"
echo ""
echo "Run this to see detailed Laravel errors:"
echo "  tail -f storage/logs/laravel.log"
echo ""
