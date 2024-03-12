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
        Schema::create('prayertimes', function (Blueprint $table) {
            $table->id();
            $table->string('city_external_id')->index();
            $table->date('prayer_at')->index();
            $table->unique([
                'city_external_id', 'prayer_at',
            ]);
            $table->time('imsak');
            $table->time('subuh');
            $table->time('terbit');
            $table->time('dhuha');
            $table->time('dzuhur');
            $table->time('ashar');
            $table->time('maghrib');
            $table->time('isya');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayertimes');
    }
};
