# 📊 New Relic Integration Guide

To use New Relic with your WhatsApp Business API application (Laravel), follow these steps to enable Application Performance Monitoring (APM), Error Tracking, and Infrastructure Monitoring.

---

## 🚀 I Have Already Configured:
1.  **Logging**: Added the `newrelic` channel to `config/logging.php` and enabled it in your `.env` stack.
2.  **Transaction Naming**: Created `App\Http\Middleware\NewRelicTransactionName` and registered it globally. This ensures you see `Controller@Action` names in your dashboard.
3.  **Environment**: Added placeholders for `NEWRELIC_LICENSE_KEY` and `NEWRELIC_APPNAME` in your `.env`.

---

## 🛠️ Remaining Steps (Action Required)

### 1. Server-Side Installation (The Agent)
New Relic requires a PHP extension installed on your server to collect data. 
**Run this on your Ubuntu/DigitalOcean production server:**
```bash
curl -Ls https://download.newrelic.com/install/newrelic-cli/scripts/install.sh | bash && sudo NEW_RELIC_API_KEY=YOUR_API_KEY NEW_RELIC_ACCOUNT_ID=YOUR_ACCOUNT_ID /usr/local/bin/newrelic install
```

### 2. Update Production `.env`
On your server, update your `.env` with your actual license key:
```env
NEWRELIC_LICENSE_KEY="your_actual_license_key"
NEWRELIC_APPNAME="Meta Solution Partner (Production)"
```

### 3. Restart PHP-FPM
After the agent is installed, restart PHP to enable the extension:
```bash
sudo systemctl restart php8.3-fpm
```

---

## 🔍 Monitoring Supervisor Queues
Since your app uses 30+ Supervisor workers, New Relic will automatically track these as "Non-web" transactions.

### **Important Note for Workers**
If your workers don't show up, ensure the PHP extension is enabled for the CLI. You can verify with:
```bash
php -m | grep newrelic
```

---

## ✅ Verifying Success
1.  **Check the Dashboard**: Log in to [New Relic Explorer](https://one.newrelic.com).
2.  **Watch the Traffic**: You should see "Meta Solution Partner" listed.
3.  **Check Errors**: Go to the "Errors" tab to see real-time Laravel exceptions.
4.  **Database**: Check "Databases" to see slow SQL queries.

---

**Current Status**: Code integration is complete. You only need to install the agent on the server.
