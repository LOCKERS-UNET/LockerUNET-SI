<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rates', function (Blueprint $table) {
            $table->id('rate_id');
            $table->string('locker_type', 20)->comment('small | mid | large');
            $table->decimal('monthly_amount', 10, 2);
            $table->date('effective_from');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by');
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rates');
    }
};
