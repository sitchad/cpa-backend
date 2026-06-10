<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('offer_id');
            $table->string('click_id', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('country', 2)->nullable();
            $table->enum('status', ['clicked','converted','fraud','expired'])->default('clicked');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('offer_id')->references('id')->on('offers')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('clicks'); }
};
