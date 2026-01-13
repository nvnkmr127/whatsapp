<?php

namespace App\Console\Commands;

use App\DTOs\WhatsAppMessage;
use App\Jobs\SendWhatsAppMessage;
use Faker\Factory as Faker;
use Illuminate\Console\Command;

class GenerateWhatsAppTestJobs extends Command
{
    protected $signature = 'whatsapp:generate-test-jobs {count=5 : Number of test jobs to generate}';

    protected $description = 'Generate test WhatsApp jobs';

    protected array $phoneNumbers = [
        '9909919284',
        '9925100866',
        '9925119284',
        '9016076449',
        '9313461810',
    ];

    public function handle()
    {
        $count = $this->argument('count');
        $faker = Faker::create();

        // Using a single template for testing
        $template = 'hello_world';

        $this->info("Generating {$count} test WhatsApp jobs...");

        $bar = $this->output->createProgressBar($count);

        for ($i = 0; $i < $count; $i++) {
            // Get phone number by cycling through the array
            $phoneIndex  = $i % count($this->phoneNumbers);
            $phoneNumber = $this->phoneNumbers[$phoneIndex];

            $message = new WhatsAppMessage(
                to: $phoneNumber,
                template: $template,
                parameters: [
                    '1' => $faker->name,
                ]
            );

            SendWhatsAppMessage::dispatch($message)
                ->onQueue('whatsapp-messages');

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info('Test jobs generated successfully!');

        // Show jobs count in database
        $jobsCount = \DB::table('jobs')->count();
        $this->info("Total jobs in queue: {$jobsCount}");

        // Show distribution of phone numbers
        $this->newLine();
        $this->info('Jobs distribution by phone number:');
        $distribution = \DB::table('jobs')
            ->where('queue', 'whatsapp-messages')
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $command = unserialize(data_get($payload, 'data.command'));

                return $command->message->to ?? 'N/A';
            })
            ->countBy()
            ->toArray();

        foreach ($distribution as $phone => $count) {
            $this->info("Phone: {$phone} - Jobs: {$count}");
        }
    }
}
