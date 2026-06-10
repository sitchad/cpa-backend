<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 18, 8);
            $table->string('method')->default('USDT_TRC20');
            $table->string('wallet_address');
            $table->enum('status', ['pending','processing','completed','rejected'])->default('pending');
            $table->string('tx_hash')->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('withdrawals'); }
};
