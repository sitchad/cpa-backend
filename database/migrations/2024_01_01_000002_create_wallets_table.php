<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->decimal('balance', 18, 8)->default(0);
            $table->decimal('pending', 18, 8)->default(0);
            $table->decimal('total_earned', 18, 8)->default(0);
            $table->decimal('total_withdrawn', 18, 8)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('wallets'); }
};
