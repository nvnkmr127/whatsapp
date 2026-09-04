<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class ExternalTemplateController extends Controller
{
    use StandardApiResponses;

    public function __construct(
        protected WhatsAppService $whatsappService
    ) {}

    /**
     * List all available WhatsApp templates for the authenticated team.
     * GET /api/v1/templates
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (! $team) {
            return $this->error('No team context selected.', 400);
        }

        try {
            // First check local synced templates
            $localTemplates = \App\Models\WhatsappTemplate::where('team_id', $team->id)->get();

            if ($localTemplates->isNotEmpty() && ! $request->boolean('force_refresh')) {
                return $this->success(
                    $this->mapTemplates($localTemplates),
                    'Templates retrieved successfully.'
                );
            }

            // Live fallback from Meta API
            $this->whatsappService->setTeam($team);
            $result = $this->whatsappService->getTemplates();

            if ($result['success'] ?? false) {
                $templates = isset($result['data']['data']) ? $result['data']['data'] : ($result['data'] ?? []);
                return $this->success(
                    $templates,
                    'Templates retrieved successfully.'
                );
            }

            if ($localTemplates->isNotEmpty()) {
                return $this->success($this->mapTemplates($localTemplates), 'Templates retrieved successfully.');
            }

            return $this->error($result['message'] ?? 'Failed to fetch templates', 500);

        } catch (\Exception $e) {
            $localTemplates = \App\Models\WhatsappTemplate::where('team_id', $team->id)->get();
            if ($localTemplates->isNotEmpty()) {
                return $this->success($this->mapTemplates($localTemplates), 'Templates retrieved successfully.');
            }
            return $this->error($e->getMessage(), 500);
        }
    }

    private function mapTemplates($templates)
    {
        return $templates->map(fn($t) => $this->mapTemplate($t));
    }

    private function mapTemplate($t): array
    {
        return [
            'id' => $t->id,
            'template_id' => $t->whatsapp_template_id,
            'name' => $t->name,
            'language' => $t->language,
            'category' => $t->category,
            'status' => $t->status,
            'components' => $t->components,
        ];
    }

    /**
     * Get a specific template by ID or name.
     * GET /api/v1/templates/{id}
     */
    public function show(Request $request, string $id)
    {
        $team = $request->user()->currentTeam;

        if (! $team) {
            return $this->error('No team context selected.', 400);
        }

        $template = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                    ->orWhere('whatsapp_template_id', $id)
                    ->orWhere('name', $id);
            })
            ->first();

        if (! $template) {
            return $this->error('Template not found.', 404);
        }

        return $this->success($this->mapTemplate($template), 'Template retrieved successfully.');
    }
}
