<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class ExternalTemplateController extends Controller
{
    /**
     * List all available WhatsApp templates for the authenticated team.
     * GET /api/v1/templates
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            return response()->json(['error' => 'No team context'], 400);
        }

        try {
            $whatsappService = new WhatsAppService();
            $whatsappService->setTeam($team);

            $result = $whatsappService->getTemplates();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'templates' => $result['data']['data'] ?? [],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch templates',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
