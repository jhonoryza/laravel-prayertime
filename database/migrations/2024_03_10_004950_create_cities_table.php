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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('province_external_id')->nullable()->index();
            $table->string('external_id')->index();
            $table->string('name')->index();
            $table->decimal('latitude', 10, 5)->default(0);
            $table->decimal('longitude', 10, 5)->default(0);
            $table->index([
                'province_external_id', 'name',
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
