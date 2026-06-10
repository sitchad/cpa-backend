<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('network');
            $table->string('external_offer_id')->nullable();
            $table->decimal('payout', 10, 4);
            $table->string('currency', 10)->default('USD');
            $table->string('category')->nullable();
            $table->string('country_targeting')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('offer_url');
            $table->boolean('is_active')->default(true);
            $table->integer('daily_cap')->nullable();
            $table->integer('conversions_today')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('offers'); }
};
