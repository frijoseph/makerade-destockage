<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One spin per e-mail and wheel; the prize is drawn server-side.
     */
    public function up(): void
    {
        Schema::create('wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('prize');
            $table->string('coupon_code', 40)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['popup_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheel_spins');
    }
};
