<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperatives', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('county');
            $t->string('place')->nullable();
            $t->string('registration_no')->nullable(); // self-declared, not verified by FreshRoute
            $t->string('contact_phone')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('phone')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->string('role')->index();
            $t->string('locale', 5)->default('en');
            $t->string('county')->nullable();
            $t->string('place')->nullable();
            $t->string('business_name')->nullable();
            $t->string('buyer_type')->nullable();
            $t->foreignId('coop_id')->nullable()->constrained('cooperatives')->nullOnDelete();
            $t->string('origin')->default('user_submitted');
            $t->timestamp('consent_at')->nullable();
            $t->json('notification_prefs')->nullable();
            $t->text('totp_secret')->nullable();
            $t->timestamp('totp_confirmed_at')->nullable();
            $t->text('recovery_codes')->nullable();
            $t->timestamp('suspended_at')->nullable();
            $t->timestamp('deletion_requested_at')->nullable();
            $t->timestamp('anonymised_at')->nullable();
            $t->timestamp('last_active_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('cooperatives');
    }
};
