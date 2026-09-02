<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactImportService
{
    protected $team;

    protected $contactService;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->contactService = new ContactService;
    }

    /**
     * Resolve absolute local path and extension for file or path string.
     */
    protected function resolveLocalPath($fileOrPath, ?string $originalName = null): array
    {
        $realPath = null;
        $extension = '';

        if ($fileOrPath instanceof \Illuminate\Http\UploadedFile) {
            $originalName = $originalName ?: $fileOrPath->getClientOriginalName();
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            try {
                $realPath = $fileOrPath->getRealPath();
            } catch (\Throwable $e) {
                $realPath = null;
            }

            if (! $realPath || ! file_exists($realPath)) {
                $disk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');
                try {
                    $realPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($fileOrPath->path());
                } catch (\Throwable $e) {
                    $realPath = null;
                }
            }

            if (! $realPath || ! file_exists($realPath)) {
                $fallback = storage_path('app/' . $fileOrPath->path());
                if (file_exists($fallback)) {
                    $realPath = $fallback;
                }
            }
        } elseif (is_string($fileOrPath)) {
            $extension = strtolower(pathinfo($fileOrPath, PATHINFO_EXTENSION));

            if (file_exists($fileOrPath)) {
                $realPath = $fileOrPath;
            } else {
                $disk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');
                try {
                    $diskPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($fileOrPath);
                    if (file_exists($diskPath)) {
                        $realPath = $diskPath;
                    }
                } catch (\Throwable $e) {
                }

                if (! $realPath) {
                    $fallback = storage_path('app/' . ltrim($fileOrPath, '/'));
                    if (file_exists($fallback)) {
                        $realPath = $fallback;
                    }
                }
            }
        }

        if ($extension === '' || $extension === 'tmp') {
            if ($originalName) {
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            }
        }

        if (($extension === '' || $extension === 'tmp') && $realPath && file_exists($realPath)) {
            $header = @file_get_contents($realPath, false, null, 0, 4);
            if ($header !== false && str_starts_with($header, 'PK')) {
                $extension = 'xlsx';
            } else {
                $extension = 'csv';
            }
        }

        return [$realPath ?: (is_string($fileOrPath) ? $fileOrPath : ''), $extension];
    }

    /**
     * Get headers from file (CSV or Excel).
     */
    public function getHeaders($fileOrPath, ?string $originalName = null): array
    {
        [$filePath, $extension] = $this->resolveLocalPath($fileOrPath, $originalName);

        if (! file_exists($filePath)) {
            Log::error("Import getHeaders failed: File does not exist at {$filePath}");
            return [];
        }

        if (in_array($extension, ['csv', 'txt'])) {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            return $csv->getHeader();
        }

        // Excel Support
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestColumn = $worksheet->getHighestColumn();
            $headers = $worksheet->rangeToArray('A1:'.$highestColumn.'1', null, true, false);

            return $headers[0] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to load Excel headers: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Import contacts from file.
     *
     * @param  string  $filePath  Path to file
     * @param  array  $columnMapping  Mapping of columns to contact fields
     * @param  array  $options  Import options including consent information
     * @return array Results with success count and errors
     */
    public function import($fileOrPath, array $columnMapping, array $options = [])
    {
        [$filePath, $extension] = $this->resolveLocalPath($fileOrPath, $options['original_name'] ?? null);
        $records = [];
        $onDuplicate = $options['on_duplicate'] ?? 'update';
        $onDuplicateTag = $options['on_duplicate_tag'] ?? 'add';

        if (! file_exists($filePath)) {
            return ['success_count' => 0, 'errors' => ["Import file not found: {$filePath}"]];
        }

        if (in_array($extension, ['csv', 'txt'])) {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
        } else {
            // Excel Support
            try {
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray(null, true, true, true);

                $headers = array_shift($data);
                foreach ($data as $row) {
                    $record = [];
                    foreach ($headers as $colLetter => $header) {
                        if ($header) {
                            $record[$header] = $row[$colLetter] ?? null;
                        }
                    }
                    if (! empty(array_filter($record))) {
                        $records[] = $record;
                    }
                }
            } catch (\Exception $e) {
                return ['success_count' => 0, 'errors' => ['Excel load error: '.$e->getMessage()]];
            }
        }

        // GDPR Compliance
        $requireConsent = $options['require_consent'] ?? true;
        $consentSource = $options['consent_source'] ?? 'FILE_IMPORT';
        $consentProof = $options['consent_proof_url'] ?? null;

        $entitlement = app(\App\Services\EntitlementService::class)->for($this->team);
        $limit = $entitlement->limit('contact_limit');
        $currentCount = $this->team->contacts()->count();

        $successCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $existingPhones = [];
        $seenPhones = [];

        // Final check for existing phones if we need to skip them
        if ($onDuplicate === 'skip') {
            $phonesToCheck = [];
            foreach ($records as $index => $record) {
                $contactData = $this->mapRecordToContactData($record, $columnMapping);
                if (empty($contactData['phone_number'])) {
                    continue;
                }
                try {
                    $phonesToCheck[] = \App\Helpers\PhoneNumberHelper::normalize((string) $contactData['phone_number']);
                } catch (\Exception $e) {
                    continue;
                }
            }

            $phonesToCheck = array_values(array_unique($phonesToCheck));
            if (! empty($phonesToCheck)) {
                foreach (array_chunk($phonesToCheck, 1000) as $chunk) {
                    $existing = Contact::where('team_id', $this->team->id)
                        ->whereIn('phone_number', $chunk)
                        ->pluck('phone_number')
                        ->toArray();
                    foreach ($existing as $phone) {
                        $existingPhones[$phone] = true;
                    }
                }
            }
        }

        // IMPORTANT: Reset the records iterator for CSV files before the main loop
        // because the duplicate check above might have consumed it.
        if (in_array($extension, ['csv', 'txt'])) {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
        }

        foreach ($records as $index => $record) {
            try {
                \Illuminate\Support\Facades\Log::info('Importing Row '.($index + 1), [
                    'team_id' => $this->team->id,
                    'raw_record' => $record,
                ]);

                $contactData = $this->mapRecordToContactData($record, $columnMapping);

                if (empty($contactData['phone_number'])) {
                    \Illuminate\Support\Facades\Log::warning('Skipping Row '.($index + 1).': No phone number found', [
                        'mapped_data' => $contactData,
                    ]);
                    $errors[] = 'Row '.($index + 1).': Phone number is required.';

                    continue;
                }

                $originalPhone = (string) $contactData['phone_number'];
                $contactData['phone_number'] = \App\Helpers\PhoneNumberHelper::normalize($originalPhone);

                \Illuminate\Support\Facades\Log::debug('Normalized Phone', [
                    'original' => $originalPhone,
                    'normalized' => $contactData['phone_number'],
                ]);

                if ($onDuplicate === 'skip') {
                    if (isset($seenPhones[$contactData['phone_number']])) {
                        \Illuminate\Support\Facades\Log::info('Row '.($index + 1).': Skipping duplicate in file', [
                            'phone' => $contactData['phone_number'],
                        ]);
                        $skippedCount++;

                        continue;
                    }
                    $seenPhones[$contactData['phone_number']] = true;

                    if (isset($existingPhones[$contactData['phone_number']])) {
                        \Illuminate\Support\Facades\Log::info('Row '.($index + 1).': Skipping existing contact', [
                            'phone' => $contactData['phone_number'],
                        ]);
                        $skippedCount++;

                        continue;
                    }
                }

                // Add consent information
                if ($requireConsent && empty($contactData['opt_in_status'])) {
                    $contactData['opt_in_status'] = 'opted_in';
                    $contactData['opt_in_source'] = $consentSource;
                    $contactData['opt_in_at'] = now();
                }

                $existingContact = null;
                if ($onDuplicate === 'update') {
                    $existingContact = Contact::where('team_id', $this->team->id)
                        ->where('phone_number', $contactData['phone_number'])
                        ->first();
                }

                if (! $existingContact && $limit > 0 && $currentCount >= $limit) {
                    $errors[] = 'Row '.($index + 1).': Contact limit reached ('.$limit.' contacts).';

                    continue;
                }

                \Illuminate\Support\Facades\Log::info('Processing Contact for Row '.($index + 1), [
                    'is_update' => (bool) $existingContact,
                    'contact_data' => $contactData,
                ]);

                $contact = $this->processContact($contactData, [
                    'is_duplicate_existing' => (bool) $existingContact,
                    'on_duplicate_tag' => $onDuplicateTag,
                ]);

                // Log consent
                if ($contact->opt_in_status === 'opted_in') {
                    \App\Models\ConsentLog::create([
                        'team_id' => $this->team->id,
                        'contact_id' => $contact->id,
                        'action' => 'OPT_IN',
                        'source' => $consentSource,
                        'notes' => 'Imported from '.strtoupper($extension).': '.basename($filePath),
                        'proof_url' => $consentProof,
                    ]);
                }

                $successCount++;
                if ($existingContact) {
                    $updatedCount++;
                } else {
                    $createdCount++;
                    $currentCount++;
                }

                \Illuminate\Support\Facades\Log::info('Successfully processed Row '.($index + 1), [
                    'contact_id' => $contact->id,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed Row '.($index + 1), [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'row_data' => $record ?? null,
                ]);
                $errors[] = 'Row '.($index + 1).': '.$e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
        ];
    }

    public function previewImport($fileOrPath, array $columnMapping, ?string $originalName = null): array
    {
        [$filePath, $extension] = $this->resolveLocalPath($fileOrPath, $originalName);
        $records = [];

        if (! file_exists($filePath)) {
            return [
                'total_rows' => 0,
                'valid_rows' => 0,
                'errors' => ["Import file not found: {$filePath}"],
                'duplicates_existing' => [],
                'duplicates_in_file' => [],
            ];
        }

        if (in_array($extension, ['csv', 'txt'])) {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
        } else {
            try {
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray(null, true, true, true);
                $headers = array_shift($data);
                foreach ($data as $row) {
                    $record = [];
                    foreach ($headers as $colLetter => $header) {
                        if ($header) {
                            $record[$header] = $row[$colLetter] ?? null;
                        }
                    }
                    if (! empty(array_filter($record))) {
                        $records[] = $record;
                    }
                }
            } catch (\Exception $e) {
                return [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'errors' => ['Excel load error: '.$e->getMessage()],
                    'duplicates_existing' => [],
                    'duplicates_in_file' => [],
                ];
            }
        }

        $errors = [];
        $rows = [];
        $phones = [];
        $rowsByPhone = [];

        foreach ($records as $index => $record) {
            try {
                $contactData = $this->mapRecordToContactData($record, $columnMapping);
                $rowNo = $index + 1;

                if (empty($contactData['phone_number'])) {
                    $errors[] = "Row {$rowNo}: Phone number is required.";

                    continue;
                }

                $normalized = \App\Helpers\PhoneNumberHelper::normalize((string) $contactData['phone_number']);
                $phones[] = $normalized;

                $incomingTags = $contactData['tags'] ?? [];
                if (! is_array($incomingTags)) {
                    $incomingTags = [];
                }
                $incomingTags = array_values(array_filter(array_map('trim', $incomingTags), fn ($t) => $t !== ''));

                $rows[] = [
                    'row' => $rowNo,
                    'name' => (string) ($contactData['name'] ?? ''),
                    'phone_number' => $normalized,
                    'email' => (string) ($contactData['email'] ?? ''),
                    'tags' => $incomingTags,
                ];

                if (! isset($rowsByPhone[$normalized])) {
                    $rowsByPhone[$normalized] = [];
                }
                $rowsByPhone[$normalized][] = $rowNo;
            } catch (\Exception $e) {
                $errors[] = 'Row '.($index + 1).': '.$e->getMessage();
            }
        }

        $phones = array_values(array_unique($phones));
        $existingByPhone = [];
        if (! empty($phones)) {
            foreach (array_chunk($phones, 1000) as $chunk) {
                $existing = Contact::where('team_id', $this->team->id)
                    ->whereIn('phone_number', $chunk)
                    ->with(['tags:id,name'])
                    ->get(['id', 'name', 'phone_number']);
                foreach ($existing as $c) {
                    $existingByPhone[$c->phone_number] = [
                        'id' => $c->id,
                        'name' => (string) $c->name,
                        'phone_number' => (string) $c->phone_number,
                        'tags' => $c->tags->pluck('name')->toArray(),
                    ];
                }
            }
        }

        $duplicatesExisting = [];
        foreach ($rows as $r) {
            if (isset($existingByPhone[$r['phone_number']])) {
                $existing = $existingByPhone[$r['phone_number']];
                $duplicatesExisting[] = [
                    'row' => $r['row'],
                    'phone_number' => $r['phone_number'],
                    'incoming_name' => $r['name'],
                    'incoming_email' => $r['email'],
                    'incoming_tags' => ! empty($r['tags']) ? $r['tags'] : null,
                    'existing' => $existing,
                ];
            }
        }

        $duplicatesInFile = [];
        foreach ($rowsByPhone as $phone => $rowNumbers) {
            if (count($rowNumbers) > 1) {
                $duplicatesInFile[] = [
                    'phone_number' => $phone,
                    'rows' => $rowNumbers,
                ];
            }
        }

        return [
            'total_rows' => count($rows) + count($errors),
            'valid_rows' => count($rows),
            'errors' => $errors,
            'duplicates_existing' => $duplicatesExisting,
            'duplicates_in_file' => $duplicatesInFile,
        ];
    }

    protected function mapRecordToContactData(array $record, array $mapping)
    {
        $data = [
            'team_id' => $this->team->id,
            'custom_attributes' => [],
            'tags' => [],
        ];

        foreach ($mapping as $csvHeader => $targetField) {
            $value = $record[$csvHeader] ?? null;
            if (empty($value)) {
                continue;
            }

            $targetField = (string) $targetField;

            if ($targetField === 'tags') {
                $data['tags'] = array_map('trim', explode(',', $value));
            } elseif ($targetField === 'phone') {
                $data['phone_number'] = $value;
            } elseif ($targetField === 'category') {
                $data['category_name'] = $value;
            } elseif (in_array($targetField, ['name', 'phone_number', 'email', 'language'])) {
                $data[$targetField] = $value;
            } elseif (str_starts_with($targetField, 'attr_')) {
                $key = substr($targetField, 5);
                if ($key !== '') {
                    $data['custom_attributes'][$key] = $value;
                }
            } else {
                $data['custom_attributes'][$targetField] = $value;
            }
        }

        return $data;
    }

    protected function processContact(array $data, array $options = [])
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        if (! empty($data['category_name'])) {
            $categoryName = trim((string) $data['category_name']);
            if ($categoryName !== '') {
                $category = Category::firstOrCreate(
                    [
                        'team_id' => $this->team->id,
                        'target_module' => 'contacts',
                        'name' => $categoryName,
                    ],
                    [
                        'description' => null,
                        'color' => '#0ea5e9',
                        'icon' => 'fas fa-tag',
                        'is_active' => true,
                    ]
                );
                $data['category_id'] = $category->id;
            }
            unset($data['category_name']);
        }

        $data['force_name_update'] = true;
        $contact = $this->contactService->createOrUpdate($data);

        if (! empty($tags)) {
            $onDuplicateTag = $options['on_duplicate_tag'] ?? 'add';
            $isDuplicateExisting = (bool) ($options['is_duplicate_existing'] ?? false);

            if ($isDuplicateExisting) {
                if ($onDuplicateTag === 'replace') {
                    $this->contactService->syncTags($contact, $tags);
                } elseif ($onDuplicateTag === 'keep') {
                } else {
                    $this->contactService->addTags($contact, $tags);
                }
            } else {
                $this->contactService->syncTags($contact, $tags);
            }
        }

        return $contact;
    }
}
