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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->index(); // Assuming Team model exists
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('retailer_id')->unique()->comment('SKU or Unique ID for Catalog');
            $table->string('image_url')->nullable();
            $table->string('meta_product_id')->nullable();
            $table->string('url')->nullable()->comment('Link to product on website');
            $table->string('availability')->default('in stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'team_id',
                'name',
                'description',
                'price',
                'currency',
                'retailer_id',
                'image_url',
                'meta_product_id',
                'url',
                'availability'
            ]);
        });
    }
};
