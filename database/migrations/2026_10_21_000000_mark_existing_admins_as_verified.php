<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Las rutas ahora exigen el correo verificado. Los admins existentes se
     * crearon desde el seeder sin email_verified_at y quedarian bloqueados.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('is_admin', true)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No se revierte: no se sabe qué admins estaban sin verificar antes
    }
};
