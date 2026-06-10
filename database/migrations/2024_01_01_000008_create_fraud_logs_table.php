<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('fraud_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->enum('type', ['proxy_vpn','multi_account','duplicate_click','invalid_ip','suspicious_conversion','bot_detected']);
            $table->decimal('fraud_score', 5, 2)->nullable();
            $table->json('details')->nullable();
            $table->string('action_taken')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('fraud_logs'); }
};
