<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\RSA;

class FlowEncryptionService
{
    private $privateKey;

    public function __construct()
    {
        $keyPath = config('whatsapp.flow_private_key_path');
        if (file_exists($keyPath)) {
            $this->privateKey = file_get_contents($keyPath);
        }
    }

    /**
     * Decrypt the request from Meta.
     */
    public function decryptRequest($encryptedFlowData, $encryptedAesKey, $initialVector)
    {
        if (!$this->privateKey) {
            throw new \Exception("Flow Private Key not found. Please generate keys.");
        }

        try {
            // 1. Decrypt the AES Key using our Private Key
            $privateKey = RSA::load($this->privateKey);

            // Meta uses RSA-OAEP-256
            $decryptedAesKey = $privateKey->withHash('sha256')->withMGFHash('sha256')->decrypt(base64_decode($encryptedAesKey));

            // 2. Decrypt the Payload using the AES Key
            // AES-GCM 128 bit
            $encryptedFlowDataIdx = base64_decode($encryptedFlowData);
            $initialVectorIdx = base64_decode($initialVector);

            // Tag is the last 16 bytes of the encrypted data
            $tagLength = 16;
            $dataLength = strlen($encryptedFlowDataIdx) - $tagLength;
            $ciphertext = substr($encryptedFlowDataIdx, 0, $dataLength);
            $tag = substr($encryptedFlowDataIdx, $dataLength);

            $decryptedJSON = openssl_decrypt(
                $ciphertext,
                'aes-128-gcm',
                $decryptedAesKey,
                OPENSSL_RAW_DATA,
                $initialVectorIdx,
                $tag
            );

            if ($decryptedJSON === false) {
                throw new \Exception("OpenSSL Decryption failed.");
            }

            return json_decode($decryptedJSON, true);

        } catch (\Exception $e) {
            Log::error("Flow Decryption Error: " . $e->getMessage());
            throw new \Exception("Decryption failed: " . $e->getMessage());
        }
    }

    /**
     * Encrypt the response for Meta.
     */
    public function encryptResponse($response, $encryptedAesKey, $initialVector)
    {
        if (!$this->privateKey) {
            throw new \Exception("Flow Private Key not found.");
        }

        try {
            // 1. Decrypt AES Key again (or reuse if we passed it, but stateless is safer)
            $privateKey = RSA::load($this->privateKey);
            $decryptedAesKey = $privateKey->withHash('sha256')->withMGFHash('sha256')->decrypt(base64_decode($encryptedAesKey));

            // 2. Generate new IV (Meta recommends inverting bits of request IV, or random)
            // Simpler: Flip bits of existing IV
            $initialVectorIdx = base64_decode($initialVector);
            $invertedIv = ~$initialVectorIdx;

            // 3. Encrypt Response
            $tag = "";
            $encryptedResponse = openssl_encrypt(
                json_encode($response),
                'aes-128-gcm',
                $decryptedAesKey,
                OPENSSL_RAW_DATA,
                $invertedIv,
                $tag
            );

            // Append tag to ciphertext
            $payload = $encryptedResponse . $tag;

            return base64_encode($payload);

        } catch (\Exception $e) {
            Log::error("Flow Encryption Error: " . $e->getMessage());
            throw new \Exception("Encryption failed.");
        }
    }
}
