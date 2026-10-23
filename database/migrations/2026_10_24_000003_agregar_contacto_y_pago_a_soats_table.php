<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('soats', function (Blueprint $table) {
            $table->string('celular', 20)->default('')->after('nombre_cliente');
            $table->string('correo', 255)->default('')->after('celular');
            $table->string('placa', 10)->default('')->after('correo');
            $table->string('forma_pago', 20)->default('efectivo');
            $table->decimal('valor_efectivo', 15, 2)->default(0);
            $table->decimal('valor_qr', 15, 2)->default(0);
        });

        DB::table('soats')->update(['valor_efectivo' => DB::raw('valor_soat')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soats', function (Blueprint $table) {
            $table->dropColumn(['celular', 'correo', 'placa', 'forma_pago', 'valor_efectivo', 'valor_qr']);
        });
    }
};
