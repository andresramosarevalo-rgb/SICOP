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
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('celular', 20)->default('')->after('nombre_cliente');
            $table->string('correo', 255)->default('')->after('celular');
            $table->string('numero_certificado', 50)->default('')->after('correo');
            $table->string('numero_comparendo', 50)->default('')->after('numero_certificado');
            $table->string('secretaria_transito', 150)->default('')->after('numero_comparendo');
            $table->time('hora_inicio')->default('00:00:00')->after('secretaria_transito');
            $table->time('hora_fin')->default('00:00:00')->after('hora_inicio');
            $table->string('referido', 10)->nullable()->after('hora_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn(['celular', 'correo', 'numero_certificado', 'numero_comparendo', 'secretaria_transito', 'hora_inicio', 'hora_fin', 'referido']);
        });
    }
};
