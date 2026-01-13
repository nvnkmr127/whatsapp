<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ListWhatsAppTemplates extends Command
{
    protected $signature = 'whatsapp:list-templates';

    protected $description = 'List all available WhatsApp templates';

    public function handle(): int
    {
        try {
            $accountId   = get_setting('whatsapp.wm_business_account_id');
            $accessToken = get_setting('whatsapp.wm_access_token');

            $response = Http::get("https://graph.facebook.com/v18.0/{$accountId}/message_templates", [
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                throw new \Exception($response->body());
            }

            $templates = $response->json('data', []);

            if (empty($templates)) {
                $this->info('No templates found.');

                return self::SUCCESS;
            }

            // Display templates in a table
            $rows = [];
            foreach ($templates as $template) {
                $rows[] = [
                    $template['name'],
                    $template['language'] ?? 'N/A',
                    $template['status']   ?? 'N/A',
                    $template['category'] ?? 'N/A',
                ];
            }

            $this->table(
                ['Template Name', 'Language', 'Status', 'Category'],
                $rows
            );

            $this->newLine();
            $this->info('Total templates: ' . count($templates));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
