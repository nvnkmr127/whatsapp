<?php

namespace App\Console\Commands;

use App\Models\WhatsappTemplate;
use App\Validators\TemplateValidator;
use Illuminate\Console\Command;

class AuditTemplateCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:audit-compliance {--fix : Auto-fix manageable issues (not implemented yet)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all local templates for Meta Policy Compliance violations.';

    /**
     * Execute the console command.
     */
    public function handle(TemplateValidator $validator)
    {
        $this->info("Starting Compliance Audit...");

        $templates = WhatsappTemplate::all();
        $bar = $this->output->createProgressBar($templates->count());
        $violations = [];
        $stats = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0
        ];

        foreach ($templates as $template) {
            $stats['total']++;
            $result = $validator->validate($template);

            if (!$result->isValid()) {
                $stats['failed']++;

                $issues = [];
                foreach ($result->getErrors() as $error) {
                    $issues[] = "[{$error->code}] {$error->message}";
                }

                $violations[] = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category' => $template->category,
                    'status' => $template->status,
                    'is_paused' => $template->is_paused ? 'YES' : 'NO',
                    'issues' => implode(' | ', $issues)
                ];
            } else {
                $stats['passed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Audit Complete.");
        $this->table(['Total', 'Passed', 'Failed/Warn'], [
            [$stats['total'], $stats['passed'], $stats['failed']]
        ]);

        if (count($violations) > 0) {
            $this->error("Found " . count($violations) . " templates with compliance risks:");
            $this->table(
                ['ID', 'Name', 'Category', 'Status', 'Paused', 'Issues'],
                $violations
            );
        } else {
            $this->info("Zero compliance violations found! Great job.");
        }

        return 0;
    }
}
