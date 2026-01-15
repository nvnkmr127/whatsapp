<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Products Table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'team_id')) {
                $table->foreignId('team_id')->after('id')->index();
            }
            if (!Schema::hasColumn('products', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2);
            }
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency')->default('USD');
            }
            if (!Schema::hasColumn('products', 'retailer_id')) {
                $table->string('retailer_id')->nullable()->unique()->comment('SKU or Unique ID for Catalog');
            }
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url')->nullable();
            }
            if (!Schema::hasColumn('products', 'meta_product_id')) {
                $table->string('meta_product_id')->nullable();
            }
            if (!Schema::hasColumn('products', 'url')) {
                $table->string('url')->nullable();
            }
            if (!Schema::hasColumn('products', 'availability')) {
                $table->string('availability')->default('in stock');
            }
        });

        // 2. Create Carts Table
        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
                $table->json('items')->nullable(); // [{product_id, quantity, price}]
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('currency')->default('USD');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create Orders Table
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->index();
                $table->string('order_id')->nullable()->comment('Meta Order ID');
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
                $table->json('items'); // Snapshot of items
                $table->decimal('total_amount', 10, 2);
                $table->string('currency');
                $table->string('status')->default('pending'); // pending, paid, shipped, cancelled
                $table->json('payment_details')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't strictly drop columns to avoid data loss in production, 
        // but we can drop tables if needed.
        Schema::dropIfExists('orders');
        Schema::dropIfExists('carts');
    }
};
