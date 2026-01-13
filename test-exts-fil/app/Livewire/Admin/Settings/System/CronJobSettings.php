<?php

namespace App\Livewire\Admin\Settings\System;

use App\Settings\CronJobSettings as CronSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Symfony\Component\Process\PhpExecutableFinder;

class CronJobSettings extends Component
{
    public ?string $last_cron_run = '';

    public ?string $last_cron_run_datetime = '';

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $settings    = app(CronSettings::class);
        $lastCronRun = $settings->last_cron_run;

        if ($lastCronRun && $lastCronRun !== 'false') {
            $timestamp = is_numeric($lastCronRun) ? intval($lastCronRun) : json_decode($lastCronRun);
            if ($timestamp) {
                $timezone = get_setting('general.timezone', config('app.timezone'));

                $this->last_cron_run = Carbon::createFromTimestamp($timestamp)
                    ->setTimezone($timezone)
                    ->diffForHumans();

                $this->last_cron_run_datetime = Carbon::createFromTimestamp($timestamp)
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s');
            } else {
                $this->last_cron_run = t('never');
            }
        } else {
            $this->last_cron_run = t('never');
        }
    }

    public function getPrepareCronUrlProperty()
    {
        return sprintf(
            '%s %s/artisan schedule:run >> /dev/null 2>&1',
            (new PhpExecutableFinder)->find(false),
            base_path()
        );
    }

    public function save() {}

    public function runCronManually()
    {
        try {
            // Update status to running
            set_setting('cron-job.status', 'running');

            // Store start time
            $startTime = now()->timestamp;
            set_setting('cron-job.job_start_time', $startTime);

            whatsapp_log('Manual cron job execution start', 'info', [
                'start_time' => $startTime,
                'user_id'    => auth()->id(),
            ]);

            // Run the main scheduler
            Artisan::call('schedule:run');

            // Optional: Run specific commands if needed
            Artisan::call('campaigns:process-scheduled');
            Artisan::call('queue:work --queue=whatsapp-messages --stop-when-empty --sleep=3 --tries=3 --timeout=120 --backoff=5 --max-time=3600 --max-jobs=100');

            // Calculate execution time
            $endTime       = now()->timestamp;
            $executionTime = $endTime - $startTime;

            // Update settings
            $now = now();

            // Update additional settings
            set_setting('cron-job.status', 'completed');
            set_setting('cron-job.last_execution_time', $executionTime);
            set_setting('cron-job.last_cron_run', $endTime);

            // Update UI properties
            $this->last_cron_run          = $now->diffForHumans();
            $this->last_cron_run_datetime = $now->format('Y-m-d H:i:s');

            // Log success
            whatsapp_log('Manual cron job execution over', 'info', [
                'execution_time' => $executionTime,
                'user_id'        => auth()->id(),
            ]);

            $this->notify([
                'type'    => 'success',
                'message' => t('cron_job_executed_successfully'),
            ]);
        } catch (\Exception $e) {
            // Update status to failed
            set_setting('cron-job.status', 'failed');

            // Log error
            whatsapp_log('Manual cron job execution failed', 'error', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ], $e);

            $this->notify([
                'type'    => 'danger',
                'message' => t('failed_to_execute_cron_job') . ': ' . $e->getMessage(),
            ]);
        }
    }

    public function isCronStale(): bool
    {
        $settings = app(CronSettings::class);
        $lastRun  = $settings->last_cron_run;

        if (! $lastRun || $lastRun === 'false') {
            // Update status to reflect stale condition
            set_setting('cron-job.status', 'failed');

            return true;
        }

        $timestamp = (int) json_decode($lastRun);
        $isStale   = Carbon::createFromTimestamp($timestamp)->diffInHours(now()) >= 48;

        // If cron is stale but status shows completed, update status
        if ($isStale && get_setting('cron-job.status', '') === 'completed') {
            set_setting('cron-job.status', 'failed');
        }

        return $isStale;
    }

    public function render()
    {
        return view('livewire.admin.settings.system.cron-job-settings');
    }
}
