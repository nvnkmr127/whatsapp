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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_id')) {
                $table->string('order_id')->nullable()->comment('Meta Order ID')->after('team_id');
            }
            if (!Schema::hasColumn('orders', 'contact_id')) {
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete()->after('order_id');
            }
            if (!Schema::hasColumn('orders', 'items')) {
                $table->json('items')->after('contact_id');
            }
            if (!Schema::hasColumn('orders', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->after('items');
            }
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency')->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('pending')->after('currency');
            }
            if (!Schema::hasColumn('orders', 'payment_details')) {
                $table->json('payment_details')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safety: Do not drop columns to avoid data loss on rollback of a fix
    }
};
