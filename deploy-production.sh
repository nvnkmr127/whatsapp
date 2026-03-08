#!/bin/bash

# =============================================================================
# WhatsApp Business API - Production Deployment Script for DigitalOcean
# =============================================================================
# This script automates the deployment and queue worker setup
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN}WhatsApp Business API - Production Setup${NC}"
echo -e "${GREEN}=============================================${NC}"
echo ""

# =============================================================================
# Configuration Variables - UPDATE THESE FOR YOUR SERVER
# =============================================================================

PROJECT_PATH="/var/www/whatsapp"
WEB_USER="www-data"
SUPERVISOR_CONFIG_PATH="/etc/supervisor/conf.d/whatsapp-workers.conf"

# =============================================================================
# Step 1: Check if running with sudo
# =============================================================================

if [ "$EUID" -ne 0 ]; then 
   echo -e "${RED}Please run this script with sudo${NC}"
   echo "Usage: sudo bash deploy-production.sh"
   exit 1
fi

# =============================================================================
# Step 2: Install Supervisor (if not installed)
# =============================================================================

echo -e "${YELLOW}Step 1: Checking Supervisor installation...${NC}"

if ! command -v supervisord &> /dev/null; then
    echo "Supervisor not found. Installing..."
    apt-get update
    apt-get install -y supervisor
    echo -e "${GREEN}✓ Supervisor installed${NC}"
else
    echo -e "${GREEN}✓ Supervisor already installed${NC}"
fi

# =============================================================================
# Step 3: Check if Redis is running
# =============================================================================

echo -e "${YELLOW}Step 2: Checking Redis status...${NC}"

if ! systemctl is-active --quiet redis-server && ! systemctl is-active --quiet redis; then
    echo -e "${RED}✗ Redis is not running!${NC}"
    echo "Starting Redis..."
    systemctl start redis-server || systemctl start redis
    echo -e "${GREEN}✓ Redis started${NC}"
else
    echo -e "${GREEN}✓ Redis is running${NC}"
fi

# =============================================================================
# Step 4: Copy Supervisor configuration
# =============================================================================

echo -e "${YELLOW}Step 3: Setting up Supervisor configuration...${NC}"

if [ ! -f "${PROJECT_PATH}/supervisor-whatsapp-workers.conf" ]; then
    echo -e "${RED}✗ supervisor-whatsapp-workers.conf not found in project root!${NC}"
    echo "Please ensure the file exists at: ${PROJECT_PATH}/supervisor-whatsapp-workers.conf"
    exit 1
fi

# Update paths in supervisor config
sed -i "s|/var/www/whatsapp|${PROJECT_PATH}|g" "${PROJECT_PATH}/supervisor-whatsapp-workers.conf"
sed -i "s|user=www-data|user=${WEB_USER}|g" "${PROJECT_PATH}/supervisor-whatsapp-workers.conf"

# Copy to supervisor directory
cp "${PROJECT_PATH}/supervisor-whatsapp-workers.conf" "${SUPERVISOR_CONFIG_PATH}"
echo -e "${GREEN}✓ Supervisor configuration copied${NC}"

# =============================================================================
# Step 5: Create log directory
# =============================================================================

echo -e "${YELLOW}Step 4: Creating log directories...${NC}"

mkdir -p "${PROJECT_PATH}/storage/logs"
chown -R ${WEB_USER}:${WEB_USER} "${PROJECT_PATH}/storage"
chmod -R 775 "${PROJECT_PATH}/storage"
echo -e "${GREEN}✓ Log directories ready${NC}"

# =============================================================================
# Step 6: Optimize Laravel
# =============================================================================

echo -e "${YELLOW}Step 5: Optimizing Laravel...${NC}"

cd "${PROJECT_PATH}"

# Clear all caches
sudo -u ${WEB_USER} php artisan config:clear
sudo -u ${WEB_USER} php artisan cache:clear
sudo -u ${WEB_USER} php artisan route:clear
sudo -u ${WEB_USER} php artisan view:clear

# Cache for production
sudo -u ${WEB_USER} php artisan config:cache
sudo -u ${WEB_USER} php artisan route:cache
sudo -u ${WEB_USER} php artisan view:cache

echo -e "${GREEN}✓ Laravel optimized${NC}"

# =============================================================================
# Step 7: Reload Supervisor
# =============================================================================

echo -e "${YELLOW}Step 6: Reloading Supervisor...${NC}"

supervisorctl reread
supervisorctl update
supervisorctl status

echo -e "${GREEN}✓ Supervisor reloaded${NC}"

# =============================================================================
# Step 8: Start Queue Workers
# =============================================================================

echo -e "${YELLOW}Step 7: Starting queue workers...${NC}"

# Stop all workers first (in case any are running)
supervisorctl stop whatsapp-queue:* 2>/dev/null || true

# Start all workers
supervisorctl start whatsapp-queue:*

echo -e "${GREEN}✓ Queue workers started${NC}"

# =============================================================================
# Step 9: Verify Workers are Running
# =============================================================================

echo -e "${YELLOW}Step 8: Verifying workers...${NC}"
sleep 2

RUNNING_WORKERS=$(supervisorctl status whatsapp-queue:* | grep RUNNING | wc -l)
TOTAL_WORKERS=$(supervisorctl status whatsapp-queue:* | wc -l)

echo "Running workers: ${RUNNING_WORKERS}/${TOTAL_WORKERS}"

if [ "$RUNNING_WORKERS" -eq "$TOTAL_WORKERS" ]; then
    echo -e "${GREEN}✓ All workers are running!${NC}"
else
    echo -e "${YELLOW}⚠ Some workers may have failed to start${NC}"
    echo "Check logs: supervisorctl tail -f whatsapp-queue-webhooks"
fi

# =============================================================================
# Step 10: Check Queue Status
# =============================================================================

echo -e "${YELLOW}Step 9: Checking queue status...${NC}"

echo "Queue sizes in Redis:"
redis-cli llen queues:webhooks | xargs echo "  webhooks:"
redis-cli llen queues:messages | xargs echo "  messages:"
redis-cli llen queues:campaigns | xargs echo "  campaigns:"
redis-cli llen queues:broadcasts | xargs echo "  broadcasts:"
redis-cli llen queues:default | xargs echo "  default:"

# =============================================================================
# Final Summary
# =============================================================================

echo ""
echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN}✓ Deployment Complete!${NC}"
echo -e "${GREEN}=============================================${NC}"
echo ""
echo "Next steps:"
echo "1. Check worker status: sudo supervisorctl status"
echo "2. View worker logs: sudo supervisorctl tail -f whatsapp-queue-webhooks"
echo "3. Monitor queues: watch -n 2 'redis-cli llen queues:webhooks'"
echo "4. Restart workers: sudo supervisorctl restart whatsapp-queue:*"
echo ""
echo "Useful commands:"
echo "  - View all logs: sudo tail -f ${PROJECT_PATH}/storage/logs/*.log"
echo "  - Stop workers: sudo supervisorctl stop whatsapp-queue:*"
echo "  - Start workers: sudo supervisorctl start whatsapp-queue:*"
echo "  - Restart workers: sudo supervisorctl restart whatsapp-queue:*"
echo ""
echo -e "${YELLOW}IMPORTANT: Update your .env file with production settings${NC}"
echo ""
