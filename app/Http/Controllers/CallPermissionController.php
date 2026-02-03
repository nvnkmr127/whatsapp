<?php

namespace App\Http\Controllers;

use App\Models\CallPermission;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CallPermissionController extends Controller
{
    /**
     * Request permission to call a contact
     * POST /api/whatsapp/calls/request-permission
     */
    public function requestPermission(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'contact_id' => 'required|exists:contacts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $contact = Contact::where('team_id', $team->id)
                ->where('id', $request->contact_id)
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'error' => 'Contact not found',
                ], 404);
            }

            $whatsappService = new WhatsAppService($team);
            $response = $whatsappService->requestCallPermission($request->contact_id);

            if (!($response['success'] ?? false)) {
                return response()->json($response, 400);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to request call permission', [
                'team_id' => $team->id,
                'contact_id' => $request->contact_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check permission status for a contact
     * GET /api/whatsapp/calls/permission/{contactId}
     */
    public function checkPermission(Request $request, int $contactId)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }

        try {
            $permission = CallPermission::where('contact_id', $contactId)
                ->where('team_id', $team->id)
                ->latest()
                ->first();

            if (!$permission) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_permission' => false,
                        'can_request' => true,
                        'message' => 'No permission record found',
                    ],
                ]);
            }

            $canRequest = $permission->canRequestPermission();
            $isWithinWindow = $permission->isWithinCallingWindow();

            return response()->json([
                'success' => true,
                'data' => [
                    'has_permission' => $isWithinWindow,
                    'can_request' => $canRequest,
                    'permission_status' => $permission->permission_status,
                    'permission_expires_at' => $permission->permission_expires_at,
                    'calls_made_count' => $permission->calls_made_count,
                    'requests_in_24h' => $permission->requests_in_24h,
                    'requests_in_7d' => $permission->requests_in_7d,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check call permission', [
                'team_id' => $team->id,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate a call with permission validation
     * POST /api/whatsapp/calls/initiate
     */
    public function initiateCall(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'contact_id' => 'required|exists:contacts,id',
            'permission_id' => 'sometimes|exists:call_permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $contact = Contact::where('team_id', $team->id)
                ->where('id', $request->contact_id)
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'error' => 'Contact not found',
                ], 404);
            }

            if ($request->filled('permission_id')) {
                $permission = CallPermission::where('team_id', $team->id)
                    ->where('id', $request->permission_id)
                    ->first();

                if (!$permission) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Permission not found',
                    ], 404);
                }
            }

            $whatsappService = new WhatsAppService($team);

            $response = $whatsappService->initiateCallWithPermission(
                $contact->phone,
                $request->permission_id,
                $request->only(['metadata'])
            );

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to initiate call', [
                'team_id' => $team->id,
                'contact_id' => $request->contact_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
