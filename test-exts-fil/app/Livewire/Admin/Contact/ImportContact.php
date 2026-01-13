<?php

namespace App\Livewire\Admin\Contact;

use App\Models\Contact;
use App\Models\Source;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Statement;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportContact extends Component
{
    use WithFileUploads;

    public $csvFile;

    public $totalRecords = 0;

    public $validRecords = 0;

    public $invalidRecords = 0;

    public $processedRecords = 0;

    public $errorMessages = [];

    public $importInProgress = false;

    protected $batchSize = 100;

    protected $referenceData = [];

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:51200',
    ];

    public function mount()
    {
        if (! checkPermission('contact.bulk_import')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    protected function getValidationRules()
    {
        return [
            'firstname'   => 'required|string|max:191',
            'lastname'    => 'required|string|max:191',
            'company'     => 'nullable|string|max:191',
            'type'        => 'required|in:lead,customer',
            'description' => 'nullable|string',
            'country_id'  => 'nullable|exists:countries,id',
            'zip'         => 'nullable|string|max:20',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:191',
            'assigned_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (! empty($value)) {
                        \Validator::make(
                            [$attribute => $value],
                            [$attribute => 'exists:users,id']
                        )->validate();
                    }
                },
            ],
            'status_id' => 'required|exists:statuses,id',
            'source_id' => 'required|exists:sources,id',
            'email'     => 'nullable|email|max:100|unique:contacts,email',
            'phone'     => [
                'required',
                'string',
                'max:50',
                'unique:contacts,phone',
                function ($attribute, $value, $fail) {
                    if (! preg_match('/^\+[1-9]\d{10,14}$/', $value)) {
                        $fail(t('phone_validation'));
                    }
                },
            ],
            'default_language' => 'nullable|string|max:40',
        ];
    }

    protected function validateCsvContents()
    {
        try {
            $csv = Reader::createFromPath($this->csvFile->path());
            $csv->setHeaderOffset(0);

            $headers         = array_map('strtolower', $csv->getHeader());
            $requiredColumns = [
                'firstname',
                'lastname',
                'type',
                'phone',
                'status_id',
                'source_id',
            ];

            $missingColumns = array_diff($requiredColumns, $headers);

            if (! empty($missingColumns)) {
                $this->addError('csvFile', t('missing_required_columns') . ': ' . implode(', ', $missingColumns));

                return false;
            }

            // Get accurate count without loading all records
            $stmt               = Statement::create();
            $this->totalRecords = iterator_count($stmt->process($csv));
            $this->resetCounters();

            return true;
        } catch (\Exception $e) {
            $this->addError('csvFile', t('invalid_csv_file') . ': ' . $e->getMessage());

            app_log('CSV validation failed: ' . $e->getMessage(), 'error', $e, [
                'file'      => $this->csvFile->getClientOriginalName(),
                'file_path' => $this->csvFile->path(),
                'file_size' => $this->csvFile->getSize(),
                'file_mime' => $this->csvFile->getMimeType(),
            ]);

            return false;
        }
    }

    protected function loadReferenceData()
    {
        if (empty($this->referenceData)) {
            $this->referenceData = [
                'statuses' => Status::pluck('id', 'name')->toArray(),
                'sources'  => Source::pluck('id', 'name')->toArray(),
                'users'    => User::pluck('id', 'firstname')->toArray(),
            ];
        }

        return $this->referenceData;
    }

    protected function transformRecord($record)
    {
        $record = array_change_key_case($record, CASE_LOWER);

        return [
            'firstname'          => $record['firstname'],
            'lastname'           => $record['lastname'],
            'company'            => $record['company']          ?? null,
            'type'               => strtolower($record['type']) ?? 'lead',
            'description'        => $record['description']      ?? null,
            'assigned_id'        => (int) ($record['assigned_id'] ?? auth()->id()),
            'status_id'          => (int) $record['status_id'],
            'source_id'          => (int) $record['source_id'],
            'email'              => $record['email'] ?? null,
            'phone'              => $this->formatPhoneNumber($record['phone']),
            'addedfrom'          => auth()->id(),
            'dateassigned'       => now(),
            'last_status_change' => now(),
            'default_language'   => 'en',
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }

    protected function processBatch($records)
    {
        $validRecords = [];

        foreach ($records as $index => $record) {
            try {
                $transformedRecord = $this->transformRecord($record);
                $validator         = Validator::make($transformedRecord, $this->getValidationRules());

                if ($validator->fails()) {
                    $this->invalidRecords++;
                    $this->errorMessages[] = [
                        'row'    => $this->processedRecords + $index + 1,
                        'errors' => $validator->errors()->toArray(),
                    ];

                    continue;
                }

                $validRecords[] = $transformedRecord;
                $this->validRecords++;
            } catch (\Exception $e) {
                $this->invalidRecords++;
                $this->errorMessages[] = [
                    'row'    => $this->processedRecords + $index + 1,
                    'errors' => ['system' => [$e->getMessage()]],
                ];

                app_log('Record transformation failed: ' . $e->getMessage(), 'error', $e, [
                    'row'         => $this->processedRecords + $index + 1,
                    'record_data' => $record,
                    'batch_size'  => count($records),
                    'file'        => $e->getFile(),
                    'line'        => $e->getLine(),
                ]);
            }
        }

        if (! empty($validRecords)) {
            try {
                Contact::insert($validRecords);
            } catch (\Exception $e) {

                app_log('Batch insert failed: ' . $e->getMessage(), 'error', $e, [
                    'batch_size' => count($validRecords),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                ]);

                foreach ($validRecords as $record) {
                    try {
                        Contact::create($record);
                    } catch (\Exception $inner) {
                        $this->invalidRecords++;
                        $this->validRecords--;
                        $this->errorMessages[] = [
                            'row'    => 'Unknown',
                            'errors' => ['system' => [$inner->getMessage()]],
                        ];

                        app_log('Fallback record creation failed: ' . $inner->getMessage(), 'error', $inner, [
                            'record_data' => $record,
                            'file'        => $inner->getFile(),
                            'line'        => $inner->getLine(),
                        ]);
                    }
                }
            }
        }
    }

    public function processImport()
    {
        $this->validate();

        if (! $this->csvFile || $this->importInProgress) {
            return;
        }

        if (! $this->validateCsvContents()) {
            return;
        }

        $this->importInProgress = true;
        $this->resetCounters();

        try {
            $csv = Reader::createFromPath($this->csvFile->path());
            $csv->setHeaderOffset(0);

            $this->loadReferenceData();

            $offset = 0;

            do {
                $stmt = Statement::create()
                    ->offset($offset)
                    ->limit($this->batchSize);

                $records = iterator_to_array($stmt->process($csv));

                if (empty($records)) {
                    break;
                }

                $this->processBatch($records);

                $recordsProcessed = count($records);
                $this->processedRecords += $recordsProcessed;
                $offset                 += $this->batchSize;

                // Update the UI
                $this->dispatch('updateImportProgress', [
                    'processed' => $this->processedRecords,
                    'total'     => $this->totalRecords,
                    'valid'     => $this->validRecords,
                    'invalid'   => $this->invalidRecords,
                ]);

                gc_collect_cycles();
            } while ($recordsProcessed > 0);

            $this->dispatch('importComplete');

            $this->notify([
                'type'    => 'success',
                'message' => "{$this->validRecords} " . t('import_completed', [
                    'valid'   => $this->validRecords,
                    'invalid' => $this->invalidRecords,
                ]),
            ]);
        } catch (\Exception $e) {

            app_log('Import failed: ' . $e->getMessage(), 'error', $e, [
                'user_id'    => auth()->id(),
                'file_path'  => $this->csvFile->path(),
                'processed'  => $this->processedRecords,
                'valid'      => $this->validRecords,
                'invalid'    => $this->invalidRecords,
                'batch_size' => $this->batchSize,
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            $this->notify([
                'type'    => 'danger',
                'message' => t('import_error') . ': ' . $e->getMessage(),
            ]);
        } finally {
            $this->importInProgress = false;
        }
    }

    protected function resetCounters()
    {
        $this->validRecords     = 0;
        $this->invalidRecords   = 0;
        $this->processedRecords = 0;
        $this->errorMessages    = [];
    }

    protected function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (! str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    public function downloadSample()
    {
        $filePath = public_path('csv_sample/contacts_sample.csv');
        if (! file_exists($filePath)) {
            $this->notify(['type' => 'danger', 'message' => t('sample_file_not_found')]);

            return;
        }

        return response()->download($filePath);
    }

    public function render()
    {
        return view('livewire.admin.contact.import-contact');
    }
}
