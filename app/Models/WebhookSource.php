<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookSource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'auth_config' => 'array',
        'field_mappings' => 'array',
        'transformation_rules' => 'array',
        'filtering_rules' => 'array',
        'action_config' => 'array',
        'process_delay' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($source) {
            if (empty($source->slug)) {
                $source->slug = Str::slug($source->name) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function payloads()
    {
        return $this->hasMany(WebhookPayload::class);
    }

    // Helper Methods
    public function getAuthConfig($key = null, $default = null)
    {
        $config = $this->auth_config;

        if ($key === null) {
            return $config ?? [];
        }

        return data_get($config, $key, $default);
    }

    public function getFieldMapping($eventType)
    {
        return $this->field_mappings[$eventType] ?? [];
    }

    public function getTransformationRules()
    {
        return $this->transformation_rules ?? [];
    }

    /**
     * Get filtering rules
     */
    public function getFilteringRules()
    {
        return $this->filtering_rules ?? [];
    }

    /**
     * Check if payload passes filtering rules
     */
    public function checkFilters(array $payload)
    {
        $rules = $this->filtering_rules ?? [];
        if (empty($rules)) {
            return true; // No rules = pass
        }

        // We can use a simple dot notation helper here to avoid circular dependency or service instantiation
        $extractValue = function ($array, $path) {
            $keys = explode('.', $path);
            foreach ($keys as $key) {
                if (isset($array[$key])) {
                    $array = $array[$key];
                } else {
                    return null;
                }
            }
            return $array;
        };

        foreach ($rules as $rule) {
            if (empty($rule['field']) || empty($rule['operator'])) {
                continue;
            }

            $fieldValue = $extractValue($payload, $rule['field']);
            $operator = $rule['operator'];
            $targetValue = $rule['value'] ?? null;

            switch ($operator) {
                case 'equals':
                    if ((string) $fieldValue !== (string) $targetValue)
                        return false;
                    break;
                case 'not_equals':
                    if ((string) $fieldValue === (string) $targetValue)
                        return false;
                    break;
                case 'contains':
                    if (is_string($fieldValue) && !str_contains($fieldValue, (string) $targetValue))
                        return false;
                    break;
                case 'not_contains':
                    if (is_string($fieldValue) && str_contains($fieldValue, (string) $targetValue))
                        return false;
                    break;
                case 'greater_than':
                    if (!is_numeric($fieldValue) || !($fieldValue > $targetValue))
                        return false;
                    break;
                case 'less_than':
                    if (!is_numeric($fieldValue) || !($fieldValue < $targetValue))
                        return false;
                    break;
                case 'is_empty':
                    if (!empty($fieldValue))
                        return false;
                    break;
                case 'is_not_empty':
                    if (empty($fieldValue))
                        return false;
                    break;
            }
        }

        return true;
    }

    public function getActionConfig()
    {
        return $this->action_config ?? [];
    }

    public function incrementReceived()
    {
        $this->increment('total_received');
    }

    public function incrementProcessed()
    {
        $this->increment('total_processed');
    }

    public function incrementFailed()
    {
        $this->increment('total_failed');
    }

    public function getWebhookUrl()
    {
        return url("/api/v1/webhooks/inbound/{$this->slug}");
    }

    public function getSuccessRate()
    {
        if ($this->total_received == 0) {
            return 0;
        }

        return round(($this->total_processed / $this->total_received) * 100, 2);
    }
}
