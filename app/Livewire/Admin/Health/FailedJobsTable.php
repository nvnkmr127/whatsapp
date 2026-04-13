<?php

namespace App\Livewire\Admin\Health;

use App\Models\Team;
use App\Support\FailedJobs\FailedJobInspector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class FailedJobsTable extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 25;

    public bool $showDetailsModal = false;
    public ?string $selectedUuid = null;
    public array $selected = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDetails(string $uuid): void
    {
        $failure = DB::table('failed_jobs')->where('uuid', $uuid)->first();
        if (! $failure) {
            session()->flash('flash.banner', 'Failed job not found.');
            session()->flash('flash.bannerStyle', 'danger');
            return;
        }

        $payload = json_decode((string) $failure->payload, true);
        $payloadArray = is_array($payload) ? $payload : null;

        $teamId = FailedJobInspector::teamIdFromPayload($payloadArray);
        $teamName = $teamId ? Team::whereKey($teamId)->value('name') : null;

        $this->selectedUuid = $uuid;
        $this->selected = [
            'uuid' => (string) $failure->uuid,
            'failed_at' => (string) $failure->failed_at,
            'connection' => (string) $failure->connection,
            'queue' => (string) $failure->queue,
            'team_id' => $teamId,
            'team_name' => $teamName ?: 'System/Unknown',
            'job' => FailedJobInspector::jobLabelFromPayload($payloadArray),
            'exception' => (string) $failure->exception,
            'payload' => json_encode($payloadArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];

        $this->showDetailsModal = true;
    }

    public function retry(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => $uuid]);
        session()->flash('flash.banner', 'Failed job queued for retry.');
        session()->flash('flash.bannerStyle', 'success');
    }

    public function forget(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
        session()->flash('flash.banner', 'Failed job removed.');
        session()->flash('flash.bannerStyle', 'success');

        if ($this->selectedUuid === $uuid) {
            $this->showDetailsModal = false;
            $this->selectedUuid = null;
            $this->selected = [];
        }
    }

    public function render()
    {
        $query = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('exception', 'like', $term)
                        ->orWhere('payload', 'like', $term);
                });
            })
            ->orderByDesc('failed_at');

        $paginator = $query->paginate($this->perPage);

        $rows = $paginator->getCollection()->map(function ($row) {
            $payload = json_decode((string) $row->payload, true);
            $payloadArray = is_array($payload) ? $payload : null;

            $teamId = FailedJobInspector::teamIdFromPayload($payloadArray);
            $teamName = $teamId ? Team::whereKey($teamId)->value('name') : null;

            return (object) [
                'uuid' => (string) $row->uuid,
                'failed_at' => $row->failed_at,
                'connection' => (string) $row->connection,
                'queue' => (string) $row->queue,
                'team_name' => $teamName ?: 'System/Unknown',
                'job' => FailedJobInspector::jobLabelFromPayload($payloadArray),
                'exception_preview' => mb_substr((string) $row->exception, 0, 120),
            ];
        });

        $paginator->setCollection($rows);

        return view('livewire.admin.health.failed-jobs-table', [
            'failedJobs' => $paginator,
        ]);
    }
}

