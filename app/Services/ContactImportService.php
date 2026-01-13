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

    public function import(string $filePath, array $columnMapping)
    {
        // $columnMapping = ['csv_header' => 'contact_field_key']
        // internal fields: name, phone_number, email, tags
        // custom fields: keys from contact_fields table

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

                $this->processContact($contactData);
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
    }
}
