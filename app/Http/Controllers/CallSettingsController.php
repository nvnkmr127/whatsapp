<?php

namespace App\Http\Controllers;

use App\Models\CallSettings;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CallSettingsController extends Controller
{
    /**
     * Update call settings for a phone number
     * POST /api/whatsapp/{phoneNumberId}/settings
     */
    public function update(Request $request, string $phoneNumberId)
    {
        $team = $request->user()->currentTeam;

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:enabled,disabled',
            'call_icon_visibility' => 'sometimes|in:show,hide',
            'business_hours' => 'sometimes|array',
            'business_hours.timezone' => 'required_with:business_hours|string',
            'business_hours.hours' => 'required_with:business_hours|array',
            'business_hours.hours.*.day' => 'required|in:MON,TUE,WED,THU,FRI,SAT,SUN',
            'business_hours.hours.*.open' => 'required|date_format:H:i',
            'business_hours.hours.*.close' => 'required|date_format:H:i',
            'callback_permission_status' => 'sometimes|in:enabled,disabled',
            'sip' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update Meta's system settings
            $whatsappService = new WhatsAppService($team);
            $metaResponse = $whatsappService->updateSystemCallSettings($request->all());

            if (!($metaResponse['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update Meta settings',
                    'details' => $metaResponse,
                ], 500);
            }

            // Update local settings
            $settings = CallSettings::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'phone_number_id' => $phoneNumberId,
                ],
                [
                    'calling_enabled' => $request->input('status') === 'enabled',
                    'call_icon_visibility' => $request->input('call_icon_visibility', 'hide'),
                    'business_hours' => $request->input('business_hours'),
                    'callback_permission_enabled' => $request->input('callback_permission_status') === 'enabled',
                    'sip_config' => $request->input('sip'),
                ]
            );

            Log::info('Call settings updated', [
                'team_id' => $team->id,
                'phone_number_id' => $phoneNumberId,
                'settings' => $settings->only(['calling_enabled', 'call_icon_visibility']),
            ]);

            return response()->json([
                'success' => true,
                'data' => $settings->makeVisible(['sip_config']),
                'message' => 'Call settings updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update call settings', [
                'team_id' => $team->id,
                'phone_number_id' => $phoneNumberId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get call settings for a phone number
     * GET /api/whatsapp/{phoneNumberId}/settings
     */
    public function show(Request $request, string $phoneNumberId)
    {
        $team = $request->user()->currentTeam;
        $includeSip = $request->boolean('include_sip_credentials', false);

        try {
            // Get local settings
            $settings = CallSettings::where('team_id', $team->id)
                ->where('phone_number_id', $phoneNumberId)
                ->first();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'error' => 'Call settings not found',
                ], 404);
            }

            // Optionally fetch from Meta to ensure sync
            $whatsappService = new WhatsAppService($team);
            $metaResponse = $whatsappService->getSystemCallSettings();

            $data = $settings->toArray();

            // Include SIP config if requested
            if ($includeSip) {
                $data['sip_config'] = $settings->getSipConfiguration();
            } else {
                unset($data['sip_config']);
            }

            // Add Meta's settings for comparison
            if ($metaResponse['success'] ?? false) {
                $data['meta_settings'] = $metaResponse['data'];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get call settings', [
                'team_id' => $team->id,
                'phone_number_id' => $phoneNumberId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a call link
     * POST /api/whatsapp/calls/generate-link
     */
    public function generateLink(Request $request)
    {
        $team = $request->user()->currentTeam;

        try {
            $whatsappService = new WhatsAppService($team);
            $callLink = $whatsappService->generateCallLink();

            return response()->json([
                'success' => true,
                'data' => [
                    'call_link' => $callLink,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
