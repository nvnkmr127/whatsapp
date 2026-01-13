<?php

namespace App\Livewire\Admin\Settings\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Component;

class CacheManagementSettings extends Component
{
    public array $cacheSizes = [];

    public string $loadingType = '';

    public ?bool $environment = false;

    public ?bool $enable_wp_log = false;

    public ?bool $production_mode = false;

    protected function rules()
    {
        return [
            'environment'     => ['nullable', 'boolean'],
            'enable_wp_log'   => ['nullable', 'boolean'],
            'production_mode' => ['nullable', 'boolean'],
        ];
    }

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }

        // Use config values instead of env for better caching support
        $this->environment = config('app.debug');
        // Check if whatsapp logging channel exists, otherwise fallback to env
        $this->enable_wp_log   = config('logging.channels.whatsapp.enabled', env('WHATSAPP_LOGGING_ENABLED') === 'true');
        $this->production_mode = config('app.env') !== 'local';

        $this->calculateSizes();
    }

    public function calculateSizes(): void
    {
        $directories = [
            'framework' => storage_path('framework/cache'),
            'views'     => storage_path('framework/views'),
            'config'    => base_path('bootstrap/cache'),
            'routing'   => base_path('bootstrap/cache'), // Route cache is stored here in Laravel 11
            'logs'      => storage_path('logs'),
        ];

        $this->cacheSizes = array_map(
            fn ($path) => $this->getDirectorySize($path),
            $directories
        );
    }

    public function clearCache(string $type): void
    {
        $this->loadingType = $type;

        try {
            match ($type) {
                'framework' => clear_cache(),
                'views'     => clear_view(),
                'config'    => clear_config(),
                'routing'   => $this->clearRouteCache(),
                'logs'      => $this->clearLogFiles(),
                default     => throw new \InvalidArgumentException("Invalid cache type: {$type}"),
            };

            // Update only the cleared cache size
            $this->cacheSizes[$type] = $this->getDirectorySize($this->getDirectoryPath($type));

            $this->notify([
                'type'    => 'success',
                'message' => Str::headline($type) . t('cache_cleared_successfully'),
            ]);
        } catch (\Exception $e) {
            report($e);
            $this->notify([
                'type'    => 'danger',
                'message' => t('failed_to_clear_cache') . ': ' . $e->getMessage(),
            ]);
        }

        $this->loadingType = '';
    }

    private function getDirectoryPath(string $type): string
    {
        return match ($type) {
            'framework' => storage_path('framework/cache'),
            'views'     => storage_path('framework/views'),
            'config'    => base_path('bootstrap/cache'),
            'routing'   => base_path('bootstrap/cache'), // Route cache is in bootstrap/cache
            'logs'      => storage_path('logs'),
            default     => throw new \InvalidArgumentException("Invalid cache type: {$type}"),
        };
    }

    private function getDirectorySize(string $path): string
    {
        if (! is_dir($path)) {
            return '0 B';
        }

        try {
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }

            return $this->formatSizeUnits($size);
        } catch (\Exception $e) {
            report($e);

            return '0 B';
        }
    }

    private function clearRouteCache(): bool
    {
        try {
            // Clear Laravel 11 route cache file
            $routeCacheFiles = [
                base_path('bootstrap/cache/routes-v7.php'),
                base_path('bootstrap/cache/routes.php'), // Also check for older format
            ];

            foreach ($routeCacheFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Also run the Artisan command to ensure complete cleanup
            Artisan::call('route:clear');

            return true;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    private function clearLogFiles(): void
    {
        $path = storage_path('logs');

        try {
            // Get all log files
            $files = File::glob("{$path}/*.log");
            // Preserve WhatsApp logs and today's Laravel log
            $keepFiles = [
                'whatsapp.log',
            ];

            foreach ($files as $file) {
                $fileName = basename($file);
                // Keep WhatsApp logs and specified log files
                if (! in_array($fileName, $keepFiles) && ! Str::startsWith($fileName, 'whats')) {
                    File::delete($file);
                }
            }
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    private function formatSizeUnits(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_073_741_824 => number_format($bytes / 1_073_741_824, 2) . ' GB',
            $bytes >= 1_048_576     => number_format($bytes / 1_048_576, 2) . ' MB',
            $bytes >= 1_024         => number_format($bytes / 1_024, 2) . ' KB',
            $bytes > 1              => "{$bytes} bytes",
            $bytes === 1            => '1 byte',
            default                 => '0 B',
        };
    }

    public function toggleEnvironment()
    {
        try {
            $this->environment = ! $this->environment;
            $this->updateEnvVariable('APP_DEBUG', $this->environment ? 'true' : 'false');

            $this->notify([
                'type'    => 'success',
                'message' => t('environment_updated'),
            ]);
        } catch (\Exception $e) {
            report($e);
            $this->notify([
                'type'    => 'danger',
                'message' => t('failed_to_update_environment') . ': ' . $e->getMessage(),
            ]);
        }
    }

    public function toggleEnableWpLog()
    {
        try {
            $this->enable_wp_log = ! $this->enable_wp_log;
            $this->updateEnvVariable('WHATSAPP_LOGGING_ENABLED', $this->enable_wp_log ? 'true' : 'false');

            $this->notify([
                'type'    => 'success',
                'message' => t('whatsapp_log_updated'),
            ]);
        } catch (\Exception $e) {
            report($e);
            $this->notify([
                'type'    => 'danger',
                'message' => t('failed_to_update_whatsapp_log_setting') . ': ' . $e->getMessage(),
            ]);
        }
    }

    public function toggleEnableProductionMode()
    {
        try {
            $this->production_mode = ! $this->production_mode;
            $this->updateEnvVariable('APP_ENV', $this->production_mode ? 'production' : 'local');

            $this->notify([
                'type'    => 'success',
                'message' => $this->production_mode
                    ? t('enable_production_mode_successfully')
                    : t('disable_production_mode_successfully'),
            ]);
        } catch (\Exception $e) {
            report($e);
            $this->notify([
                'type'    => 'danger',
                'message' => t('failed_to_update_production_mode') . ': ' . $e->getMessage(),
            ]);
        }
    }

    protected function updateEnvVariable(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            throw new \Exception('The .env file does not exist.');
        }

        if (! is_writable($path)) {
            throw new \Exception('The .env file is not writable.');
        }

        try {
            $content = file_get_contents($path);

            // Escape the key for regex
            $escapedKey = preg_quote($key, '/');

            // If the key exists, replace its value
            if (preg_match("/^{$escapedKey}=/m", $content)) {
                $content = preg_replace("/^{$escapedKey}=.*$/m", "{$key}={$value}", $content);
            } else {
                // If the key doesn't exist, add it
                $content .= PHP_EOL . "{$key}={$value}";
            }

            file_put_contents($path, $content);

            // Clear config cache to apply changes
            Artisan::call('config:clear');

            // Update the current environment variable for this request
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        } catch (\Exception $e) {
            report($e);
            throw new \Exception('Failed to update environment variable: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.cache-management-settings');
    }
}
