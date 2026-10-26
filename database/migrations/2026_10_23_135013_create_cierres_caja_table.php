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
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->date('fecha');
            $table->string('estado', 10)->default('abierto');
            $table->decimal('valor_base_caja', 15, 2)->nullable();
            $table->decimal('valor_efectivo_ventas', 15, 2)->nullable();
            $table->decimal('valor_pago_gestores', 15, 2)->nullable();
            $table->decimal('valor_gastos', 15, 2)->nullable();
            $table->decimal('valor_efectivo_esperado', 15, 2)->nullable();
            $table->decimal('valor_efectivo_contado', 15, 2)->nullable();
            $table->decimal('valor_diferencia_efectivo', 15, 2)->nullable();
            $table->decimal('valor_ventas_datafono', 15, 2)->nullable();
            $table->decimal('valor_reportado_datafono', 15, 2)->nullable();
            $table->decimal('valor_diferencia_datafono', 15, 2)->nullable();
            $table->decimal('valor_ventas_qr', 15, 2)->nullable();
            $table->decimal('valor_reportado_qr', 15, 2)->nullable();
            $table->decimal('valor_diferencia_qr', 15, 2)->nullable();
            $table->decimal('valor_a_consignar', 15, 2)->nullable();
            $table->text('justificacion')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();

            $table->unique(['sede_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
