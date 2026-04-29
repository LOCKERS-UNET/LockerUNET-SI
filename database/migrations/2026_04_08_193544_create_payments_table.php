<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->comment('Fecha límite de pago mostrada al usuario');
            $table->string('payment_status', 20)->default('pending')->comment('pending | paid | overdue');
            $table->string('semester', 20)->nullable()->comment('Ej: 2026-1, usado en estadísticas');
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('assignment_id')->references('assignment_id')->on('locker_assignments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
