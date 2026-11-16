<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los empleados existentes pueden completar su información bancaria después.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('banco', 255)->nullable();
            $table->string('numero_cuenta_bancaria', 255)->nullable();
            $table->string('tipo_cuenta_bancaria', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['banco', 'numero_cuenta_bancaria', 'tipo_cuenta_bancaria']);
        });
    }
};
