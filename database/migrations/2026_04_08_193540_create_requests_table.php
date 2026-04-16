<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('locker_id');
            $table->string('request_status', 20)->default('pending')->comment('pending | approved | rejected');
            $table->timestamp('requested_at')->useCurrent();
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('Admin que procesó');
            $table->timestamp('reviewed_at')->nullable();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('locker_id')->references('locker_id')->on('lockers')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_requests');
    }
};
