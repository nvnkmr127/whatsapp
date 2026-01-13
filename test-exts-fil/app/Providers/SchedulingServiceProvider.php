<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class SchedulingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->booted(function () {

            $schedule = $this->app->make(Schedule::class);
            $timezone = get_setting('general.timezone', config('app.timezone'));

            // Run chat history cleanup daily at midnight
            $schedule->command('whatsapp:clear-chat-history')
                ->daily()
                ->timezone($timezone)
                ->before(function () {
                    try {
                        set_setting('cron-job.status', 'running');
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status before execution', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                ->after(function () {
                    try {
                        set_setting('cron-job.status', 'completed');
                        set_setting('cron-job.last_cron_run', now()->timestamp);
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status after execution', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                ->onFailure(function () {
                    try {
                        set_setting('cron-job.status', 'failed');
                        whatsapp_log('Cron job failed for clear-chat-history: whatsapp:clear-chat-history', 'error');
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status on failure', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                });

            // Run every minute to check for campaigns that need to be sent
            $schedule->command('campaigns:process-scheduled')
                ->everyMinute()
                ->timezone($timezone)
                ->before(function () {
                    try {
                        set_setting('cron-job.status', 'running');
                        // Store start time for duration calculation
                        set_setting('cron-job.job_start_time', now()->timestamp);
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status before execution', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                ->after(function () {
                    try {
                        $startTime     = (int) get_setting('cron-job.job_start_time', 0);
                        $endTime       = now()->timestamp;
                        $executionTime = $endTime - $startTime;

                        set_setting('cron-job.status', 'completed');
                        set_setting('cron-job.last_cron_run', $endTime);
                        set_setting('cron-job.last_execution_time', $executionTime);
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status after execution', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                ->onFailure(function () {
                    try {
                        set_setting('cron-job.status', 'failed');
                        whatsapp_log('Cron job failed for campaigns:process-scheduled: campaigns:process-scheduled', 'error');
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status on failure', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                });

            // Run every minute to check for campaigns that need to be sent
            $schedule->command('whatsmark:check-updates')->daily()
                ->timezone($timezone)
                ->before(function () {
                    try {
                        set_setting('cron-job.status', 'running');
                        // Store start time for duration calculation
                        set_setting('cron-job.job_start_time', now()->timestamp);
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status before execution', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                ->after(function () {
                    try {
                        $startTime     = (int) get_setting('cron-job.job_start_time', 0);
                        $endTime       = now()->timestamp;
                        $executionTime = $endTime - $startTime;

                        set_setting('cron-job.status', 'completed');
                        set_setting('cron-job.last_cron_run', $endTime);
                        set_setting('cron-job.last_execution_time', $executionTime);
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status after execution', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                ->onFailure(function () {
                    try {
                        set_setting('cron-job.status', 'failed');
                        whatsapp_log('Cron job failed for whatsmark:check-updates', 'error');
                    } catch (\Exception $e) {
                        Log::error('Failed to update cron job status on failure', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                });

            $schedule->command('queue:work --queue=whatsapp-messages --stop-when-empty --sleep=3 --tries=3 --timeout=60 --backoff=5 --max-time=3600 --max-jobs=100')
                ->withoutOverlapping()
                ->everyMinute();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
