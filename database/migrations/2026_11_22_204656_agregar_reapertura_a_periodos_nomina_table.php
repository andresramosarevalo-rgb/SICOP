<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un periodo cerrado se puede reabrir para corregirlo: queda registrado quién lo reabrió y cuándo.
     */
    public function up(): void
    {
        Schema::table('periodos_nomina', function (Blueprint $table) {
            $table->foreignId('reabierto_por')->nullable()->after('fecha_cierre')->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_reapertura')->nullable()->after('reabierto_por');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_nomina', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reabierto_por');
            $table->dropColumn('fecha_reapertura');
        });
    }
};
