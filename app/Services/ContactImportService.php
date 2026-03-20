<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactField;
use App\Models\ContactTag;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactImportService
{
    protected $team;
    protected $contactService;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->contactService = new ContactService();
    }

    /**
     * Get headers from file (CSV or Excel).
     */
    public function getHeaders(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

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
            $headers = $worksheet->rangeToArray('A1:' . $highestColumn . '1', NULL, TRUE, FALSE);
            return $headers[0] ?? [];
        } catch (\Exception $e) {
            Log::error("Failed to load Excel headers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Import contacts from file.
     * 
     * @param string $filePath Path to file
     * @param array $columnMapping Mapping of columns to contact fields
     * @param array $options Import options including consent information
     * @return array Results with success count and errors
     */
    public function import(string $filePath, array $columnMapping, array $options = [])
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $records = [];

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
                    if (!empty(array_filter($record))) {
                        $records[] = $record;
                    }
                }
            } catch (\Exception $e) {
                return ['success_count' => 0, 'errors' => ["Excel load error: " . $e->getMessage()]];
            }
        }

        // GDPR Compliance
        $requireConsent = $options['require_consent'] ?? true;
        $consentSource = $options['consent_source'] ?? 'FILE_IMPORT';
        $consentProof = $options['consent_proof_url'] ?? null;

        $successCount = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $contactData = $this->mapRecordToContactData($record, $columnMapping);

                if (empty($contactData['phone_number'])) {
                    $errors[] = "Row " . ($index + 1) . ": Phone number is required.";
                    continue;
                }

                // Add consent information
                if ($requireConsent && empty($contactData['opt_in_status'])) {
                    $contactData['opt_in_status'] = 'opted_in';
                    $contactData['opt_in_source'] = $consentSource;
                    $contactData['opt_in_at'] = now();
                }

                $contact = $this->processContact($contactData);

                // Log consent
                if ($contact->opt_in_status === 'opted_in') {
                    \App\Models\ConsentLog::create([
                        'team_id' => $this->team->id,
                        'contact_id' => $contact->id,
                        'action' => 'OPT_IN',
                        'source' => $consentSource,
                        'notes' => "Imported from " . strtoupper($extension) . ": " . basename($filePath),
                        'proof_url' => $consentProof,
                    ]);
                }

                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'errors' => $errors,
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
            if (empty($value))
                continue;

            if ($targetField === 'tags') {
                $data['tags'] = array_map('trim', explode(',', $value));
            } elseif (in_array($targetField, ['name', 'phone_number', 'email', 'language'])) {
                $data[$targetField] = $value;
            } else {
                $data['custom_attributes'][$targetField] = $value;
            }
        }

        return $data;
    }

    protected function processContact(array $data)
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $contact = $this->contactService->createOrUpdate($data);

        if (!empty($tags)) {
            $this->contactService->addTags($contact, $tags);
        }

        return $contact;
    }
}
