<?php

namespace App\Console\Commands;

use Corbital\Installer\Classes\UpdateChecker;
use Illuminate\Console\Command;

class CheckWhatsMarkUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsmark:check-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for WhatsMarks updates and store the latest version in settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Get token from settings
            $settings = explode('|', get_setting('whats-mark.wm_verification_token'));
            $token    = $settings[0];

            if (empty($token)) {
                set_setting('whats-mark.whatsmark_latest_version', null);
                $this->error('WhatsMarks API token not found in settings');

                return 1;
            }

            // Initialize update checker
            $update_checker = new UpdateChecker;

            // Check for updates
            $update_data = $update_checker->checkUpdate($token, 'update');

            if (! isset($update_data['data']) || ! isset($update_data['data']['latest_version'])) {
                set_setting('whats-mark.whatsmark_latest_version', null);
                $this->error('Invalid response from update server');

                return 1;
            }

            // Get the latest version
            $latest_version = $update_data['data']['latest_version'];

            // Store in settings
            set_setting('whats-mark.whatsmark_latest_version', $latest_version);

            $this->info("WhatsMarks update check completed. Latest version: {$latest_version}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking updates: ' . $e->getMessage());

            return 1;
        }
    }
}
