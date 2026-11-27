<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodities', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name_en');
            $t->string('name_sw');
            $t->string('unit_en');
            $t->string('unit_sw');
            $t->unsignedInteger('unit_kg');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('farms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('county');
            $t->string('place');
            $t->decimal('area_acres', 8, 2)->nullable();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
        });

        Schema::create('crop_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->string('variety')->nullable();
            $t->unsignedInteger('capacity_bags')->default(0); // expected bags per season
            $t->timestamps();
        });

        Schema::create('harvest_calendar_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $t->date('harvest_on')->index();
            $t->unsignedInteger('expected_bags')->nullable();
            $t->string('note')->nullable();
            $t->timestamp('reminded_at')->nullable();
            $t->timestamps();
        });

        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->string('variety')->nullable();
            $t->unsignedInteger('quantity_bags');
            $t->date('harvest_date');
            $t->date('available_from');
            $t->date('available_to')->nullable();
            $t->string('county')->index();
            $t->string('place');
            $t->string('quality_grade', 1);
            $t->unsignedInteger('price_minor'); // KES cents per bag
            $t->string('storage_requirements')->nullable();
            $t->string('status')->default('active')->index();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
            $t->unique(['user_id', 'client_uuid']);
        });

        Schema::create('listing_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('mime', 40);
            $t->unsignedInteger('bytes');
            $t->string('sha256', 64);
            $t->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedInteger('quantity_bags');
            $t->text('message')->nullable();
            $t->text('reply')->nullable();
            $t->string('status')->default('sent')->index();
            $t->string('origin')->default('user_submitted');
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
            $t->unique(['buyer_id', 'client_uuid']);
        });

        Schema::create('saved_suppliers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['buyer_id', 'supplier_id']);
        });

        Schema::create('buyer_needs', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->unsignedInteger('quantity_bags');
            $t->string('county');
            $t->date('needed_by');
            $t->unsignedInteger('max_price_minor')->nullable();
            $t->string('min_grade', 1)->default('C');
            $t->string('status')->default('open')->index();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
            $t->unique(['buyer_id', 'client_uuid']);
        });

        Schema::create('storage_facilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('county')->index();
            $t->string('place');
            $t->unsignedInteger('capacity_bags');
            $t->unsignedInteger('free_bags');
            $t->smallInteger('temp_min_c')->nullable();
            $t->smallInteger('temp_max_c')->nullable();
            $t->string('temperature_status')->default('self_declared'); // self_declared|verified|not_applicable
            $t->timestamp('verified_at')->nullable();
            $t->unsignedInteger('price_minor')->default(0); // KES cents per bag per week
            $t->string('contact_phone')->nullable();
            $t->json('commodities')->nullable();
            $t->boolean('active')->default(true);
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
        });

        Schema::create('storage_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('facility_id')->constrained('storage_facilities')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('bags');
            $t->date('from_date');
            $t->string('status')->default('sent'); // sent|accepted|declined
            $t->timestamps();
        });

        Schema::create('transporter_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('company_name');
            $t->string('vehicle_type');
            $t->unsignedInteger('capacity_bags');
            $t->text('routes');
            $t->unsignedInteger('base_price_minor')->default(0);
            $t->string('availability_note')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamp('verified_at')->nullable();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
        });

        Schema::create('delivery_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('transporter_profile_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('bags');
            $t->string('pickup_place');
            $t->string('dropoff_place');
            $t->date('pickup_date');
            $t->string('status')->default('requested')->index();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
            $t->unique(['requester_id', 'client_uuid']);
        });

        Schema::create('price_sources', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('url');
            $t->string('licence_status')->default('unconfirmed');
            $t->text('licence_note')->nullable();
            $t->string('origin')->default('officially_published'); // officially_published|synthetic
            $t->timestamps();
        });

        Schema::create('price_import_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('price_source_id')->constrained();
            $t->string('file_name');
            $t->unsignedInteger('rows_read')->default(0);
            $t->unsignedInteger('rows_imported')->default(0);
            $t->unsignedInteger('rows_rejected')->default(0);
            $t->json('errors')->nullable();
            $t->timestamps();
        });

        Schema::create('official_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('commodity_id')->constrained();
            $t->foreignId('price_source_id')->constrained();
            $t->foreignId('import_batch_id')->nullable()->constrained('price_import_batches')->nullOnDelete();
            $t->string('market');
            $t->string('county')->nullable();
            $t->string('price_type')->default('wholesale'); // wholesale|retail|farmgate
            $t->string('unit');
            $t->unsignedInteger('price_minor');
            $t->date('collected_on')->index();
            $t->string('source_url');
            $t->string('licence_status');
            $t->string('origin'); // officially_published|synthetic
            $t->timestamp('retrieved_at');
            $t->timestamps();
            $t->unique(['commodity_id', 'market', 'price_type', 'collected_on', 'price_source_id'], 'official_price_unique');
        });

        Schema::create('user_prices', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->string('market');
            $t->string('unit');
            $t->unsignedInteger('price_minor');
            $t->date('observed_on');
            $t->string('note')->nullable();
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
            $t->unique(['user_id', 'client_uuid']);
        });

        Schema::create('harvest_lots', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->date('harvested_on');
            $t->unsignedInteger('harvested_bags');
            $t->unsignedInteger('sold_bags')->default(0);
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
            $t->unique(['user_id', 'client_uuid']);
        });

        Schema::create('loss_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('commodity_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained('harvest_lots')->nullOnDelete();
            $t->string('stage')->index();
            $t->string('category'); // damaged|spoiled|rejected
            $t->unsignedInteger('bags');
            $t->string('reason');
            $t->date('occurred_on');
            $t->string('origin')->default('user_submitted');
            $t->timestamps();
            $t->unique(['user_id', 'client_uuid']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('action')->index();
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('idempotency_keys', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->uuid('key');
            $t->string('type');
            $t->string('result_type')->nullable();
            $t->unsignedBigInteger('result_id')->nullable();
            $t->string('outcome'); // created|rejected
            $t->json('errors')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'key']);
        });

        Schema::create('deletion_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('requested'); // requested|cancelled|completed
            $t->timestamp('requested_at');
            $t->timestamp('due_at');
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('sms_outbox', function (Blueprint $t) { // sandbox only: nothing is sent
            $t->id();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('to');
            $t->text('body');
            $t->string('status')->default('sandbox_not_sent');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sms_outbox', 'notifications', 'deletion_requests', 'idempotency_keys', 'audit_logs', 'loss_events', 'harvest_lots', 'user_prices', 'official_prices', 'price_import_batches', 'price_sources', 'delivery_requests', 'transporter_profiles', 'storage_requests', 'storage_facilities', 'buyer_needs', 'saved_suppliers', 'enquiries', 'listing_photos', 'listings', 'harvest_calendar_entries', 'crop_profiles', 'farms', 'commodities'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
