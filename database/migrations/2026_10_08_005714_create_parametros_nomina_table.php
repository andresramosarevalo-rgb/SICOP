<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parametros_nomina', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio')->unique();
            $table->decimal('valor_salario_minimo', 15, 2);
            $table->decimal('valor_auxilio_transporte', 15, 2);
            $table->decimal('valor_uvt', 15, 2);
            $table->decimal('porcentaje_salud_empleado', 5, 2);
            $table->decimal('porcentaje_pension_empleado', 5, 2);
            $table->unsignedSmallInteger('horas_mensuales');
            $table->decimal('porcentaje_recargo_hora_extra_diurna', 5, 2);
            $table->decimal('porcentaje_recargo_hora_extra_nocturna', 5, 2);
            $table->decimal('porcentaje_recargo_nocturno', 5, 2);
            $table->decimal('porcentaje_recargo_dominical_festivo', 5, 2);
            $table->decimal('porcentaje_incapacidad', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_nomina');
    }
};
