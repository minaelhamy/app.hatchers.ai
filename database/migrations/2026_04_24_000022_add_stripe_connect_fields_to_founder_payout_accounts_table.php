<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('founder_payout_accounts', function (Blueprint $table) {
            $table->string('stripe_account_id', 191)->nullable()->after('bank_currency');
            $table->string('stripe_onboarding_status', 32)->default('not_started')->after('stripe_account_id');
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_onboarding_status');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            $table->timestamp('stripe_details_submitted_at')->nullable()->after('stripe_payouts_enabled');
            $table->timestamp('stripe_payouts_enabled_at')->nullable()->after('stripe_details_submitted_at');
            $table->json('meta_json')->nullable()->after('notes');

            $table->index('stripe_account_id');
            $table->index('stripe_onboarding_status');
        });
    }

    public function down(): void
    {
        Schema::table('founder_payout_accounts', function (Blueprint $table) {
            $table->dropIndex(['stripe_account_id']);
            $table->dropIndex(['stripe_onboarding_status']);
            $table->dropColumn([
                'stripe_account_id',
                'stripe_onboarding_status',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_details_submitted_at',
                'stripe_payouts_enabled_at',
                'meta_json',
            ]);
        });
    }
};
