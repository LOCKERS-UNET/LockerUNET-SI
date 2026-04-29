<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('locker_id');
            $table->unsignedBigInteger('request_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('NULL mientras esté activo');
            $table->string('assignment_status', 20)->default('active')->comment('active | released');
            $table->unsignedBigInteger('created_by')->comment('Admin que asignó');
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('locker_id')->references('locker_id')->on('lockers')->onDelete('cascade');
            $table->foreign('request_id')->references('request_id')->on('locker_requests')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_assignments');
    }
};
