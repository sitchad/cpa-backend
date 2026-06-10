<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['user','admin'])->default('user');
            $table->enum('status', ['active','banned','suspended'])->default('active');
            $table->string('referral_code', 10)->unique()->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->text('ban_reason')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
