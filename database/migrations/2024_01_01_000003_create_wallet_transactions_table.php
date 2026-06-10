<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['credit','debit','pending','cancelled']);
            $table->decimal('amount', 18, 8);
            $table->decimal('balance_before', 18, 8);
            $table->decimal('balance_after', 18, 8);
            $table->string('description')->nullable();
            $table->string('reference')->unique()->nullable();
            $table->nullableMorphs('transactionable');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('wallet_transactions'); }
};
