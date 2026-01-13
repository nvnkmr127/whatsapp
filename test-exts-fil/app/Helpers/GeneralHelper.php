<?php

use App\Models\EmailTemplate;
use App\Services\MergeFields;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

if (! function_exists('format_currency')) {
    /**
     * Format currency based on system settings
     *
     * @param  float  $amount Amount to format
     * @return string Formatted currency string
     */
    function format_currency(float $amount): string
    {
        $symbol            = settings('system.currency_symbol', '$');
        $position          = settings('system.currency_position', 'right');
        $decimals          = settings('system.decimal_places', 2);
        $decimalSeparator  = settings('system.decimal_separator', '.');
        $thousandSeparator = settings('system.thousand_separator', ',');

        $formattedAmount = number_format(
            $amount,
            $decimals,
            $decimalSeparator,
            $thousandSeparator
        );

        return $position === 'left'
            ? "{$symbol}{$formattedAmount}"
            : "{$formattedAmount}{$symbol}";
    }
}

if (! function_exists('format_date')) {
    /**
     * Format date according to system settings
     *
     * @param  string|Carbon $date   Date to format
     * @param  string|null   $format Custom format (optional)
     * @return string        Formatted date
     */
    function format_date(string|Carbon $date, ?string $format = null): string
    {
        $format ??= settings('system.date_format', 'Y-m-d');

        return Carbon::parse($date)->format($format);
    }
}

if (! function_exists('format_time')) {
    /**
     * Format time according to system settings
     *
     * @param  string|Carbon $time   Time to format
     * @param  string|null   $format Custom format (optional)
     * @return string        Formatted time
     */
    function format_time(string|Carbon $time, ?string $format = null): string
    {
        $format ??= settings('system.time_format', 'H:i');

        return Carbon::parse($time)->format($format);
    }
}

if (! function_exists('format_date_time')) {
    function format_date_time($dateTime)
    {
        $timezone   = get_setting('general.timezone', config('app.timezone'));
        $dateFormat = get_setting('general.date_format', config('app.date_format'));
        $timeFormat = get_setting('general.time_format') == '12' ? 'h:i A' : 'H:i';

        return Carbon::parse($dateTime)
            ->setTimezone($timezone)
            ->format("$dateFormat $timeFormat");
    }
}

if (! function_exists('to_sql_date')) {
    /**
     * Convert string to SQL date based on current date format from settings
     *
     * @param  string      $date     Date string
     * @param  bool        $datetime Include time in conversion
     * @return string|null SQL formatted date(time)
     */
    function to_sql_date(string $date, bool $datetime = false): ?string
    {
        if (empty($date)) {
            return null;
        }

        $to_date     = 'Y-m-d';
        $from_format = get_setting('system.date_format', 'Y-m-d');

        try {
            // Check if already in Y-m-d format
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date)) {
                return $datetime ? $date . ' 00:00:00' : $date;
            }

            if (! $datetime) {
                return Carbon::createFromFormat($from_format, $date)->format($to_date);
            }

            // Handle datetime conversion
            if (strpos($date, ' ') === false) {
                $date .= ' 00:00:00';
            } else {
                $is24Hour = get_setting('system.time_format', 'H:i') === 'H:i';

                $_temp = explode(' ', $date);
                $time  = explode(':', $_temp[1]);

                if ($is24Hour) {
                    // 24-hour format
                    if (count($time) == 2) {
                        $date .= ':00';
                    }
                } else {
                    // 12-hour format
                    $carbonDate = Carbon::createFromFormat(
                        $from_format . ' ' . 'g:i A',
                        $date
                    );
                    $time = $carbonDate->format('G:i');
                    $date = $carbonDate->format($from_format . ' ' . 'G:i:s');
                }
            }

            return Carbon::createFromFormat(
                $from_format . ($datetime ? ' H:i:s' : ''),
                $date
            )->format('Y-m-d' . ($datetime ? ' H:i:s' : ''));
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }
}

if (! function_exists('optimize_clear')) {
    /**
     * Clear all cached files including config, route, and view caches.
     *
     * @return void
     */
    function optimize_clear()
    {
        Artisan::call('optimize:clear');
    }
}

if (! function_exists('clear_cache')) {
    /**
     * Clear the application cache.
     *
     * @return void
     */
    function clear_cache()
    {
        Artisan::call('cache:clear');
    }
}

if (! function_exists('clear_config')) {
    /**
     * Clear the cached configuration files.
     *
     * @return void
     */
    function clear_config()
    {
        Artisan::call('config:clear');
    }
}

if (! function_exists('clear_route')) {
    /**
     * Clear the cached route files.
     *
     * @return void
     */
    function clear_route()
    {
        Artisan::call('route:clear');
    }
}

if (! function_exists('clear_view')) {
    /**
     * Clear all compiled view files.
     *
     * @return void
     */
    function clear_view()
    {
        Artisan::call('view:clear');
    }
}

if (! function_exists('rebuild_cache')) {
    /**
     * Rebuild and cache configuration, route, and view files.
     *
     * @return void
     */
    function rebuild_cache()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }
}

if (! function_exists('optimize')) {
    /**
     * Cache the framework bootstrap files for optimized performance.
     *
     * @return void
     */
    function optimize()
    {
        Artisan::call('optimize');
    }
}

if (! function_exists('create_storage_link')) {
    /**
     * Create a symbolic link from "public/storage" to "storage/app/public".
     *
     * @return void
     */
    function create_storage_link()
    {
        if (! is_link(public_path('storage'))) {
            Artisan::call('storage:link');
        }
    }
}

if (! function_exists('get_meta_allowed_extension')) {
    /**
     * Get the allowed file extensions and their maximum sizes for various media types.
     *
     * @return array<string, array{extension: string, size: float}> An associative array containing:
     *                                                              - 'image': Allowed image extensions and maximum size (in MB).
     *                                                              - 'video': Allowed video extensions and maximum size (in MB).
     *                                                              - 'audio': Allowed audio extensions and maximum size (in MB).
     *                                                              - 'document': Allowed document extensions and maximum size (in MB).
     *                                                              - 'sticker': Allowed sticker extensions and maximum size (in MB).
     */
    function get_meta_allowed_extension()
    {
        return [
            'image' => [
                'extension' => '.jpeg, .png',
                'size'      => 5,
            ],
            'video' => [
                'extension' => '.mp4, .3gp',
                'size'      => 16,
            ],
            'audio' => [
                'extension' => '.aac, .amr, .mp3, .m4a, .ogg',
                'size'      => 16,
            ],
            'document' => [
                'extension' => '.pdf, .doc, .docx, .txt, .xls, .xlsx, .ppt, .pptx',
                'size'      => 100,
            ],
            'sticker' => [
                'extension' => '.webp',
                'size'      => 0.1,
            ],
        ];
    }
}

if (! function_exists('get_whatsmark_allowed_extension')) {
    /**
     * Get the allowed file extensions for WhatsMark uploads.
     *
     * @return array<string, array{extension: string}> An associative array containing:
     *                                                 - 'file_types': Allowed file extensions for WhatsMark uploads.
     */
    function get_whatsmark_allowed_extension()
    {
        return [
            'file_types' => [
                'extension' => '.png,.jpg,.jpeg,.svg,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt,.webp,.aac, .amr, .mp3, .m4a, .ogg,.mp4, .3gp',
            ],
        ];
    }
}

if (! function_exists('t')) {
    /**
     * Retrieve a translation for the given key and locale.
     *
     * This function fetches translations from cached JSON files based on the user's locale.
     * If the translation does not exist, it returns the provided key as a fallback.
     *
     * @param  string      $key     The translation key to retrieve.
     * @param  array       $replace An optional array of placeholders and their replacements.
     *                              Example: ['name' => 'John'] replaces ":name" in the translation.
     * @param  string|null $locale  The locale to use (optional). Defaults to the user's locale or the app's active language.
     * @return string      The translated string or the original key if the translation is not found.
     */
    function t($key, $replace = [], $locale = null)
    {
        $locale = Auth::user() ? Session::get('locale', config('app.locale')) : get_setting('general.active_language');

        $translations = cache()->remember("translations.{$locale}", 3600, function () use ($locale) {
            $path = $locale === 'en'
                ? resource_path('lang/en.json')
                : resource_path("lang/translations/{$locale}.json");

            if (! file_exists($path)) {
                return json_decode(file_get_contents(resource_path('lang/en.json')), true) ?? [];
            }

            return json_decode(file_get_contents($path), true) ?? [];
        });

        $translation = $translations[$key] ?? $key;

        // Handle replacements
        foreach ($replace as $k => $v) {
            $translation = str_replace(":{$k}", $v, $translation);
        }

        return $translation;
    }
}

if (! function_exists('getLanguage')) {
    /**
     * Retrieve language(s) from the DB.
     *
     * @param  mixed $filter  (null, id, name, code, or associative array for custom where)
     * @param  array $columns (columns to select, default ['*'])
     * @return mixed
     */
    function getLanguage($filter = null, $columns = ['*'])
    {
        $query = \App\Models\Language::query();

        if (is_array($filter)) {
            // If filter is an associative array, apply where conditions.
            $query->where($filter);
        } elseif (! is_null($filter)) {
            if (is_numeric($filter)) {
                $query->where('id', $filter);
            } else {
                // Try matching by code first.
                $record = $query->where('code', $filter)->select($columns)->first();
                if ($record) {
                    return $record;
                }

                // If not found, try matching by name.
                $query = \App\Models\Language::query();
                $query->where('name', $filter);

                return $query->select($columns)->first();
            }
        }

        return $query->select($columns)->get();
    }
}

if (! function_exists('isSmtpValid')) {
    /**
     * Check if SMTP configuration is valid.
     *
     * This function verifies that all required SMTP settings are properly configured.
     * It checks for the presence of essential SMTP configuration values.
     *
     * @return bool Returns true if all required SMTP configurations are set, otherwise false.
     */
    function isSmtpValid()
    {
        $requiredConfigs = [
            'mail.mailers.smtp.host',
            'mail.mailers.smtp.port',
            'mail.mailers.smtp.encryption',
            'mail.mailers.smtp.username',
            'mail.mailers.smtp.password',
        ];

        foreach ($requiredConfigs as $config) {
            if (empty(config($config))) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('getArrayItem')) {
    /**
     * Retrieve an item from an array using a given key.
     *
     * This function checks if the provided key exists in the array and returns its value.
     * If the key is not found or the value is 'null', it returns the default value.
     *
     * @param  string|int $key     The key to search for in the array.
     * @param  array      $array   The array to search within.
     * @param  mixed      $default The default value to return if the key does not exist or the value is 'null'.
     * @return mixed      The value corresponding to the key or the default value if not found.
     */
    function getArrayItem($key, $array, $default = null)
    {
        return is_array($array) && ! empty($array) && array_key_exists($key, $array) && $array[$key] !== 'null'
            ? $array[$key]
            : $default;
    }
}

if (! function_exists('mailTemplate')) {
    /**
     * Retrieve an email template by its slug.
     *
     * This function fetches the first matching email template from the database
     * based on the provided slug.
     *
     * @param  string                         $slug The unique identifier (slug) of the email template.
     * @return \App\Models\EmailTemplate|null The matching EmailTemplate instance or null if not found.
     */
    function mailTemplate($slug)
    {
        return EmailTemplate::where('slug', $slug)->first();
    }
}

if (! function_exists('getLanguageJson')) {
    /**
     * Retrieve and decode the language JSON file for the specified language code.
     *
     * This function reads a language JSON file based on the provided language code.
     * If the language code is 'en', it fetches the default English JSON file.
     * For other language codes, it attempts to retrieve the corresponding translation file.
     *
     * @param string $languageCode The language code (e.g., 'en', 'fr').
     *
     * @throws \Exception If the language file is missing or cannot be decoded.
     *
     * @return array An associative array containing the language translations.
     */
    function getLanguageJson(string $languageCode): array
    {
        try {
            // Determine the path of the language JSON file
            if ($languageCode === 'en') {
                $filePath = resource_path('lang/en.json');
            } else {
                $filePath = resource_path("lang/translations/{$languageCode}.json");
            }

            // Check if the file exists
            if (! file_exists($filePath)) {
                throw new Exception(t('language_file_for') . $languageCode . t('not_found'));
            }

            // Read and decode the JSON file
            $jsonData    = file_get_contents($filePath);
            $decodedData = json_decode($jsonData, true);

            // Check if JSON decoding failed
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(t('failed_to_decode_json_from') . $filePath);
            }

            return $decodedData;
        } catch (Exception $e) {
            Log::error(t('error_loading_language_file') . $e->getMessage());

            return [];
        }
    }
}

if (! function_exists('getLangugeValue')) {
    /**
     * Retrieve the value for a specific key from a language JSON file.
     *
     * @param  string $languageCode The language code (e.g., 'en', 'fr', etc.)
     * @param  string $key          The key in the language JSON file
     * @param  mixed  $default      The default value if the key is not found (optional)
     * @return mixed
     */
    function getLangugeValue(string $languageCode, string $key, $default = null)
    {
        try {

            $filePath = $languageCode === 'en'
                ? resource_path('lang/en.json')
                : resource_path("lang/translations/{$languageCode}.json");

            if (! file_exists($filePath)) {
                throw new Exception(t('language_file_for') . $languageCode . ('not_found'));
            }

            // Read and decode the JSON file
            $jsonData    = file_get_contents($filePath);
            $decodedData = json_decode($jsonData, true);

            // Check if JSON decoding failed
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(t('failed_to_decode_json_from') . $filePath);
            }

            // Check if the key exists in the decoded JSON data
            if (isset($decodedData[$key])) {
                return $decodedData[$key];
            }

            return $default;
        } catch (Exception $e) {
            Log::error(t('error_fetching_language_key_value') . $e->getMessage());

            return $default;
        }
    }
}

if (! function_exists('app_log')) {
    /**
     * Write application logs with consistent formatting
     *
     * @param string          $message   Main log message
     * @param string          $level     Log level (error, info, debug, warning)
     * @param \Throwable|null $exception Optional exception object
     * @param array           $context   Additional context data
     */
    function app_log(string $message, string $level = 'error', ?\Throwable $exception = null, array $context = []): void
    {
        // Skip debug logs if app.debug is false
        if ($level === 'debug' && ! config('app.debug')) {
            return;
        }

        // Ensure request() is available and not running in the console
        $request = request();

        // Build log context
        $logContext = array_merge([
            'timestamp' => now()->setTimezone(get_setting('general.timezone', config('app.timezone')))->toDateTimeString(),
            'env'       => config('app.env'),
            'request'   => [
                'id'     => $request ? ($request->header('X-Request-ID') ?? (string) Str::uuid()) : (string) Str::uuid(),
                'url'    => $request ? $request->fullUrl() : 'CLI',
                'method' => $request ? $request->method() : 'CLI',
                'ip'     => $request ? $request->ip() : '127.0.0.1',
            ],
            'user_id' => auth()->id() ?? 'guest',
        ], $context);

        // Add exception details if provided
        if ($exception) {
            $logContext['exception'] = [
                'class'   => get_class($exception),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile() . ':' . $exception->getLine(),
                'trace'   => array_slice(
                    array_filter(
                        array_map(
                            'trim',
                            explode("\n", $exception->getTraceAsString())
                        )
                    ),
                    0,
                    5 // Limit trace to first 5 lines
                ),
            ];
        }

        \Log::{$level}($message, $logContext);
    }
}

if (! function_exists('parseText')) {
    /**
     * Parse text with merge fields
     *
     * @param  string       $rel_type
     * @param  string       $type
     * @param  array        $data
     * @param  string       $return_type
     * @return string|array
     */
    function parseText($rel_type, $type, $data, $return_type = 'text')
    {
        // Ensure we have a MergeFields service instance
        $mergeFieldsService = app(\App\Services\MergeFields::class);

        // Prepare context for merge field parsing
        $context = [
            'contactId' => $data['rel_id'] ?? null,
            'relType'   => $rel_type,
        ];

        // Default to empty array if params are not set
        $data["{$type}_params"] = $data["{$type}_params"] ?? '[]';

        // Replace @{} with {} for consistent merge field syntax
        $data["{$type}_params"] = preg_replace('/@{(.*?)}/', '{$1}', $data["{$type}_params"]);

        // Parse the parameters using merge fields
        $data["{$type}_params"] = $mergeFieldsService->parseTemplates(['other-group', 'contact-group'], $data["{$type}_params"], $context);

        // Get merge fields from both groups
        $merge_fields = array_merge(
            $mergeFieldsService->getFieldsForTemplate('other-group'),
            $mergeFieldsService->getFieldsForTemplate('contact-group')
        );

        // Prepare to parse parameters
        $parsedData  = [];
        $paramsCount = $data["{$type}_params_count"]              ?? 0;
        $params      = json_decode($data["{$type}_params"], true) ?? [];
        $index       = ($return_type == 'text') ? 1 : 0;
        $last        = ($return_type == 'text') ? $paramsCount : $paramsCount - 1;
        // Process each parameter
        for ($i = $index; $i <= $last; $i++) {
            $parsedText = is_array($params) ? array_map(function ($body) use ($merge_fields) {
                // Replace merge fields
                $body = preg_replace('/@{(.*?)}/', '{$1}', $body);
                foreach ($merge_fields as $field) {
                    $key  = $field['key'] ?? '';
                    $body = str_contains($body, "{{$key}}")
                        ? str_replace("{{$key}}", '', $body)
                        : $body;
                }

                return preg_replace('/\s+/', ' ', trim($body));
            }, $params) : [1 => trim($data["{$type}_params"] ?? '')];

            // Handle message template
            if ('text' == $return_type && ! empty($data["{$type}_message"])) {
                $data["{$type}_message"] = str_replace("{{{$i}}}", ! empty($parsedText[$i - 1]) ? $parsedText[$i - 1] : ' ', $data["{$type}_message"]);
            }

            $parsedData[] = ! empty($parsedText[$i]) ? $parsedText[$i] : ' ';
        }

        return ('text' == $return_type) ? $data["{$type}_message"] : $parsedData;
    }
}

if (! function_exists('parseMessageText')) {
    /**
     * Parse message text with merge fields
     *
     * @param  array $data
     * @return array
     */
    function parseMessageText($data)
    {
        $data['reply_text'] = preg_replace('/@{(.*?)}/', '{$1}', $data['reply_text'] ?? '');

        $mergeFieldsService = app(MergeFields::class);
        if ($data['rel_type'] == 'lead' || $data['rel_type'] == 'customer') {
            $data['reply_text'] = $mergeFieldsService->parseTemplates(['other-group', 'contact-group'], $data['reply_text'], ['contactId' => $data['rel_id']]);
        }
        $data['reply_text'] = $mergeFieldsService->parseTemplates(['other-group'], $data['reply_text'], []);

        return $data;
    }
}

if (! function_exists('isJson')) {
    /**
     * Check if a given string is valid JSON.
     *
     * This function verifies whether the provided string is a valid JSON format.
     * It returns true if the string can be decoded as JSON, including the 'null' value.
     *
     * @param  mixed $string The input to be checked.
     * @return bool  True if the input is a valid JSON string, otherwise false.
     */
    function isJson($string): bool
    {
        return is_string($string) && (json_decode($string) !== null || $string === 'null')
            ? true
            : false;
    }
}

if (! function_exists('parseCsvText')) {
    /**
     * Parse CSV-like text and replace placeholders with corresponding data.
     *
     * This function processes text by replacing placeholders in the format `{key}` with values from
     * the provided `$relData` array. It supports multiple parameters through JSON or plain text.
     *
     * @param  string $type    The type prefix used to extract parameters and count from `$data`.
     * @param  array  $data    The main data array containing parameter values and counts.
     * @param  array  $relData An associative array of placeholder keys and their corresponding values.
     * @return mixed  An array of parsed and formatted text entries.
     */
    function parseCsvText(string $type, array $data, array $relData): mixed
    {
        // Create merge fields by mapping {key} => value
        $mergeFields = collect($relData)->mapWithKeys(fn ($value, $key) => ["{{$key}}" => $value])->toArray();
        $parseData   = [];

        for ($i = 0; $i < $data["{$type}_params_count"]; $i++) {
            if (isJson($data["{$type}_params"] ?? '[]')) {
                $parsedText = json_decode($data["{$type}_params"], true) ?? [];
                $parsedText = array_map(function ($body) use ($mergeFields) {
                    // Convert "@{key}" syntax to "{$key}" syntax
                    $body = preg_replace('/@{(.*?)}/', '{$1}', $body);
                    foreach ($mergeFields as $key => $val) {
                        $body = str_contains($body, $key)
                            ? str_replace($key, ! empty($val) ? $val : ' ', $body)
                            : str_replace($key, '', $body);
                    }

                    return preg_replace('/\s+/', ' ', trim($body));
                }, $parsedText);
            } else {
                $parsedText[1] = preg_replace('/\s+/', ' ', trim($data["{$type}_params"]));
            }

            $parseData[] = ! empty($parsedText[$i]) ? $parsedText[$i] : ' ';
        }

        return $parseData;
    }
}

if (! function_exists('checkRemoteFile')) {
    /**
     * Check if a remote file exists and is accessible via HTTP.
     *
     * This function verifies whether a given URL is valid and returns whether the remote file is accessible.
     *
     * @param  string $url The URL of the remote file to check.
     * @return bool   True if the file is accessible, false otherwise.
     */
    function checkRemoteFile($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::head($url);

                return $response->successful();
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
}

if (! function_exists('checkPermission')) {
    /**
     * Check if the authenticated user has the required permission(s).
     *
     * This function verifies whether the current user has the specified permission(s).
     * Admin users are granted all permissions by default.
     *
     * @param  string|array $permissions The permission or array of permissions to check.
     * @return bool         True if the user has any of the specified permissions, false otherwise.
     */
    function checkPermission($permissions)
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin == 1) {
            return true;
        }

        // If multiple permissions are provided, check if any of them are allowed
        if (is_array($permissions)) {
            foreach ($permissions as $permi) {
                if ($user->can($permi)) {
                    return true;
                }
            }

            return false;
        }

        return $user->can($permissions);
    }
}

if (! function_exists('can_send_email')) {
    /**
     * Check if an email template is active based on the slug.
     *
     * @param  string $slug The email template slug.
     * @return bool   True if the email can be sent, otherwise false.
     */
    function can_send_email(string $slug): bool
    {
        return EmailTemplate::where('slug', $slug)
            ->where('is_active', true)
            ->exists();
    }
}

if (! function_exists('whatsapp_log')) {
    /**
     * Write WhatsApp logs with consistent formatting
     *
     * @param string          $message   Main log message
     * @param string          $level     Log level (error, warning, info, debug, critical, alert, emergency)
     * @param array           $context   Additional context data
     * @param \Throwable|null $exception Optional exception object
     */
    function whatsapp_log(string $message, string $level = 'info', array $context = [], ?\Throwable $exception = null): void
    {
        // Skip logging if WhatsApp logging is disabled in env
        if (! env('WHATSAPP_LOGGING_ENABLED', false)) {
            return;
        }

        // Add exception details if provided
        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile() . ':' . $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ];
        }

        // Use the whatsapp channel if it exists, otherwise fall back to the default channel
        try {
            Log::channel('whatsapp')->{$level}($message, $context);
        } catch (\Exception $e) {
            // If channel doesn't exist, log to default channel
            Log::{$level}("WhatsApp: {$message}", $context);
        }
    }
}

if (! function_exists('app_log')) {
    /**
     * Write application logs with consistent formatting
     *
     * @param string          $message   Main log message
     * @param string          $level     Log level (error, info, debug, warning, critical, alert, emergency)
     * @param array           $context   Additional context data
     * @param \Throwable|null $exception Optional exception object
     */
    function app_log(string $message, string $level = 'info', array $context = [], ?\Throwable $exception = null): void
    {
        // Skip debug logs if app.debug is false and this is a debug log
        if ($level === 'debug' && ! config('app.debug')) {
            return;
        }

        // Build log context
        $logContext = array_merge([
            'timestamp' => now()->toDateTimeString(),
            'env'       => config('app.env'),
            'request'   => [
                'id'     => request()->id() ?? \Illuminate\Support\Str::uuid(),
                'url'    => request()->fullUrl(),
                'method' => request()->method(),
                'ip'     => request()->ip(),
            ],
            'user_id' => auth()->id() ?? 'guest',
        ], $context);

        // Add exception details if provided
        if ($exception) {
            $logContext['exception'] = [
                'class'   => get_class($exception),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile() . ':' . $exception->getLine(),
                'trace'   => array_slice(
                    array_filter(
                        array_map(
                            'trim',
                            explode("\n", $exception->getTraceAsString())
                        )
                    ),
                    0,
                    5
                ),
            ];
        }

        // Log through the default channel
        \Illuminate\Support\Facades\Log::{$level}($message, $logContext);
    }
}

if (! function_exists('getCountryList')) {
    function getCountryList()
    {
        return Cache::remember('countries.all', now()->addHours(24), function () {
            $path = base_path('platform/packages/corbital/installer/countries.json');

            if (! File::exists($path)) {
                return [];
            }

            return json_decode(File::get($path), true);
        });
    }
}

/**
 * Decode WhatsApp signs to HTML tags
 *
 * @param  string $text
 * @return string
 */
if (! function_exists('ecodeWhatsAppSigns')) {
    function decodeWhatsAppSigns($text)
    {
        $patterns = [
            '/\*(.*?)\*/',       // Bold
            '/_(.*?)_/',         // Italic
            '/~(.*?)~/',         // Strikethrough
            '/```(.*?)```/',      // Monospace
        ];
        $replacements = [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<del>$1</del>',
            '<code>$1</code>',
        ];

        return preg_replace($patterns, $replacements, $text);
    }
}
