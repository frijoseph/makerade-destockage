<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phone numbers (international format) become the main identifier; e-mail turns optional.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->change();
            $table->unique('phone');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->after('name');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->unique()->after('email');
        });

        Schema::table('wheel_spins', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->after('email');
            $table->unique(['popup_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheel_spins', function (Blueprint $table) {
            $table->dropUnique(['popup_id', 'phone']);
            $table->dropColumn('phone');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn('phone');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
