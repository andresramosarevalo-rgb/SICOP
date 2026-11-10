<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los administradores que existían veían todas las sedes: pasan a super administrador.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('rol', 'administrador')
            ->whereNull('sede_id')
            ->update(['rol' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('rol', 'super_admin')
            ->update(['rol' => 'administrador']);
    }
};
