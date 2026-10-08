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
        Schema::create('detalles_recibo_nomina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recibo_nomina_id')->constrained('recibos_nomina')->cascadeOnDelete();
            $table->foreignId('concepto_nomina_id')->constrained('conceptos_nomina')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('descripcion');
            $table->unsignedInteger('cantidad')->nullable();
            $table->decimal('valor_concepto', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_recibo_nomina');
    }
};
