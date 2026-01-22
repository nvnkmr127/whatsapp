<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactField;
use App\Models\ContactTag;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use Illuminate\Support\Str;

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
     * Import contacts from CSV file.
     * 
     * @param string $filePath Path to CSV file
     * @param array $columnMapping Mapping of CSV columns to contact fields
     * @param array $options Import options including consent information
     * @return array Results with success count and errors
     */
    public function import(string $filePath, array $columnMapping, array $options = [])
    {
        // $columnMapping = ['csv_header' => 'contact_field_key']
        // internal fields: name, phone_number, email, tags
        // custom fields: keys from contact_fields table

        // GDPR Compliance: Require consent source for all imports
        $requireConsent = $options['require_consent'] ?? true;
        $consentSource = $options['consent_source'] ?? null;
        $consentProof = $options['consent_proof_url'] ?? null;

        if ($requireConsent && !$consentSource) {
            throw new \Exception("Consent source is required for contact import. Please provide 'consent_source' in options.");
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $successCount = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $contactData = $this->mapRecordToContactData($record, $columnMapping);

                if (empty($contactData['phone_number'])) {
                    $errors[] = "Row " . ($index + 1) . ": Phone number is required.";
                    continue;
                }

                // Add consent information if not already in CSV
                if ($requireConsent && empty($contactData['opt_in_status'])) {
                    $contactData['opt_in_status'] = 'opted_in';
                    $contactData['opt_in_source'] = $consentSource;
                    $contactData['opt_in_at'] = now();
                }

                $contact = $this->processContact($contactData);

                // Log consent for audit trail
                if ($contact->opt_in_status === 'opted_in') {
                    \App\Models\ConsentLog::create([
                        'team_id' => $this->team->id,
                        'contact_id' => $contact->id,
                        'action' => 'OPT_IN',
                        'source' => $consentSource,
                        'notes' => "Imported from CSV: " . basename($filePath),
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
                // Assume tags are comma separated
                $data['tags'] = array_map('trim', explode(',', $value));
            } elseif (in_array($targetField, ['name', 'phone_number', 'email', 'language'])) {
                $data[$targetField] = $value;
            } else {
                // Custom Attribute
                $data['custom_attributes'][$targetField] = $value;
            }
        }

        return $data;
    }

    protected function processContact(array $data)
    {
        // Extract tags and custom attributes
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        // Create or Update Contact
        $contact = $this->contactService->createOrUpdate($data);

        // Sync Tags
        if (!empty($tags)) {
            $this->contactService->addTags($contact, $tags);
        }

        return $contact;
    }
}
