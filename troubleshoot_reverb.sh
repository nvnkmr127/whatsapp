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

# 3. Check Listening Ports
echo -e "${YELLOW}3️⃣ Checking which ports are actively listening on 127.0.0.1:${NC}"
if command -v ss &> /dev/null; then
    ss -tulpn | grep -E '8080|8081|reverb|php' || echo "No active listener on 8080 or 8081"
elif command -v netstat &> /dev/null; then
    netstat -tulpn | grep -E '8080|8081|reverb|php' || echo "No active listener on 8080 or 8081"
else
    lsof -i :8080 -i :8081 || echo "Neither ss, netstat, nor lsof could find active listeners on 8080/8081"
fi
echo ""

# 4. Check Nginx configuration
echo -e "${YELLOW}4️⃣ Checking Nginx Proxy Configuration:${NC}"
NGINX_CONF_PATH=""
if [ -f "/etc/nginx/sites-enabled/default" ]; then
    NGINX_CONF_PATH="/etc/nginx/sites-enabled/default"
elif [ -f "/etc/nginx/sites-enabled/whatsapp" ]; then
    NGINX_CONF_PATH="/etc/nginx/sites-enabled/whatsapp"
else
    # Find any site config containing flow.watxio.com
    NGINX_CONF_PATH=$(grep -rl "flow.watxio.com" /etc/nginx/sites-enabled/ 2>/dev/null | head -n 1)
fi

if [ -n "$NGINX_CONF_PATH" ] && [ -f "$NGINX_CONF_PATH" ]; then
    echo -e "Found Nginx config: ${BLUE}$NGINX_CONF_PATH${NC}"
    echo "---------------- Nginx proxy pass rules ----------------"
    grep -A 5 -B 2 "proxy_pass" "$NGINX_CONF_PATH" || echo "No proxy_pass directive found in config."
else
    echo -e "${RED}✗ Could not find Nginx configuration file for flow.watxio.com in /etc/nginx/sites-enabled/${NC}"
fi
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
echo "- If Nginx proxies to 8080, but Reverb listens on 8081: update .env to REVERB_SERVER_PORT=8080 and restart supervisor."
echo "- If Reverb fails to start (e.g. Address already in use): another process is using the port. Find it with: sudo lsof -i :<port>"
echo "- If supervisor is stopped: start it with: sudo supervisorctl start whatsapp-queue:*"
echo ""
