<?php

namespace App\Http\Controllers;

use App\Services\FlowEncryptionService;
use App\Services\WhatsAppFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowController extends Controller
{
    public function handle(Request $request)
    {
        try {
            // 1. Extract Encrypted Data
            $encryptedFlowData = $request->input('encrypted_flow_data');
            $encryptedAesKey = $request->input('encrypted_aes_key');
            $initialVector = $request->input('initial_vector');

            if (! $encryptedFlowData || ! $encryptedAesKey || ! $initialVector) {
                return response()->json(['error' => 'Missing encryption parameters'], 400);
            }

            // 2. Decrypt
            $encryptor = new FlowEncryptionService;
            $decryptedData = $encryptor->decryptRequest($encryptedFlowData, $encryptedAesKey, $initialVector);

            // 3. Process Logic
            $service = new WhatsAppFlowService;
            $responsePayload = $service->handleRequest($decryptedData);

            // 4. Encrypt Response
            $encryptedResponse = $encryptor->encryptResponse($responsePayload, $encryptedAesKey, $initialVector);

            return response($encryptedResponse, 200)
                ->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            Log::error('Flow Endpoint Error: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
