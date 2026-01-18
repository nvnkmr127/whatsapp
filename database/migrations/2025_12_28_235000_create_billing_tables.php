<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. SaaS Plans
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Basic, Pro, Enterprise
                $table->decimal('monthly_price', 10, 2);
                $table->integer('message_limit')->default(1000);
                $table->integer('agent_limit')->default(2);
                $table->timestamps();
            });
        }

        // 2. Wallets (Prepaid Credits for WhatsApp Conversations)
        if (!Schema::hasTable('team_wallets')) {
            Schema::create('team_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->decimal('balance', 10, 2)->default(0.00); // Credits
                $table->string('currency')->default('USD');
                $table->timestamps();
            });
        }

        // 3. Transactions (Invoices/Usage)
        if (!Schema::hasTable('team_transactions')) {
            Schema::create('team_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 10, 2); // Positive (Deposit) or Negative (Usage)
                $table->string('type'); // 'deposit', 'usage_charge', 'subscription_fee'
                $table->string('description');
                $table->string('invoice_number')->nullable(); // For GST/Export
                $table->timestamps();
            });
        }

        // 4. WhatsApp Conversation Windows (Meta Billing)
        if (!Schema::hasTable('whatsapp_conversations')) {
            Schema::create('whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
                $table->string('category'); // marketing, utility, authentication, service
                $table->string('wamid_start')->nullable(); // Message ID that started it
                $table->decimal('cost', 8, 4)->default(0);
                $table->dateTime('window_starts_at')->nullable();
                $table->dateTime('window_ends_at')->nullable();
                $table->timestamps();

                // Allow one open window per category per contact
                $table->index(['team_id', 'contact_id', 'category', 'window_ends_at'], 'wa_conv_idx');
            });
        }

        // 5. White-label Settings
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'branding_config')) {
                $table->json('branding_config')->nullable(); // Logo, Colors, Domain
            }
            if (!Schema::hasColumn('teams', 'gst_number')) {
                $table->string('gst_number')->nullable();
            }
            if (!Schema::hasColumn('teams', 'billing_address')) {
                $table->string('billing_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['branding_config', 'gst_number', 'billing_address']);
        });
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('team_transactions');
        Schema::dropIfExists('team_wallets');
        Schema::dropIfExists('plans');
    }
};
