#!/bin/bash

# =============================================================================
# Production Reverb WebSocket Troubleshooting Script
# =============================================================================
# Run this on your production server (DigitalOcean droplet) to diagnose 502/connection issues:
# Command: sudo bash troubleshoot_reverb.sh
# =============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_PATH="/var/www/whatsapp"

echo -e "${BLUE}🔍 Starting Reverb WebSocket Diagnostics...${NC}\n"

# 1. Read production .env Reverb settings
echo -e "${YELLOW}1️⃣ Checking Reverb settings in .env:${NC}"
if [ -f "$PROJECT_PATH/.env" ]; then
    grep -E 'REVERB_|VITE_REVERB_' "$PROJECT_PATH/.env"
else
    echo -e "${RED}✗ .env file not found at $PROJECT_PATH/.env${NC}"
fi
echo ""

# 2. Check Supervisor Status
echo -e "${YELLOW}2️⃣ Checking Supervisor Reverb Worker status:${NC}"
if command -v supervisorctl &> /dev/null; then
    supervisorctl status | grep -E 'reverb|whatsapp'
else
    echo -e "${RED}✗ supervisorctl not installed${NC}"
fi
echo ""

# 3. Check Listening Ports & Running Processes
echo -e "${YELLOW}3️⃣ Checking running Reverb/PHP processes:${NC}"
ps aux | grep -E 'reverb:start|artisan' | grep -v 'grep' || echo "No running reverb processes found."
echo ""
echo -e "${YELLOW}3️⃣b Checking which ports are actively listening on 127.0.0.1/0.0.0.0:${NC}"
if command -v ss &> /dev/null; then
    ss -tulpn | grep -E '8080|8081|php' || echo "No active listener on 8080 or 8081"
elif command -v netstat &> /dev/null; then
    netstat -tulpn | grep -E '8080|8081|php' || echo "No active listener on 8080 or 8081"
else
    lsof -i :8080 -i :8081 || echo "Neither ss, netstat, nor lsof could find active listeners on 8080/8081"
fi
echo ""

# 4. Check Nginx configuration in multiple locations
echo -e "${YELLOW}4️⃣ Checking Nginx Proxy Configuration:${NC}"
NGINX_CONF_PATH=""
for dir in "/etc/nginx/sites-enabled" "/etc/nginx/sites-available" "/etc/nginx/conf.d" "/etc/nginx"; do
    if [ -d "$dir" ]; then
        FOUND=$(grep -rl "flow.watxio.com" "$dir" 2>/dev/null | head -n 1)
        if [ -n "$FOUND" ]; then
            NGINX_CONF_PATH="$FOUND"
            break
        fi
    fi
done

if [ -n "$NGINX_CONF_PATH" ] && [ -f "$NGINX_CONF_PATH" ]; then
    echo -e "Found Nginx config: ${BLUE}$NGINX_CONF_PATH${NC}"
    echo "---------------- Nginx proxy pass rules ----------------"
    grep -E 'proxy_pass|upstream|server_name|location' "$NGINX_CONF_PATH" || grep -A 5 -B 2 "proxy_pass" "$NGINX_CONF_PATH" || echo "No proxy_pass directive found in config."
else
    # Fallback to scanning for any config containing reverb proxy pass
    NGINX_CONF_PATH=$(grep -rl "proxy_pass.*:808" /etc/nginx/ 2>/dev/null | head -n 1)
    if [ -n "$NGINX_CONF_PATH" ] && [ -f "$NGINX_CONF_PATH" ]; then
        echo -e "Found matching Nginx proxy config: ${BLUE}$NGINX_CONF_PATH${NC}"
        grep -E 'proxy_pass|location|server_name' "$NGINX_CONF_PATH"
    else
        echo -e "${RED}✗ Could not find Nginx configuration file containing flow.watxio.com or reverb proxy ports.${NC}"
    fi
fi
echo ""

# 4b. Check Laravel Config Status
echo -e "${YELLOW}4️⃣b Checking Laravel Configuration State:${NC}"
if [ -f "$PROJECT_PATH/bootstrap/cache/config.php" ]; then
    echo -e "${YELLOW}⚠ Laravel configuration is CACHED. Reverb might be using stale cached settings.${NC}"
    echo -e "To clear, run: ${GREEN}php artisan config:clear${NC}"
else
    echo -e "${GREEN}✓ Laravel configuration is not cached (reads live from .env)${NC}"
fi
echo ""
echo -e "Resolved Reverb server port via artisan:"
php "$PROJECT_PATH/artisan" tinker --execute="echo 'Port: ' . config('reverb.servers.reverb.port') . PHP_EOL . 'Host: ' . config('reverb.servers.reverb.host') . PHP_EOL;" 2>/dev/null || echo "Could not run artisan tinker."
echo ""

# 5. Tail Reverb Worker logs
echo -e "${YELLOW}5️⃣ Tail of Reverb log file:${NC}"
LOG_FILE="$PROJECT_PATH/storage/logs/reverb.log"
if [ -f "$LOG_FILE" ]; then
    tail -n 30 "$LOG_FILE"
else
    echo -e "${RED}✗ Reverb log file not found at $LOG_FILE${NC}"
fi
echo ""

echo -e "${BLUE}=================================================${NC}"
echo -e "${BLUE}💡 TROUBLESHOOTING TIPS:${NC}"
echo -e "${BLUE}=================================================${NC}"
echo "- If Nginx proxies to 8080, but Reverb listens on 8081 (or vice versa): update .env to match Nginx's target port."
echo "- If there are multiple Reverb workers in Supervisor (e.g. reverb and whatsapp-reverb), disable one to avoid port conflicts."
echo "- Always run 'php artisan config:clear' after changing port values in .env!"
echo ""
