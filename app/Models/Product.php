<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'category_id',
        'name',
        'description',
        'price',
        'currency',
        'retailer_id',
        'image_url',
        'meta_product_id',
        'url',
        'availability',
        'stock_quantity',
        'manage_stock',
        'sync_state',
        'sync_errors',
        'is_active',
        'locked_fields',
        'last_external_update_at'
    ];

    protected $casts = [
        'manage_stock' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'locked_fields' => 'array',
        'last_external_update_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Audit the product for WhatsApp readiness.
     */
    public function getReadinessAttribute()
    {
        $issues = [];

        if (empty($this->name))
            $issues[] = "Missing Name";
        if (empty($this->price) || $this->price <= 0)
            $issues[] = "Invalid Price";
        if (empty($this->retailer_id))
            $issues[] = "Missing SKU";
        if (empty($this->image_url))
            $issues[] = "Missing Image (Required for Catalog)";
        if (strlen($this->description ?? '') < 10)
            $issues[] = "Description too short";

        // SKU Character Check (Meta preference: alphanumeric, underscores, hyphens)
        if (preg_match('/[^a-zA-Z0-9\-_]/', $this->retailer_id)) {
            $issues[] = "SKU contains invalid characters (use only A-Z, 0-9, -, _)";
        }

        if ($this->manage_stock && $this->stock_quantity <= 0 && $this->availability === 'in stock') {
            $issues[] = "Stock mismatch: Marked 'In Stock' but quantity is 0";
        }

        return [
            'is_ready' => empty($issues),
            'issues' => $issues,
            'score' => max(0, 100 - (count($issues) * 20))
        ];
    }

    public function getStatusColorAttribute()
    {
        if (!$this->is_active)
            return 'gray';
        if ($this->sync_state === 'synced')
            return 'emerald';
        if ($this->sync_state === 'failed')
            return 'rose';
        return 'amber';
    }

    /**
     * Filter incoming data based on which fields are "locked" by the local user.
     */
    public function filterIncomingSyncData(array $data): array
    {
        $locked = $this->locked_fields ?? [];

        foreach ($locked as $field) {
            unset($data[$field]);
        }

        // Always allow authority for certain infrastructure fields
        $data['last_external_update_at'] = now();

        return $data;
    }

    /**
     * Enforcement Hook: Only return products that are sellable 
     * based on store readiness and stock.
     */
    public function scopeShoppable($query)
    {
        $readinessService = app(\App\Services\CommerceReadinessService::class);
        return $readinessService->applyShoppableScope($query, $this->team);
    }

    protected static function booted()
    {
        static::updated(function ($product) {
            if ($product->wasChanged('stock_quantity') && $product->manage_stock && (int)$product->stock_quantity <= 5) {
                try {
                    // T9. low_stock trigger
                    // Usually fires for the Store Owner or Admins of the team
                    $admins = $product->team->users()->where('role', 'admin')->get();
                    if ($admins->isEmpty()) $admins = [$product->team->owner];

                    foreach ($admins as $admin) {
                        if ($admin && $admin->contact) {
                            app(\App\Services\AutomationService::class)->checkSpecialTriggers($admin->contact, 'low_stock', [
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'stock_quantity' => $product->stock_quantity,
                                'retailer_id' => $product->retailer_id
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Automation [low_stock] Trigger Failed: " . $e->getMessage());
                }
            }
        });
    }
}
