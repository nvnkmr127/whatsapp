<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Contact;
use App\Services\AutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckBirthdayTriggers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automations:check-birthdays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fire birthday automation triggers for contacts celebrating today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking birthday triggers for '.now()->toDateString());

        // Find automations with birthday trigger
        $automations = Automation::where('is_active', true)
            ->where('trigger_type', 'contact_birthday')
            ->get();

        if ($automations->isEmpty()) {
            $this->info('No active birthday automations found.');

            return;
        }

        $service = app(AutomationService::class);
        $todayMonth = now()->month;
        $todayDay = now()->day;

        foreach ($automations as $automation) {
            $fieldName = $automation->trigger_config['field_name'] ?? 'birthday';
            $daysBefore = (int) ($automation->trigger_config['days_before'] ?? 0);

            $targetDate = now()->addDays($daysBefore);
            $targetMonth = $targetDate->month;
            $targetDay = $targetDate->day;

            $this->info("Processing automation #{$automation->id} (targeting {$targetMonth}-{$targetDay})");

            // Look in core fields (if they exist) or custom_attributes
            Contact::where('team_id', $automation->team_id)
                ->where(function ($q) {
                    // This is a bit complex due to JSON custom_attributes
                    // We'll fetch all and filter for now, or use JSON query if DB supports it.
                    // Assuming standard Laravel whereJsonContains or similar is available.
                })
                ->chunk(100, function ($contacts) use ($service, $automation, $fieldName, $targetMonth, $targetDay) {
                    foreach ($contacts as $contact) {
                        $dobValue = $contact->{$fieldName} ?? ($contact->custom_attributes[$fieldName] ?? null);
                        if (! $dobValue) {
                            continue;
                        }

                        try {
                            $dob = \Carbon\Carbon::parse($dobValue);
                            if ($dob->month === $targetMonth && $dob->day === $targetDay) {
                                // Check if already run THIS YEAR to prevent double firing
                                $alreadyRun = $contact->automationRuns()
                                    ->where('automation_id', $automation->id)
                                    ->where('created_at', '>', now()->startOfYear())
                                    ->exists();

                                if (! $alreadyRun) {
                                    $service->start($automation, $contact);
                                    $this->info("Triggered birthday automation for contact #{$contact->id}");
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error("Birthday trigger date parsing failed for contact {$contact->id}: ".$e->getMessage());
                        }
                    }
                });
        }

        $this->info('Birthday check complete.');
    }
}
