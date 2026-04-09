<?php

namespace App\Livewire\Admin;

use App\Services\OfferSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * OfferSettings – Super Admin Livewire component
 * ════════════════════════════════════════════════
 * Controls ALL configurable knobs for the "6 Months Free" launch offer.
 * This component does NOT maintain a hardcoded list of settings — it reads
 * the full parameter schema from OfferSettingsService and renders it
 * dynamically. Adding a new offer parameter requires only:
 *
 *   1. A new entry in OfferSettingsService::SCHEMA
 *   2. A migration/seed for the settings table row
 *
 * No changes needed here.
 */
class OfferSettings extends Component
{
    // ------------------------------------------------------------------
    // Dynamic schema-driven settings
    //
    // $settings is a flat key→value map populated from the DB via the
    // OfferSettingsService schema on mount(). The Blade view iterates the
    // schema groups and binds inputs to $settings[param_key].
    // ------------------------------------------------------------------

    /** @var array<string, mixed> Live Livewire-bound values for every schema key */
    public array $settings = [];

    /** Included feature slugs (handled separately as a multi-checkbox) */
    public array $includedFeatures = [];

    // ------------------------------------------------------------------
    // Computed schema (not reactive state — populated in mount/render)
    // ------------------------------------------------------------------

    /** @var array<int, array> Full schema groupings for the view */
    public array $schemaGroups = [];

    /** All available feature toggles shown in the UI */
    public array $availableFeatures = [
        'chat' => 'Live Chat',
        'contacts' => 'CRM / Contacts',
        'templates' => 'WhatsApp Templates',
        'campaigns' => 'Marketing Campaigns',
        'automations' => 'Automations / Flows',
        'analytics' => 'Analytics',
        'commerce' => 'Commerce / Store',
        'ai' => 'AI / Knowledge Base',
        'webhooks' => 'Developer Webhooks',
        'api_access' => 'API Access',
        'flows' => 'WhatsApp Flows',
    ];

    // ------------------------------------------------------------------
    // Lifecycle
    // ------------------------------------------------------------------

    public function mount(): void
    {
        Gate::authorize('manage-settings');

        if (! Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $this->loadFromSchema();
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    public function save(): void
    {
        Gate::authorize('manage-settings');

        /** @var OfferSettingsService $svc */
        $svc = app(OfferSettingsService::class);
        $schema = $svc->schema();

        // ── Keys whose changes only affect NEW signups (write-once at claim) ──
        // offer_trial_months   → stamped as trial_ends_at ONCE at signup.
        //                        Changing this does NOT modify any existing tenant.
        // offer_initial_credit → deposited ONCE into wallet at signup.
        //                        Changing this does NOT alter past wallet transactions.
        $newSignupsOnlyKeys = ['offer_trial_months', 'offer_initial_credit'];

        // ── Keys whose changes apply live to all current trial teams ──────────
        // Read at runtime by EntitlementService. Changing them does NOT retroactively
        // modify past usage data (messages already sent are still counted),
        // but the new limit/feature gates take effect immediately.
        $liveRuntimeKeys = [
            'offer_message_limit',
            'offer_agent_limit',
            'offer_whatsapp_limit',
            'offer_contacts_limit',
            'offer_campaign_limit',
            'offer_automation_limit',
            'offer_call_minutes_limit',
            'offer_included_features',
            'offer_start_at',
            'offer_end_at',
            'identity_domain_limit',
            'identity_public_domains',
        ];

        // ── 1. Snapshot BEFORE values from DB ─────────────────────────────────
        $before = [];
        foreach ($schema as $entry) {
            $key = $entry['key'];
            $before[$key] = $key === 'offer_included_features'
                ? json_encode($svc->includedFeatures(), JSON_UNESCAPED_SLASHES)
                : get_setting($key);
        }

        // ── 2. Build validation rules ──────────────────────────────────────────
        $rules = [];
        foreach ($schema as $entry) {
            $key = $entry['key'];
            if ($key === 'offer_included_features') {
                $rules['includedFeatures'] = 'nullable|array';
                $rules['includedFeatures.*'] = 'string';

                continue;
            }
            $rules["settings.{$key}"] = match ($entry['type']) {
                'int' => 'nullable|integer|min:0',
                'float' => 'nullable|numeric|min:0',
                'bool' => 'nullable|boolean',
                default => 'nullable|string|max:1000',
            };
        }
        $rules['settings.offer_start_at'] = 'nullable|date';
        $rules['settings.offer_end_at'] = 'nullable|date|after_or_equal:settings.offer_start_at';

        $this->validate($rules);

        // ── 3. Persist every schema key ────────────────────────────────────────
        foreach ($schema as $entry) {
            $key = $entry['key'];

            if ($key === 'offer_included_features') {
                set_setting($key, json_encode($this->includedFeatures), 'system');

                continue;
            }

            $value = $this->settings[$key] ?? null;
            $value = match ($entry['type']) {
                'bool' => $value ? '1' : '0',
                'json' => is_array($value) ? json_encode($value) : ($value ?? '[]'),
                default => ($value !== null && $value !== '') ? (string) $value : null,
            };

            set_setting($key, $value, 'system');
        }

        // ── 4. Detect changes (before → after diff) ────────────────────────────
        $changes = [];
        foreach ($schema as $entry) {
            $key = $entry['key'];
            $after = $key === 'offer_included_features'
                ? json_encode($this->includedFeatures, JSON_UNESCAPED_SLASHES)
                : get_setting($key);

            if ((string) ($before[$key] ?? '') !== (string) ($after ?? '')) {
                $changes[$key] = ['before' => $before[$key], 'after' => $after];
            }
        }

        // ── 5. Flush EntitlementService cache when live-runtime keys changed ───
        // Forces re-evaluation on the NEXT request; does not touch any team's
        // stored data — only the in-request resolution cache is cleared.
        if (! empty(array_intersect(array_keys($changes), $liveRuntimeKeys))) {
            app(\App\Services\EntitlementService::class)->flush();
        }

        // ── 6. Audit log ───────────────────────────────────────────────────────
        if (! empty($changes)) {
            \App\Services\OfferAuditService::logSettingsChanged(Auth::user(), $changes);
        }

        // ── 7. Precise impact flash ────────────────────────────────────────────
        if (empty($changes)) {
            session()->flash('message', 'No changes detected — settings unchanged.');

            return;
        }

        $signupKeys = array_intersect(array_keys($changes), $newSignupsOnlyKeys);
        $runtimeKeys = array_intersect(array_keys($changes), $liveRuntimeKeys);
        $globalKeys = array_diff(array_keys($changes), $newSignupsOnlyKeys, $liveRuntimeKeys);

        $impactLines = [];
        if (! empty($signupKeys)) {
            $impactLines[] = '⏳ '.implode(', ', $signupKeys)
                .' — new signups only. Existing tenants\' trial dates and wallet credit are NOT affected.';
        }
        if (! empty($runtimeKeys)) {
            $impactLines[] = '⚡ '.implode(', ', $runtimeKeys)
                .' — live effect on all current trial teams. Past usage data is unchanged.';
        }
        if (! empty($globalKeys)) {
            $impactLines[] = '🌐 '.implode(', ', $globalKeys)
                .' — global toggle, takes effect immediately for all teams.';
        }

        session()->flash('message', count($changes).' change(s) saved successfully.');
        session()->flash('impact_lines', $impactLines);
    }

    // ------------------------------------------------------------------
    // Render
    // ------------------------------------------------------------------

    public function render()
    {
        // Refresh schema groups for the view on every render
        $svc = app(OfferSettingsService::class);

        $this->schemaGroups = $svc->schema()
            ->groupBy('group')
            ->map(fn ($items) => $items->values()->toArray())
            ->toArray();

        return view('livewire.admin.offer-settings');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function loadFromSchema(): void
    {
        /** @var OfferSettingsService $svc */
        $svc = app(OfferSettingsService::class);

        foreach ($svc->schema() as $entry) {
            $key = $entry['key'];

            if ($key === 'offer_included_features') {
                $this->includedFeatures = (array) $entry['current'];

                continue;
            }

            $this->settings[$key] = $entry['current'];
        }
    }
}
