<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('unlocode', 10)->unique();
            $table->string('name');
            $table->string('city');
            $table->string('country');
            $table->string('country_code', 2)->index();
            $table->string('currency_code', 3)->index();
            $table->enum('type', ['PORT', 'AIRPORT', 'HUB', 'WAREHOUSE'])->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone');
            $table->json('weather_snapshot')->nullable();
            $table->json('news_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

