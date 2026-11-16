<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('icon');
            $t->string('bg', 9);
            $t->timestamps();
        });

        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('color', 9);
            $t->string('style', 20)->default('plain');
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('variant');
            $t->string('brand');
            $t->string('category')->index();
            $t->decimal('price', 8, 2);
            $t->decimal('old_price', 8, 2)->nullable();
            $t->unsignedTinyInteger('off')->default(0);
            $t->decimal('rating', 2, 1)->default(4.5);
            $t->unsignedInteger('reviews')->default(0);
            $t->string('kind', 20);
            $t->string('c1', 9);
            $t->string('c2', 9);
            $t->string('label');
            $t->boolean('is_new')->default(false);
            $t->boolean('hot')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
