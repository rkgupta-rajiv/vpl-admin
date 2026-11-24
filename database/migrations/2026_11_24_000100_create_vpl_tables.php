<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tournaments / seasons — the app is reusable across seasons.
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('season', 50);
            $table->string('code', 20)->unique();          // used as Registration ID prefix, e.g. VPL2
            $table->date('registration_start_date')->nullable();
            $table->date('registration_end_date')->nullable();
            $table->string('registration_status', 10)->default('closed'); // open | closed
            $table->text('description')->nullable();
            $table->longText('rules')->nullable();
            $table->longText('instructions')->nullable();
            $table->boolean('is_active')->default(false)->index(); // the tournament players currently see
            $table->timestamps();
        });

        // Payment settings per tournament season.
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->string('upi_id')->nullable();
            $table->string('payee_name')->nullable();
            $table->string('qr_code_path')->nullable();      // public disk
            $table->text('instructions')->nullable();
            $table->boolean('online_payment_enabled')->default(true);
            $table->timestamps();
        });

        // Player profile (one per player account; reused across seasons).
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name', 60);
            $table->string('surname', 60);
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('gender', 10)->nullable()->index();          // male | female | other
            $table->string('village', 100)->nullable()->index();
            $table->string('playing_role', 20)->nullable()->index();    // batsman | bowler | all_rounder | wicket_keeper
            $table->string('batting_style', 10)->nullable();            // right | left
            $table->string('bowling_style', 20)->nullable();            // right_arm | left_arm | na
            $table->string('tshirt_size', 5)->nullable();               // S..XXXL
            $table->timestamps();
        });

        // Uploaded documents (stored on the private disk).
        Schema::create('player_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                     // photo | aadhaar_front | aadhaar_back
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 60)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('status', 10)->default('pending'); // pending | verified | rejected
            $table->string('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'type']);
        });

        // A player's registration for one tournament season.
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('registration_no', 30)->nullable()->unique(); // generated only after payment approval
            $table->string('jersey_number', 3)->nullable();
            $table->string('jersey_name', 20)->nullable();
            $table->string('status', 10)->default('pending')->index();          // pending | approved | rejected
            $table->text('rejection_remarks')->nullable();
            $table->string('payment_status', 20)->default('payment_pending')->index();
            $table->decimal('fee_amount', 10, 2)->default(0);   // fee locked at time of registration
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'player_id']);
        });

        // Payment proofs (many attempts per registration).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('method', 10)->default('upi');          // upi | cash | gateway (future)
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id', 40)->nullable()->index(); // UPI UTR / transaction id
            $table->date('payment_date')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('status', 20)->default('proof_submitted')->index(); // proof_submitted | under_verification | payment_approved | payment_rejected
            $table->text('admin_remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('gateway', 20)->nullable();            // reserved for Razorpay etc.
            $table->string('gateway_reference')->nullable();
            $table->timestamps();
        });

        // Registration / payment / document status history.
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 15);          // registration | payment | document
            $table->string('from_status', 25)->nullable();
            $table->string('to_status', 25);
            $table->text('remarks')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Admin activity log.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60)->index();
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['subject_type', 'subject_id']);
        });

        // Key/value application settings.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // In-app notifications (Laravel database notifications).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Password reset OTPs for the player app.
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['password_reset_otps', 'notifications', 'settings', 'activity_logs', 'status_histories',
                  'payments', 'registrations', 'player_documents', 'players', 'payment_settings', 'tournaments'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
