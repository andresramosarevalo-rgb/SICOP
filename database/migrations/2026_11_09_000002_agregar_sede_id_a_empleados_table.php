<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los empleados existentes quedan sin sede: solo el super administrador los ve hasta asignarles una.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('area_id')
                ->constrained('sedes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });
    }
};
