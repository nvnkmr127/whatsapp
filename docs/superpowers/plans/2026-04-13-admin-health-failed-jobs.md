# Admin Health Failed Jobs Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a paginated, searchable, actionable “Failed Jobs (Last 24h)” list on `/admin/health` with per-row View/Retry/Forget actions.

**Architecture:** Keep `/admin/health` as controller + Blade and embed a Livewire component for the failed jobs table. Use a small support class to derive `team_id/team_name` and a readable job label from `failed_jobs.payload`.

**Tech Stack:** Laravel, Livewire, Blade, database-backed queue failed_jobs, Artisan queue commands.

---

## File Map

**Create**
- `app/Livewire/Admin/Health/FailedJobsTable.php`
- `resources/views/livewire/admin/health/failed-jobs-table.blade.php`
- `app/Support/FailedJobs/FailedJobInspector.php`
- `tests/Feature/Admin/HealthFailedJobsTest.php`

**Modify**
- `resources/views/admin/health/index.blade.php` (embed component + remove full-page auto refresh)

---

### Task 1: Add a reusable failed job inspector

**Files:**
- Create: `app/Support/FailedJobs/FailedJobInspector.php`
- Test: `tests/Feature/Admin/HealthFailedJobsTest.php`

- [ ] **Step 1: Write a failing unit-level assertion in the feature test for team extraction**

Add this helper usage test section (it will fail until the class exists):

```php
<?php

use App\Support\FailedJobs\FailedJobInspector;

it('extracts team id from serialized payload command', function () {
    $payload = [
        'data' => [
            'command' => '... "team_id";i:123 ...',
        ],
        'displayName' => 'App\\Jobs\\ExampleJob',
    ];

    expect(FailedJobInspector::teamIdFromPayload($payload))->toBe(123);
    expect(FailedJobInspector::jobLabelFromPayload($payload))->toBe('App\\Jobs\\ExampleJob');
});
```

- [ ] **Step 2: Run the test to confirm it fails**

Run:

```bash
php artisan test --filter=extracts\ team\ id
```

Expected: FAIL due to missing `FailedJobInspector` class.

- [ ] **Step 3: Implement `FailedJobInspector`**

Create `app/Support/FailedJobs/FailedJobInspector.php`:

```php
<?php

namespace App\Support\FailedJobs;

final class FailedJobInspector
{
    public static function teamIdFromPayload(?array $payload): ?int
    {
        $command = $payload['data']['command'] ?? null;
        if (! is_string($command) || $command === '') {
            return null;
        }

        if (preg_match('/"team_id";i:(\d+)/', $command, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/App\\\\Models\\\\Team";s:2:"id";i:(\d+)/', $command, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function jobLabelFromPayload(?array $payload): string
    {
        $label = $payload['displayName'] ?? null;
        if (is_string($label) && $label !== '') {
            return $label;
        }

        $job = $payload['job'] ?? null;
        if (is_string($job) && $job !== '') {
            return $job;
        }

        $commandName = $payload['data']['commandName'] ?? null;
        if (is_string($commandName) && $commandName !== '') {
            return $commandName;
        }

        return 'Unknown';
    }
}
```

- [ ] **Step 4: Run the test to confirm it passes**

Run:

```bash
php artisan test --filter=extracts\ team\ id
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Support/FailedJobs/FailedJobInspector.php tests/Feature/Admin/HealthFailedJobsTest.php
git commit -m "feat(admin): add failed job inspector"
```

---

### Task 2: Build the Livewire failed jobs table (pagination + search + details modal)

**Files:**
- Create: `app/Livewire/Admin/Health/FailedJobsTable.php`
- Create: `resources/views/livewire/admin/health/failed-jobs-table.blade.php`
- Test: `tests/Feature/Admin/HealthFailedJobsTest.php`

- [ ] **Step 1: Write failing tests for the `/admin/health` page + table behavior**

In `tests/Feature/Admin/HealthFailedJobsTest.php` add:

```php
<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Health\FailedJobsTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HealthFailedJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_failed_jobs_table_on_health_page(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($user)
            ->get('/admin/health')
            ->assertOk()
            ->assertSee('Failed Jobs', false);
    }

    public function test_failed_jobs_table_lists_recent_failures_and_can_search_payload_or_exception(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        DB::table('failed_jobs')->insert([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\Foo', 'data' => ['command' => '... "team_id";i:1 ...'], 'meta' => ['trace_id' => '22dc80e6-f76e-41d7-be36-fed55f3cc09e']]),
            'exception' => 'Example exception text',
            'failed_at' => now()->subHour(),
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\Bar', 'data' => ['command' => '...']]),
            'exception' => 'Different exception',
            'failed_at' => now()->subHour(),
        ]);

        Livewire::actingAs($user)
            ->test(FailedJobsTable::class)
            ->assertSee('00000000-0000-0000-0000-000000000001')
            ->assertSee('App\\Jobs\\Foo')
            ->set('search', '22dc80e6-f76e-41d7-be36-fed55f3cc09e')
            ->assertSee('00000000-0000-0000-0000-000000000001')
            ->assertDontSee('00000000-0000-0000-0000-000000000002');
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

Run:

```bash
php artisan test --filter=HealthFailedJobsTest -v
```

Expected: FAIL because the component and UI do not exist yet.

- [ ] **Step 3: Implement the Livewire component**

Create `app/Livewire/Admin/Health/FailedJobsTable.php`:

```php
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
        $teamId = FailedJobInspector::teamIdFromPayload(is_array($payload) ? $payload : null);
        $teamName = $teamId ? Team::whereKey($teamId)->value('name') : null;

        $this->selectedUuid = $uuid;
        $this->selected = [
            'uuid' => $failure->uuid,
            'failed_at' => (string) $failure->failed_at,
            'connection' => (string) $failure->connection,
            'queue' => (string) $failure->queue,
            'team_id' => $teamId,
            'team_name' => $teamName ?: 'System/Unknown',
            'job' => FailedJobInspector::jobLabelFromPayload(is_array($payload) ? $payload : null),
            'exception' => (string) $failure->exception,
            'payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
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
            $teamId = FailedJobInspector::teamIdFromPayload(is_array($payload) ? $payload : null);
            $teamName = $teamId ? Team::whereKey($teamId)->value('name') : null;

            return (object) [
                'uuid' => $row->uuid,
                'failed_at' => $row->failed_at,
                'connection' => $row->connection,
                'queue' => $row->queue,
                'team_name' => $teamName ?: 'System/Unknown',
                'job' => FailedJobInspector::jobLabelFromPayload(is_array($payload) ? $payload : null),
                'exception_preview' => mb_substr((string) $row->exception, 0, 120),
            ];
        });

        $paginator->setCollection($rows);

        return view('livewire.admin.health.failed-jobs-table', [
            'failedJobs' => $paginator,
        ]);
    }
}
```

- [ ] **Step 4: Implement the component Blade view**

Create `resources/views/livewire/admin/health/failed-jobs-table.blade.php`:

```blade
<div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800">
    <div class="flex items-center justify-between gap-4 mb-6">
        <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Failed Jobs (Last 24h)</h4>
        <div class="flex items-center gap-3">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search exception / payload / trace id..."
                class="w-80 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl text-sm"
            />
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <th class="py-3 pr-4">Failed</th>
                    <th class="py-3 pr-4">Team</th>
                    <th class="py-3 pr-4">Queue</th>
                    <th class="py-3 pr-4">Job</th>
                    <th class="py-3 pr-4">Exception</th>
                    <th class="py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($failedJobs as $job)
                    <tr class="text-sm">
                        <td class="py-4 pr-4 text-[11px] font-bold text-slate-600 dark:text-slate-300">
                            {{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}
                            <div class="text-[10px] font-mono text-slate-400">{{ $job->uuid }}</div>
                        </td>
                        <td class="py-4 pr-4 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                            {{ $job->team_name }}
                        </td>
                        <td class="py-4 pr-4 text-[11px] font-mono text-slate-500">
                            {{ $job->connection }}:{{ $job->queue }}
                        </td>
                        <td class="py-4 pr-4 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                            {{ $job->job }}
                        </td>
                        <td class="py-4 pr-4 text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $job->exception_preview }}
                        </td>
                        <td class="py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openDetails('{{ $job->uuid }}')" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black uppercase">View</button>
                                <button wire:click="retry('{{ $job->uuid }}')" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-[10px] font-black uppercase">Retry</button>
                                <button wire:click="forget('{{ $job->uuid }}')" class="px-3 py-1.5 rounded-xl bg-rose-600 text-white text-[10px] font-black uppercase">Forget</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">
                            No failed jobs in the last 24 hours
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $failedJobs->links() }}
    </div>

    <x-dialog-modal wire:model.live="showDetailsModal" maxWidth="5xl">
        <x-slot name="title">
            <div class="flex items-center justify-between w-full">
                <div class="text-lg font-black text-slate-900 dark:text-white">Failed Job Details</div>
                <div class="text-[10px] font-mono text-slate-400">{{ $selected['uuid'] ?? '' }}</div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Team</div>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $selected['team_name'] ?? '' }}</div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Queue</div>
                        <div class="text-sm font-mono text-slate-700 dark:text-slate-200">{{ ($selected['connection'] ?? '') . ':' . ($selected['queue'] ?? '') }}</div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Exception</div>
                    <pre class="text-[11px] leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $selected['exception'] ?? '' }}</pre>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Payload</div>
                    <pre class="text-[11px] leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $selected['payload'] ?? '' }}</pre>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <button wire:click="$set('showDetailsModal', false)" class="px-6 py-2.5 text-sm font-bold text-slate-500">Close</button>
                <div class="flex items-center gap-2">
                    @if(!empty($selected['uuid']))
                        <button wire:click="retry('{{ $selected['uuid'] }}')" class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-black text-sm">Retry</button>
                        <button wire:click="forget('{{ $selected['uuid'] }}')" class="px-4 py-2.5 bg-rose-600 text-white rounded-xl font-black text-sm">Forget</button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
```

- [ ] **Step 5: Run tests to confirm the component tests pass**

Run:

```bash
php artisan test --filter=HealthFailedJobsTest -v
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Health/FailedJobsTable.php resources/views/livewire/admin/health/failed-jobs-table.blade.php tests/Feature/Admin/HealthFailedJobsTest.php
git commit -m "feat(admin): add failed jobs table to health dashboard"
```

---

### Task 3: Embed component into `/admin/health` and remove disruptive full-page reload

**Files:**
- Modify: `resources/views/admin/health/index.blade.php`
- Test: `tests/Feature/Admin/HealthFailedJobsTest.php`

- [ ] **Step 1: Update `/admin/health` Blade template**

In `resources/views/admin/health/index.blade.php`:
- Insert:

```blade
<livewire:admin.health.failed-jobs-table />
```

near the existing “BACKGROUND INFO” section (below “Recent Errors”, or replace it).

- Remove the `setTimeout(function() { window.location.reload(); }, 60000);` block so pagination/search state is not disrupted.

- [ ] **Step 2: Run the page test**

Run:

```bash
php artisan test --filter=test_super_admin_sees_failed_jobs_table_on_health_page -v
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/health/index.blade.php
git commit -m "fix(admin): keep health page stateful while browsing failed jobs"
```

---

### Task 4: Optional (recommended): Expose “Clear failed jobs” button

**Files:**
- Modify: `resources/views/admin/health/index.blade.php`

- [ ] **Step 1: Add a second form posting to `admin.health.jobs.clear`**

Add a button near the existing retry-all form:

```blade
<form method="POST" action="{{ route('admin.health.jobs.clear') }}">
    @csrf
    <button type="submit" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 rounded-xl text-xs font-black uppercase tracking-widest">
        Clear Failed Jobs
    </button>
</form>
```

- [ ] **Step 2: Run full test suite**

Run:

```bash
php artisan test
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/health/index.blade.php
git commit -m "feat(admin): expose clear failed jobs action on health page"
```

---

## Plan Self-Review

- Spec coverage: table + search + modal + retry/forget + remove full reload are all covered by Tasks 2–3.
- Placeholder scan: no TBD/TODO steps; all code and commands are included.
- Consistency: component name `admin.health.failed-jobs-table` matches `App\\Livewire\\Admin\\Health\\FailedJobsTable`.

