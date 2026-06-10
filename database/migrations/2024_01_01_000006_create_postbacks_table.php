<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('postbacks', function (Blueprint $table) {
            $table->id();
            $table->string('click_id', 64)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->unsignedBigInteger('click_db_id')->nullable();
            $table->string('network')->nullable();
            $table->decimal('payout', 10, 4)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->enum('status', ['pending','approved','rejected','duplicate','fraud'])->default('pending');
            $table->json('raw_payload');
            $table->string('ip_address', 45)->nullable();
            $table->text('reject_reason')->nullable();
            $table->boolean('wallet_credited')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('postbacks'); }
};
