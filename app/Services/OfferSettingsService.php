<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * OfferSettingsService
 * ════════════════════
 * Single source of truth for every configurable offer parameter.
 *
 * Every parameter is declared in the SCHEMA constant below. Each entry
 * defines:
 *   key       – the settings table key
 *   type      – int | float | bool | json | string
 *   default   – fallback when the key is absent from the DB
 *   label     – human-readable label for the Admin UI
 *   group     – logical grouping for the UI
 *
 * Adding a new offer parameter is done exclusively here. No code changes
 * are needed in Team::getPlanLimit() or Team::hasFeature() – those methods
 * call this service generically.
 *
 * Usage
 * ─────
 *   $svc = app(OfferSettingsService::class);
 *
 *   // Get a typed value
 *   $svc->get('offer_message_limit')           // → int 5000
 *   $svc->get('offer_included_features')       // → array ['chat', ...]
 *   $svc->get('offer_enabled')                 // → bool true
 *
 *   // Check if a limit key is registered
 *   $svc->limitKey('message_limit')            // → 'offer_message_limit'
 *
 *   // Get full schema (for Admin UI)
 *   $svc->schema()                             // → Collection of all parameters
 */
class OfferSettingsService
{
    // ------------------------------------------------------------------
    // Parameter Schema Registry
    // ------------------------------------------------------------------
    // Each entry: key, type, default, label, group
    //
    // To add a new parameter:
    //   1. Add a row here.
    //   2. Add a migration or seed to insert the default into `settings`.
    //   3. Add a slug entry to LIMIT_MAP or FEATURE_KEY if it drives
    //      getPlanLimit()/hasFeature() respectively.
    // ------------------------------------------------------------------

    private const SCHEMA = [
        // ── Global toggle ─────────────────────────────────────────────
        [
            'key' => 'offer_enabled',
            'type' => 'bool',
            'default' => true,
            'label' => 'Offer Enabled',
            'group' => 'global',
        ],

        // ── Time window ───────────────────────────────────────────────
        [
            'key' => 'offer_start_at',
            'type' => 'string',
            'default' => null,
            'label' => 'Offer Start (ISO-8601 UTC)',
            'group' => 'window',
        ],
        [
            'key' => 'offer_end_at',
            'type' => 'string',
            'default' => null,
            'label' => 'Offer End (ISO-8601 UTC)',
            'group' => 'window',
        ],

        // ── Trial length & credits ─────────────────────────────────────
        [
            'key' => 'offer_trial_months',
            'type' => 'int',
            'default' => 6,
            'label' => 'Trial Length (months)',
            'group' => 'trial',
        ],
        [
            'key' => 'offer_initial_credit',
            'type' => 'float',
            'default' => 5.00,
            'label' => 'Welcome Credit (USD)',
            'group' => 'trial',
        ],

        // ── Quantity limits ────────────────────────────────────────────
        [
            'key' => 'offer_message_limit',
            'type' => 'int',
            'default' => 5000,
            'label' => 'Outbound Messages / Month',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_agent_limit',
            'type' => 'int',
            'default' => 5,
            'label' => 'Agent (Team Member) Seats',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_whatsapp_limit',
            'type' => 'int',
            'default' => 2,
            'label' => 'WhatsApp Numbers Allowed',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_contacts_limit',
            'type' => 'int',
            'default' => 0,        // 0 = unlimited
            'label' => 'Contacts Limit (0 = unlimited)',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_campaign_limit',
            'type' => 'int',
            'default' => 0,
            'label' => 'Campaigns / Month (0 = unlimited)',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_automation_limit',
            'type' => 'int',
            'default' => 0,
            'label' => 'Active Automations (0 = unlimited)',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_call_minutes_limit',
            'type' => 'int',
            'default' => 0,
            'label' => 'Call Minutes / Month (0 = unlimited)',
            'group' => 'limits',
        ],
        [
            'key' => 'offer_team_limit',
            'type' => 'int',
            'default' => 1,
            'label' => 'Max Teams Allowed',
            'group' => 'limits',
        ],

        // ── Feature flags ──────────────────────────────────────────────
        [
            'key' => 'offer_included_features',
            'type' => 'json',
            'default' => ['chat', 'contacts', 'templates', 'campaigns', 'automations', 'analytics', 'commerce', 'ai', 'webhooks'],
            'label' => 'Included Features (JSON array)',
            'group' => 'features',
        ],

        // ── Identity / Anti-abuse ──────────────────────────────────────
        [
            'key' => 'identity_domain_limit',
            'type' => 'int',
            'default' => 1,
            'label' => 'Max Trials per Business Domain',
            'group' => 'identity',
        ],
        [
            'key' => 'identity_public_domains',
            'type' => 'string',
            'default' => '',
            'label' => 'Extra Public Domains (comma-separated)',
            'group' => 'identity',
        ],
    ];

    /**
     * Maps getPlanLimit() keys → offer settings keys.
     * Add an entry here whenever a new quantitative limit is added to SCHEMA.
     */
    private const LIMIT_MAP = [
        'message_limit' => 'offer_message_limit',
        'agent_limit' => 'offer_agent_limit',
        'whatsapp_limit' => 'offer_whatsapp_limit',
        'contacts_limit' => 'offer_contacts_limit',
        'contact_limit' => 'offer_contacts_limit',
        'campaign_limit' => 'offer_campaign_limit',
        'automation_limit' => 'offer_automation_limit',
        'call_minutes' => 'offer_call_minutes_limit',
        'max_call_minutes_per_month' => 'offer_call_minutes_limit',
        'team_limit' => 'offer_team_limit',
    ];

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Get a typed value for any registered offer setting.
     * Falls back to the schema default if the key is not found in the DB.
     */
    public function get(string $key, mixed $fallback = null): mixed
    {
        $schema = $this->findSchema($key);
        $default = $schema ? $schema['default'] : $fallback;
        $raw = get_setting($key, null);

        if ($raw === null) {
            return $default;
        }

        return $this->cast($raw, $schema['type'] ?? 'string');
    }

    /**
     * Resolve a getPlanLimit() slug to the settings key that holds its value,
     * then return its typed value. Returns null if there is no offer mapping.
     */
    public function limitValue(string $limitKey): ?int
    {
        $settingsKey = self::LIMIT_MAP[$limitKey] ?? null;

        if ($settingsKey === null) {
            return null;
        }

        $schema = $this->findSchema($settingsKey);
        $default = $schema ? $schema['default'] : 0;
        $raw = get_setting($settingsKey, null);

        return (int) ($raw ?? $default);
    }

    /**
     * Returns the settings key for a given limit slug (for manual look-up).
     */
    public function limitKey(string $limitSlug): ?string
    {
        return self::LIMIT_MAP[$limitSlug] ?? null;
    }

    /**
     * Returns whether a limit slug is tracked by the offer system.
     */
    public function hasLimitMapping(string $limitKey): bool
    {
        return isset(self::LIMIT_MAP[$limitKey]);
    }

    /**
     * Returns the full list of included feature slugs from settings.
     *
     * @return string[]
     */
    public function includedFeatures(): array
    {
        $raw = get_setting('offer_included_features');

        if (! $raw) {
            return (array) $this->findSchema('offer_included_features')['default'];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Returns all parameter definitions for the Admin UI.
     * Each value is enriched with the current DB value.
     *
     * @return Collection<int, array{key: string, type: string, default: mixed, label: string, group: string, current: mixed}>
     */
    public function schema(): Collection
    {
        return collect(self::SCHEMA)->map(function (array $entry) {
            $entry['current'] = $this->get($entry['key'], $entry['default']);

            return $entry;
        });
    }

    /**
     * Returns all schema entries for a given group.
     */
    public function schemaGroup(string $group): Collection
    {
        return $this->schema()->filter(fn ($e) => $e['group'] === $group)->values();
    }

    /**
     * Returns all defined limit-map keys (for discovery by callers).
     *
     * @return array<string, string>
     */
    public function limitMap(): array
    {
        return self::LIMIT_MAP;
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    private function findSchema(string $key): ?array
    {
        foreach (self::SCHEMA as $entry) {
            if ($entry['key'] === $key) {
                return $entry;
            }
        }

        return null;
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => is_array($value) ? $value : (json_decode($value, true) ?? []),
            default => $value,
        };
    }
}
