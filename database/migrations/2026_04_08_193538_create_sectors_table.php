<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id('sector_id');
            $table->unsignedBigInteger('building_id');
            $table->string('sector_name', 50)->comment('Ej: Piso 1, Planta Baja');

            $table->foreign('building_id')->references('building_id')->on('buildings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};
