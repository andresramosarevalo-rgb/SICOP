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
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('codigo_infraccion', 1)->default('A')->after('tipo_comparendo');
        });

        foreach (DB::table('cursos')->get(['id', 'tipo_comparendo']) as $curso) {
            $letra = strtoupper(substr(trim($curso->tipo_comparendo), -1));

            if (preg_match('/^[A-J]$/', $letra) === 1) {
                DB::table('cursos')->where('id', $curso->id)->update(['codigo_infraccion' => $letra]);
            }
        }

        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('tipo_comparendo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('tipo_comparendo', 100)->default('')->after('codigo_infraccion');
        });

        DB::table('cursos')->update(['tipo_comparendo' => DB::raw("'Tipo ' || codigo_infraccion")]);

        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('codigo_infraccion');
        });
    }
};
