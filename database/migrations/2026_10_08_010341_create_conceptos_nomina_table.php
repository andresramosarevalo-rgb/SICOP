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
        Schema::create('conceptos_nomina', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->string('tipo', 20);
            $table->string('forma_calculo', 20);
            $table->decimal('valor_base', 15, 2)->nullable();
            $table->decimal('porcentaje_base', 5, 2)->nullable();
            $table->boolean('es_constitutivo_salario')->default(false);
            $table->boolean('es_sistema')->default(false);
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos_nomina');
    }
};
