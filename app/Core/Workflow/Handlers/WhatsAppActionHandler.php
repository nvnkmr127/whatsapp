<?php

namespace App\Core\Workflow\Handlers;

use App\Core\Workflow\ActionHandlerInterface;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WhatsAppActionHandler implements ActionHandlerInterface
{
    public function handle(array $config, Model $subject, array $context): void
    {
        $templateName = $config['template_name'] ?? null;
        if (! $templateName) {
            return;
        }

        $team = $subject->team ?? $subject->currentTeam ?? null;
        if (! $team && method_exists($subject, 'ownedTeams')) {
            $team = $subject->ownedTeams()->first();
        }

        $to = $config['to'] ?? ($subject instanceof Contact ? $subject->phone_number : ($subject->phone ?? null));

        if ($team && $to) {
            try {
                // Variable resolution
                $bodyParams = [];
                foreach (($config['variables'] ?? []) as $var) {
                    if ($var === '{name}') {
                        $bodyParams[] = $subject->name ?? 'User';
                    } else {
                        $bodyParams[] = $var;
                    }
                }

                (new WhatsAppService($team))->sendTemplate(
                    $to,
                    $templateName,
                    $config['language'] ?? 'en_US',
                    $bodyParams
                );
                Log::info("Workflow sent WhatsApp template {$templateName} to {$to}");
            } catch (\Exception $e) {
                Log::error('Workflow failed to send WhatsApp template: '.$e->getMessage());
            }
        }
    }
}
