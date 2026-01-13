<?php

namespace Corbital\Installer\Classes;

// use Illuminate\Support\Facades\Config;
// use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Corbital\Installer\Classes\EnvironmentManager;

use ZipArchive;

class UpdateChecker
{
    public $url;

    public function __construct()
    {
        $environment = new EnvironmentManager;
        $this->url   = $environment->guessUrl();
    }

    public function installVersion($data)
    {
        $update = $this->checkUpdate($data['token']);

        if ($update['success'] == true) {
            $download = $this->downloadUpdate($update['data']['update_id'], $update['data']['has_sql_update'], $update['data']['latest_version'], $data['token'], $data['purchase_code'], $data['username']);
            if ($download['success'] == true) {
                set_settings_batch('whats-mark', [
                    'wm_version'            => $update['data']['latest_version'],
                    'wm_verification_id'    => base64_encode($data['verification_id']),
                    'wm_verification_token' => base64_encode($data['verification_id']) . '|' . $data['token'],
                    'wm_last_verification'  => now()->timestamp,
                    'wm_support_until'      => $data['support_until'],
                ]);
            }
        }
    }

    public function checkUpdate($token, $mode = 'install')
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->post(rtrim(base64_decode(config('installer.license_verification.api_endpoint')), '/') . '/check-update', [
                'item_id' => config('installer.license_verification.product_id'),
                'version' => config('installer.license_verification.current_version'),
                'initial' => true,
                'mode'    => $mode,
            ]);

        return json_decode($response->getBody(), true);
    }

    public function downloadUpdate(
        string $updateId,
        bool $needsSqlUpdate,
        string $version,
        string $token,
        ?string $license = null,
        ?string $client = null,
        ?string $mode = 'install'
    ) {
        try {
            $mainFile = $this->downloadFile('main', $updateId, $version, $token, $license, $client);

            if (isset($mainFile['success'])) {
                return [
                    'success' => false,
                    'message' => $mainFile['message'],
                ];
            }
            $this->extractUpdate($mainFile, 'main', $mode);

            if ($needsSqlUpdate) {
                $sqlFile = $this->downloadFile('sql', $updateId, $version, $token, $license, $client);
                $this->extractUpdate($sqlFile, 'sql', $mode);
            }

            return [
                'success' => true,
                'message' => 'Update completed',
            ];
        } catch (\Throwable $e) {

            Log::error('Update failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function downloadFile(string $type, string $updateId, string $version, string $token, ?string $license, ?string $client)
    {
        $destination = config('installer.license_verification.root_path') . "/update_{$type}_{$version}.zip";

        if (! is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }

        $response = Http::timeout(60)
            ->withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->sink($destination)
            ->post(rtrim(base64_decode(config('installer.license_verification.api_endpoint')), '/') . "/download-update/$type/$updateId", [
                'license_code'     => $license,
                'client_name'      => $client,
                'activated_domain' => $this->url,
            ]);

        $responseData = $response->json();

        // If validation failed
        if (isset($responseData['success']) && $responseData['success'] != true) {
            $errorMessages = $responseData['errors'] ?? [$responseData['message'] ?? 'License validation failed'];

            if (is_array($errorMessages) && isset($errorMessages['license_code'])) {
                return [
                    'success' => $response['success'],
                    'message' => $errorMessages['license_code'][0],
                ];
            }

            return [
                'success' => $response['success'],
                'message' => $response['message'],
            ];
        }

        return $destination;
    }

    private function extractUpdate(string $zipFile, string $type, string $mode): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Failed to open update file');
        }

        // Define extraction path
        $extractPath = base_path();

        // Ensure directory exists
        if (! File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        // Extract ZIP file
        $zip->extractTo($extractPath);
        $zip->close();

        // Delete the original ZIP file after extraction
        File::delete($zipFile);

        // Handle SQL type (extract and import SQL)
        if ($type === 'sql') {
            $this->importSQLFromExtractedFiles($extractPath);
            if ($mode != 'install') {
                Artisan::call('db:seed', ['--force' => true]);
                Artisan::call('migrate', ['--force' => true]);
            }
        }
    }

    /**
     * Check for SQL file and import it into the database.
     */
    private function importSQLFromExtractedFiles(string $extractPath): void
    {
        $sqlFile = null;

        // Search for an SQL file in the extracted directory
        foreach (scandir($extractPath) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $sqlFile = $extractPath . '/' . $file;
                break;
            }
        }

        if (! $sqlFile) {
            throw new \RuntimeException('No SQL file found in extracted update.');
        }

        // Read and execute the SQL file
        try {
            $sql = file_get_contents($sqlFile);
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::unprepared($sql);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Delete SQL file after successful import
            File::delete($sqlFile);

        } catch (\Exception $e) {
            throw new \RuntimeException('SQL import failed: ' . $e->getMessage());
        }
    }

    public function checkSupportExpiryStatus($supportedUntil = '')
    {
        if ($supportedUntil) {
            $supportedDate = Carbon::parse($supportedUntil)->addDay();
            $currentDate   = Carbon::now();

            if ($currentDate->greaterThanOrEqualTo($supportedDate)) {
                return [
                    'success'     => false,
                    'type'        => 'danger',
                    'message'     => 'Support has already expired.',
                    'time_diff'   => '',
                    'support_url' => trim(base64_decode(config('installer.license_verification.support_url'))),
                ];
            }

            $timeDiff = $currentDate->diff($supportedDate)->format('%m months %d days');

            return [
                'success'     => true,
                'type'        => 'success',
                'message'     => "Support will expire on {$supportedDate->format('d M, Y')} ({$timeDiff}).",
                'time_diff'   => "{$timeDiff} left",
                'support_url' => trim(base64_decode(config('installer.license_verification.support_url'))),
            ];
        }

        return [];
    }

    public function getVersionLog()
    {
        $item_id = config('installer.license_verification.product_id');

        $response = Http::timeout(60)
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post(rtrim(base64_decode(config('installer.license_verification.api_endpoint')), '/') . "/products/$item_id");

        return json_decode($response->getBody(), true);
    }

    public function validateRequest()
    {
        $token           = explode('|', get_setting('whats-mark.wm_verification_token'))[1];
        $verification_id = ! empty(get_setting('whats-mark.wm_verification_id')) ? base64_decode(get_setting('whats-mark.wm_verification_id')) : '';

        $id_data  = explode('|', $verification_id);
        $verified = ! ((empty($verification_id)) || (4 != \count($id_data)));

        if (4 === \count($id_data)) {
            $verified = ! empty($token);
            try {

                $data = json_decode(base64_decode(explode('.', $token)[0]));

                if (! empty($data)) {
                    $verified = $data->item_id == config('installer.license_verification.product_id') && $data->item_id == $id_data[0] && $data->buyer == $id_data[2] && $data->purchase_code == $id_data[3];
                }

            } catch (\Exception $e) {
                $verified = false;
            }

            $last_verification = (int) get_setting('whats-mark.wm_last_verification');
            $seconds           = $data->check_interval ?? 0;
            if (! empty($seconds) && time() > ($last_verification + $seconds)) {
                $verified = false;
                try {
                    $response = Http::timeout(60)
                        ->withHeaders([
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json',
                            'Authorization' => 'Bearer ' . $token,
                        ])
                        ->post(rtrim(base64_decode(config('installer.license_verification.api_endpoint')), '/') . '/validate', [
                            'verification_id'  => $verification_id,
                            'item_id'          => config('installer.license_verification.product_id'),
                            'activated_domain' => $this->url,
                            'version'          => config('installer.license_verification.current_version'),
                            'purchase_code'    => $id_data[3],
                        ]);

                    $result = json_decode($response->getBody(), true);

                    $verified = $result['success'] ?? false;
                    set_setting('whats-mark.wm_validate', $verified);
                } catch (\Exception $e) {
                    $verified = false;
                }
                set_setting('whats-mark.wm_last_verification', now()->timestamp);
            }
        }

        return $verified;
    }
}
