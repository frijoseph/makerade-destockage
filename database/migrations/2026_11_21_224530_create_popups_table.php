<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storefront campaigns shown as pop-ups: promo, newsletter, spin-to-win wheel.
     */
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 20)->default('promo');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('image')->nullable();
            $table->string('coupon_code', 40)->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->string('trigger', 20)->default('delay');
            $table->unsignedSmallInteger('trigger_value')->default(8);
            $table->string('frequency', 20)->default('once');
            $table->json('pages')->nullable();
            $table->string('devices', 20)->default('all');
            $table->string('accent', 9)->default('#237a32');
            $table->json('segments')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
