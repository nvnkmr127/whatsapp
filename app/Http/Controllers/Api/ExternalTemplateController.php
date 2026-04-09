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
            $this->whatsappService->setTeam($team);
            $result = $this->whatsappService->getTemplates();

            if ($result['success'] ?? false) {
                return $this->success(
                    $result['data']['data'] ?? [],
                    'Templates retrieved successfully.'
                );
            }

            return $this->error($result['message'] ?? 'Failed to fetch templates', 500);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
