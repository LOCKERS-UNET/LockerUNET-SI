<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El inventario traía un locker con tipo "Large" (mayúscula). La validación solo
     * acepta small, mid y large, así que ese locker no se podía editar desde el panel.
     */
    public function up(): void
    {
        DB::table('lockers')
            ->whereNotIn('locker_type', ['small', 'mid', 'large'])
            ->update(['locker_type' => DB::raw('LOWER(TRIM(locker_type))')]);
    }

    public function down(): void
    {
        // No se revierte: no se sabe qué tipos venían con mayúsculas
    }
};
