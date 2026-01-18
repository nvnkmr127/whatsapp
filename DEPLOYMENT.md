# Deployment Scripts Summary

## Available Scripts

### 1. `cpanel_deploy.sh` - Main Deployment Script ✅
**Usage:** `./cpanel_deploy.sh`

**What it does:**
- ✅ Automatically handles Git conflicts (stashes local changes)
- ✅ Pulls latest code from repository
- ✅ Installs dependencies (Composer & NPM)
- ✅ Runs database migrations
- ✅ Clears and caches everything
- ✅ **Guarantees maintenance mode is disabled** (even on errors)
- ✅ Better error handling and reporting

**Improvements made:**
- Removed `set -e` to prevent premature exit
- Added `trap` to ensure app comes back online even if script fails
- Auto-stashes local changes before pulling
- Handles both `main` and `master` branches
- Better error messages and warnings
- Checks if NPM is available before using it

---

### 2. `fix_503.sh` - Fix 503 Errors
**Usage:** `./fix_503.sh`

**When to use:** If you get a 503 Service Unavailable error

**What it does:**
- Disables maintenance mode
- Fixes storage permissions
- Clears all caches
- Checks for errors in logs
- Verifies database connection
- Shows diagnostic information

---

### 3. `fix_deploy_conflicts.sh` - Resolve Git Conflicts
**Usage:** `./fix_deploy_conflicts.sh`

**When to use:** If deployment fails due to Git conflicts

**What it does:**
- Stashes local changes
- Pulls latest code
- Provides instructions for managing stashed changes

---

## Quick Reference

### Normal Deployment
```bash
./cpanel_deploy.sh
```

### If 503 Error After Deployment
```bash
./fix_503.sh
```

### If Git Conflicts
```bash
./fix_deploy_conflicts.sh
```

### Manual Maintenance Mode Control
```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up
```

### View Logs
```bash
# Live log monitoring
tail -f storage/logs/laravel.log

# Last 50 lines
tail -n 50 storage/logs/laravel.log
```

## Best Practices

1. **Always commit and push changes from local first**
   ```bash
   git add .
   git commit -m "Your changes"
   git push origin main
   ```

2. **Then deploy on server**
   ```bash
   ./cpanel_deploy.sh
   ```

3. **Monitor the deployment**
   - Watch the script output for errors
   - Check the site immediately after
   - Monitor logs if issues occur

4. **Keep backups**
   - Database backups before major deployments
   - Use Git tags for releases

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 503 Error | Run `./fix_503.sh` |
| Git conflicts | Run `./fix_deploy_conflicts.sh` |
| Permission errors | `chmod -R 775 storage bootstrap/cache` |
| Composer errors | `composer install --no-dev --optimize-autoloader` |
| NPM errors | `npm install --production && npm run build` |
| Database errors | Check `.env` database credentials |

## Safety Features

The improved deployment script now includes:
- ✅ **Automatic cleanup** - Ensures app comes back online even on failure
- ✅ **Git conflict handling** - Auto-stashes local changes
- ✅ **Error tolerance** - Continues even if non-critical steps fail
- ✅ **Better logging** - Clear success/warning/error messages
- ✅ **Health check** - Shows quick verification steps at the end
