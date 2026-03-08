<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // Percentage
            $table->boolean('is_active')->default(true);
            $table->json('payment_details')->nullable(); // Bank info, PayPal, etc.
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // The referred user
            $table->string('visitor_ip')->nullable();
            $table->string('status')->default('pending'); // pending, converted, paid, rejected
            $table->decimal('earnings', 10, 2)->default(0);
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('affiliates');
    }
};
