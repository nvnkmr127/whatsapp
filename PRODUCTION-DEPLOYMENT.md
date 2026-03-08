# 🚀 Production Deployment Guide - DigitalOcean

Complete guide to deploy WhatsApp Business API on DigitalOcean with queue workers.

---

## 📋 Prerequisites

Before deploying, ensure you have:

- ✅ DigitalOcean Droplet (Ubuntu 20.04/22.04)
- ✅ PHP 8.2+ installed
- ✅ MySQL/MariaDB installed
- ✅ Redis installed and running
- ✅ Nginx/Apache configured
- ✅ Composer installed
- ✅ Node.js & NPM installed
- ✅ SSL certificate (Let's Encrypt recommended)
- ✅ Domain pointing to your server

---

## 🔧 Step-by-Step Deployment

### **Step 1: Upload Files to Server**

```bash
# On your local machine
rsync -avz --exclude 'node_modules' --exclude 'vendor' \
  ./ user@your-server-ip:/var/www/whatsapp/
```

Or use Git:

```bash
# On your server
cd /var/www
git clone https://github.com/your-repo/whatsapp-api.git
cd whatsapp-api
```

### **Step 2: Install Dependencies**

```bash
cd /var/www/whatsapp

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install

# Build assets
npm run build
```

### **Step 3: Configure Environment**

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

**Required .env settings**:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_saas
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=your-domain.com
REVERB_PORT=8080
REVERB_SCHEME=https

# WhatsApp Configuration
WHATSAPP_VERIFY_TOKEN=your-random-token
WHATSAPP_APP_SECRET=your-meta-app-secret
```

### **Step 4: Setup Database**

```bash
php artisan migrate --force
php artisan db:seed --force
```

### **Step 5: Set Permissions**

```bash
# Set correct ownership
sudo chown -R www-data:www-data /var/www/whatsapp
sudo chmod -R 775 /var/www/whatsapp/storage
sudo chmod -R 775 /var/www/whatsapp/bootstrap/cache
```

### **Step 6: Run Deployment Script**

```bash
# Make script executable
chmod +x deploy-production.sh

# Run deployment (installs Supervisor, configures workers)
sudo bash deploy-production.sh
```

**The script will**:
- ✅ Install Supervisor (if not installed)
- ✅ Check Redis is running
- ✅ Copy and configure Supervisor config
- ✅ Create log directories
- ✅ Optimize Laravel (cache config, routes, views)
- ✅ Start 30+ queue workers
- ✅ Start Reverb WebSocket server
- ✅ Verify everything is running

---

## 📊 Verify Installation

### **Check Queue Workers**

```bash
# View worker status
sudo supervisorctl status

# Expected output:
# whatsapp-queue-webhooks:whatsapp-queue-webhooks_00   RUNNING   pid 1234
# whatsapp-queue-webhooks:whatsapp-queue-webhooks_01   RUNNING   pid 1235
# whatsapp-queue-messages:whatsapp-queue-messages_00   RUNNING   pid 1236
# ... (30+ workers total)
```

### **Check Redis Queues**

```bash
# Monitor queue sizes
watch -n 2 'redis-cli llen queues:webhooks && redis-cli llen queues:messages'

# All should be 0 when idle, numbers when processing
```

### **Check Reverb WebSocket**

```bash
# Should show Reverb process running
sudo supervisorctl status whatsapp-reverb

# Test WebSocket connection
curl -I http://localhost:8080
```

### **Test Webhook Endpoint**

```bash
# From another machine
curl -X POST https://your-domain.com/webhook/whatsapp \
  -H "Content-Type: application/json" \
  -d '{"test": "payload"}'

# Should return: EVENT_RECEIVED
```

---

## 🔍 Monitoring & Logs

### **View Worker Logs**

```bash
# Real-time webhook worker logs
sudo supervisorctl tail -f whatsapp-queue-webhooks

# Real-time message worker logs
sudo supervisorctl tail -f whatsapp-queue-messages

# All Laravel logs
tail -f /var/www/whatsapp/storage/logs/laravel.log

# Specific worker logs
tail -f /var/www/whatsapp/storage/logs/worker-webhooks.log
```

### **Monitor Queue Performance**

```bash
# Watch queue sizes in real-time
watch -n 1 'echo "=== Queue Sizes ===" && \
  redis-cli llen queues:webhooks | xargs echo "Webhooks:" && \
  redis-cli llen queues:messages | xargs echo "Messages:" && \
  redis-cli llen queues:campaigns | xargs echo "Campaigns:" && \
  redis-cli llen queues:broadcasts | xargs echo "Broadcasts:"'
```

### **Check Failed Jobs**

```bash
# View failed jobs in database
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry job-id
```

---

## 🛠️ Common Operations

### **Restart Workers After Code Update**

```bash
# After git pull or code changes
sudo supervisorctl restart whatsapp-queue:*

# Or restart specific queue
sudo supervisorctl restart whatsapp-queue-webhooks:*
```

### **Stop All Workers**

```bash
sudo supervisorctl stop whatsapp-queue:*
```

### **Start All Workers**

```bash
sudo supervisorctl start whatsapp-queue:*
```

### **Restart Reverb**

```bash
sudo supervisorctl restart whatsapp-reverb
```

### **Update Code Deployment**

```bash
# 1. Pull latest code
cd /var/www/whatsapp
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart workers
sudo supervisorctl restart whatsapp-queue:*
```

---

## 🚨 Troubleshooting

### **Workers Not Processing Jobs**

```bash
# Check if workers are running
sudo supervisorctl status | grep RUNNING

# Check Redis connection
redis-cli ping

# Check queue configuration
php artisan config:show queue.default

# View worker errors
sudo supervisorctl tail whatsapp-queue-webhooks stderr
```

### **Messages Not Receiving**

1. **Check webhook endpoint is accessible**:
   ```bash
   curl https://your-domain.com/webhook/whatsapp
   ```

2. **Check workers are processing**:
   ```bash
   redis-cli llen queues:webhooks
   redis-cli llen queues:messages
   ```

3. **View webhook logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i webhook
   ```

### **Campaigns Not Sending**

1. **Check campaign queue**:
   ```bash
   redis-cli llen queues:campaigns
   ```

2. **Check campaign workers**:
   ```bash
   sudo supervisorctl status whatsapp-queue-campaigns:*
   ```

3. **View campaign logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i campaign
   ```

### **Live Monitoring Not Working**

1. **Check Reverb is running**:
   ```bash
   sudo supervisorctl status whatsapp-reverb
   ```

2. **Check broadcast queue**:
   ```bash
   redis-cli llen queues:broadcasts
   ```

3. **Test WebSocket**:
   ```bash
   # Should return connection info
   curl http://localhost:8080
   ```

4. **Check firewall allows port 8080**:
   ```bash
   sudo ufw status
   sudo ufw allow 8080/tcp
   ```

---

## 🔐 Security Considerations

### **Firewall Configuration**

```bash
# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow Reverb (WebSocket)
sudo ufw allow 8080/tcp

# Enable firewall
sudo ufw enable
```

### **Nginx Configuration for WebSocket**

Add to your Nginx site configuration:

```nginx
# WebSocket proxy for Reverb
location /app {
    proxy_pass http://localhost:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
```

### **Environment Security**

```bash
# Protect .env file
chmod 600 .env
chown www-data:www-data .env

# Never commit .env to git
echo ".env" >> .gitignore
```

---

## 📈 Performance Optimization

### **Redis Configuration**

Edit `/etc/redis/redis.conf`:

```conf
# Increase max memory
maxmemory 2gb
maxmemory-policy allkeys-lru

# Enable persistence
save 900 1
save 300 10
save 60 10000

# Increase max clients
maxclients 10000
```

Restart Redis:
```bash
sudo systemctl restart redis-server
```

### **Supervisor Worker Scaling**

Edit `/etc/supervisor/conf.d/whatsapp-workers.conf`:

```ini
# Increase workers for high load
[program:whatsapp-queue-webhooks]
numprocs=10  # Increase from 5

[program:whatsapp-queue-messages]
numprocs=20  # Increase from 10
```

Reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### **PHP-FPM Tuning**

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500
```

Restart:
```bash
sudo systemctl restart php8.2-fpm
```

---

## 📞 Support & Maintenance

### **Health Checks**

Create a cron job for health monitoring:

```bash
# Edit crontab
crontab -e

# Add health check every 5 minutes
*/5 * * * * /var/www/whatsapp/artisan queue:monitor redis:webhooks,redis:messages --max=1000
```

### **Backup Strategy**

```bash
# Database backup
php artisan backup:run

# Or manual MySQL backup
mysqldump -u root -p whatsapp_saas > backup-$(date +%Y%m%d).sql
```

### **Log Rotation**

Create `/etc/logrotate.d/whatsapp`:

```
/var/www/whatsapp/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0775 www-data www-data
    sharedscripts
    postrotate
        sudo supervisorctl restart whatsapp-queue:*
    endscript
}
```

---

## ✅ Production Checklist

Before going live:

- [ ] All environment variables configured
- [ ] Database migrated and seeded
- [ ] SSL certificate installed
- [ ] Firewall configured
- [ ] Queue workers running (verify with `supervisorctl status`)
- [ ] Redis running and accessible
- [ ] Reverb WebSocket server running
- [ ] Webhook endpoint accessible from internet
- [ ] Meta webhook configured with your URL
- [ ] Tested message receiving
- [ ] Tested campaign sending
- [ ] Tested live monitoring
- [ ] Log monitoring setup
- [ ] Backup strategy configured
- [ ] Domain DNS configured
- [ ] `APP_DEBUG=false` in production

---

## 📚 Additional Resources

- [Laravel Queue Documentation](https://laravel.com/docs/12.x/queues)
- [Supervisor Documentation](http://supervisord.org/)
- [Redis Documentation](https://redis.io/documentation)
- [Laravel Reverb Documentation](https://laravel.com/docs/12.x/reverb)

---

## 🆘 Emergency Contacts

If something goes wrong:

1. **Stop all workers**: `sudo supervisorctl stop whatsapp-queue:*`
2. **Check logs**: `tail -f storage/logs/laravel.log`
3. **Clear queues**: `redis-cli FLUSHDB` (WARNING: loses queued jobs)
4. **Restart**: `sudo supervisorctl start whatsapp-queue:*`

---

**Last Updated**: 2026-03-08  
**Version**: 1.0.0
