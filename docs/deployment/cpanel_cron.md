# cPanel Cron Job Setup

For shared hosting where you cannot run a persistent `supervisor` process, we use the Laravel Scheduler to handle background jobs.

## 1. Configure the Cron Job

Log in to your cPanel and go to **Cron Jobs**.

Add a new Cron Job with the following settings:

- **Common Settings**: Once Per Minute (`* * * * *`)
- **Command**:
  ```bash
  cd /home/tribebel/digichatify.tribebella.com && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
  ```
  > **Note**: This command assumes your username is `tribebel` and the PHP path is `/usr/local/bin/php` based on your provided example.
  > If your PHP path is different, check the "Cron Jobs" page sidebar for the exact path.

## 2. Verify Queue Configuration

In your `.env` file (on the server), ensure the queue connection is set to `database`:

```ini
QUEUE_CONNECTION=database
```

## 3. How it Works

1. The cPanel Cron Job runs `php artisan schedule:run` every minute.
2. We have configured the scheduler in `routes/console.php` to run `queue:work --stop-when-empty` every minute.
3. This will process any pending jobs (like sending WhatsApp messages, processing webhooks) and then exit.
4. This approach ensures background jobs run reliably without hitting process limits common on shared hosting.
