<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        // Orders of customers who already have an account stay attached to it.
        DB::statement('UPDATE orders SET user_id = (SELECT customers.user_id FROM customers WHERE customers.id = orders.customer_id) WHERE customer_id IN (SELECT id FROM customers WHERE user_id IS NOT NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
