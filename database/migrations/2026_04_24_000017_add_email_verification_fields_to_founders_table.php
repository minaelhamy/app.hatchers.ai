<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('founders', function (Blueprint $table): void {
            $table->timestamp('email_verified_at')->nullable()->after('timezone');
            $table->string('email_verification_token')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_token');
            $table->string('login_verification_token')->nullable()->after('email_verification_expires_at');
            $table->timestamp('login_verification_expires_at')->nullable()->after('login_verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('founders', function (Blueprint $table): void {
            $table->dropColumn([
                'email_verified_at',
                'email_verification_token',
                'email_verification_expires_at',
                'login_verification_token',
                'login_verification_expires_at',
            ]);
        });
    }
};
