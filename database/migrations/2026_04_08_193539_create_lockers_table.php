<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lockers', function (Blueprint $table) {
            $table->id('locker_id');
            $table->string('locker_code', 50)->unique()->comment('Ej: E-6-PB-001');
            $table->unsignedBigInteger('sector_id');
            $table->string('locker_type', 20)->comment('small | mid | large');
            $table->string('plate_number', 20)->nullable()->comment('Placa física, not si no tiene');
            $table->integer('status')->default(0)->comment('0=disponible | 1=ocupado | 2=mantenimiento');
            
            // Laravel defaults
            $table->timestamps();
            
            $table->foreign('sector_id')->references('sector_id')->on('sectors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lockers');
    }
};
