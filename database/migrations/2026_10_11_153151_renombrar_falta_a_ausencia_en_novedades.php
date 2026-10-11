<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Las novedades guardadas como "falta" pasan al tipo "ausencia" (issue #23).
     */
    public function up(): void
    {
        DB::table('novedades')->where('tipo', 'falta')->update(['tipo' => 'ausencia']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('novedades')->where('tipo', 'ausencia')->update(['tipo' => 'falta']);
    }
};
