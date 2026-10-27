<?php

use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create(['nombre' => 'Sede Villavicencio', 'ciudad' => 'Villavicencio']);
});

function leerCsv(string $contenido): array
{
    $sinMarca = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

    return array_map(fn (string $linea) => str_getcsv($linea, ';'), array_values(array_filter(explode("\n", $sinMarca))));
}

// Issue #82, criterio 1
test('el CSV de cursos trae los cursos de todas las sedes del día elegido', function () {
    $otraSede = Sede::factory()->create(['nombre' => 'Sede Bogotá']);
    Curso::factory()->for($this->sede)->create([
        'fecha' => '2026-10-15', 'nombre_cliente' => 'Carlos Pérez', 'numero_documento' => '1006123456',
        'codigo_infraccion' => 'C', 'valor_curso' => 350000, 'porcentaje_descuento' => 50,
        'forma_pago' => 'mixto', 'valor_efectivo' => 100000, 'valor_datafono' => 150000, 'valor_qr' => 100000,
        'referido' => 'gestor', 'valor_pago_gestor' => 20000, 'hora_inicio' => '08:00', 'hora_fin' => '10:00',
    ]);
    Curso::factory()->for($otraSede)->create(['fecha' => '2026-10-15']);
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-16']);

    $respuesta = $this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => '2026-10-15']));

    $respuesta->assertOk()->assertDownload('cursos-2026-10-15.csv');
    expect($respuesta->headers->get('Content-Type'))->toContain('text/csv');

    $filas = leerCsv($respuesta->streamedContent());

    expect($filas)->toHaveCount(3)
        ->and($filas[0][0])->toBe('Fecha')
        ->and($filas[0])->toContain('Pago al gestor', 'Secretaría de tránsito')
        ->and($filas[1])->toHaveCount(count($filas[0]))
        ->and($filas[1][0])->toBe('2026-10-15')
        ->and($filas[1][1])->toBe('Sede Villavicencio')
        ->and($filas[1][2])->toBe('Villavicencio')
        ->and($filas[1][5])->toBe('Carlos Pérez')
        ->and($filas[1][11])->toBe('08:00')
        ->and($filas[1][13])->toBe('C')
        ->and($filas[1][14])->toBe('350000')
        ->and($filas[1][15])->toBe('50')
        ->and($filas[1][16])->toBe('Mixto')
        ->and($filas[1][17])->toBe('100000')
        ->and($filas[1][18])->toBe('150000')
        ->and($filas[1][19])->toBe('100000')
        ->and($filas[1][20])->toBe('Gestor')
        ->and($filas[1][21])->toBe('20000')
        ->and($filas[2][1])->toBe('Sede Bogotá');
});

// Issue #82, criterio 1
test('el CSV lleva la marca UTF-8 para que Excel muestre bien las tildes', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => 'María Peña']);

    $contenido = $this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => '2026-10-15']))->streamedContent();

    expect(str_starts_with($contenido, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($contenido)->toContain('María Peña')->toContain('Secretaría de tránsito');
});

// Issue #82, criterio 1
test('los decimales usan coma como en Excel en español', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'valor_curso' => 350000.5, 'valor_efectivo' => 350000.5]);

    $filas = leerCsv($this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => '2026-10-15']))->streamedContent());

    expect($filas[1][14])->toBe('350000,5');
});

// Issue #82, criterio 2
test('un texto que parece fórmula se neutraliza para que Excel no lo ejecute', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => '=HYPERLINK("http://malo.example","clic")']);
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => '+CMD', 'correo' => '@otro.com']);

    $cursos = leerCsv($this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => '2026-10-15']))->streamedContent());
    $soats = leerCsv($this->actingAs($this->gerente)->get(route('pos.soats.exportar', ['fecha' => '2026-10-15']))->streamedContent());

    expect($cursos[1][5])->toStartWith("'=")
        ->and($soats[1][5])->toBe("'+CMD")
        ->and($soats[1][7])->toBe("'@otro.com");
});

// Issue #137, criterio 1
test('un celular con prefijo internacional se exporta sin alterar', function () {
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'celular' => '+573001234567', 'nombre_cliente' => '-1+1']);

    $soats = leerCsv($this->actingAs($this->gerente)->get(route('pos.soats.exportar', ['fecha' => '2026-10-15']))->streamedContent());

    expect($soats[1][6])->toBe('+573001234567')
        ->and($soats[1][5])->toBe("'-1+1");
});

// Issue #82, criterio 1
test('sin fecha el CSV trae los registros de hoy y el nombre lleva la fecha', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today()]);
    Curso::factory()->for($this->sede)->create(['fecha' => today()->subDay()]);

    $respuesta = $this->actingAs($this->gerente)->get(route('pos.cursos.exportar'));

    $respuesta->assertDownload('cursos-'.today()->toDateString().'.csv');
    expect(leerCsv($respuesta->streamedContent()))->toHaveCount(2);
});

// Issue #82, criterio 1
test('un día sin registros descarga solo los encabezados', function () {
    expect(leerCsv($this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => '2026-01-01']))->streamedContent()))->toHaveCount(1);
});

// Issue #82, criterio 1
test('el CSV de SOAT trae los SOAT de todas las sedes del día elegido', function () {
    Soat::factory()->for($this->sede)->create([
        'fecha' => '2026-10-15', 'nombre_cliente' => 'Ana Ríos', 'placa' => 'ABC123', 'correo' => 'ana@example.com',
        'valor_soat' => 580000, 'forma_pago' => 'mixto', 'valor_efectivo' => 280000, 'valor_qr' => 300000,
    ]);
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-16']);

    $respuesta = $this->actingAs($this->gerente)->get(route('pos.soats.exportar', ['fecha' => '2026-10-15']));

    $respuesta->assertOk()->assertDownload('soat-2026-10-15.csv');

    $filas = leerCsv($respuesta->streamedContent());

    expect($filas)->toHaveCount(2)
        ->and($filas[0])->toContain('Placa', 'Valor en QR')
        ->and($filas[1])->toHaveCount(count($filas[0]))
        ->and($filas[1][1])->toBe('Sede Villavicencio')
        ->and($filas[1][5])->toBe('Ana Ríos')
        ->and($filas[1][8])->toBe('ABC123')
        ->and($filas[1][9])->toBe('580000')
        ->and($filas[1][10])->toBe('Mixto')
        ->and($filas[1][11])->toBe('280000')
        ->and($filas[1][12])->toBe('300000');
});

// Issue #82, criterio 4
test('la fecha de la exportación debe ser válida', function () {
    $this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => 'ayer']))->assertSessionHasErrors('fecha');
    $this->actingAs($this->gerente)->get(route('pos.soats.exportar', ['fecha' => 'ayer']))->assertSessionHasErrors('fecha');
});

// Issue #82, criterio 4
test('el usuario de una sede no puede exportar y el visitante va al login', function () {
    $usuarioSede = User::factory()->paraSede($this->sede)->create();

    $this->actingAs($usuarioSede)->get(route('pos.cursos.exportar'))->assertForbidden();
    $this->actingAs($usuarioSede)->get(route('pos.soats.exportar'))->assertForbidden();

    auth()->logout();
    $this->get(route('pos.cursos.exportar'))->assertRedirect(route('login'));
});

// Issue #82, criterio 3
test('el CSV de cierres trae el arqueo de cada sede y marca las que no cerraron', function () {
    $bogota = Sede::factory()->create(['nombre' => 'Sede Bogotá']);
    Sede::factory()->create(['nombre' => 'Sede Zulia', 'es_activa' => false]);
    $cierre = CierreCaja::factory()->cerrado()->for($this->sede)->create([
        'fecha' => '2026-10-15', 'valor_base_caja' => 200000, 'valor_efectivo_ventas' => 680000, 'valor_pago_gestores' => 30000,
        'valor_gastos' => 50000, 'valor_efectivo_esperado' => 800000, 'valor_efectivo_contado' => 790000,
        'valor_diferencia_efectivo' => -10000, 'valor_ventas_datafono' => 100000, 'valor_reportado_datafono' => 100000,
        'valor_diferencia_datafono' => 0, 'valor_ventas_qr' => 350000, 'valor_reportado_qr' => 350000, 'valor_diferencia_qr' => 0,
        'valor_a_consignar' => 590000, 'justificacion' => 'Faltó un recibo', 'cerrado_por' => $this->gerente->id,
        'cerrado_en' => '2026-10-15 17:45:00',
    ]);
    Consignacion::factory()->create(['cierre_caja_id' => $cierre->id, 'valor_consignado' => 590000, 'fecha_consignacion' => '2026-10-16', 'referencia' => 'REF-77']);

    $respuesta = $this->actingAs($this->gerente)->get(route('pos.cierres.exportar', ['fecha' => '2026-10-15']));

    $respuesta->assertOk()->assertDownload('cierres-de-caja-2026-10-15.csv');

    $filas = leerCsv($respuesta->streamedContent());

    expect($filas)->toHaveCount(3)
        ->and($filas[0])->toContain('Efectivo esperado', 'A consignar', 'Referencia de consignación')
        ->and($filas[1])->toHaveCount(count($filas[0]))
        ->and($filas[1][1])->toBe('Sede Bogotá')
        ->and($filas[1][3])->toBe('Sin cerrar')
        ->and($filas[1][4])->toBe('')
        ->and($filas[2][1])->toBe('Sede Villavicencio')
        ->and($filas[2][3])->toBe('Cerrado')
        ->and($filas[2][4])->toBe('200000')
        ->and($filas[2][8])->toBe('800000')
        ->and($filas[2][9])->toBe('790000')
        ->and($filas[2][10])->toBe('-10000')
        ->and($filas[2][17])->toBe('590000')
        ->and($filas[2][18])->toBe('Faltó un recibo')
        ->and($filas[2][19])->toBe($this->gerente->name)
        ->and($filas[2][20])->toBe('2026-10-15 17:45')
        ->and($filas[2][21])->toBe('590000')
        ->and($filas[2][22])->toBe('2026-10-16')
        ->and($filas[2][23])->toBe('REF-77')
        ->and($bogota->exists)->toBeTrue();
});

// Issue #82, criterio 3
test('el CSV de cierres incluye una sede inactiva solo si cerró ese día', function () {
    $inactiva = Sede::factory()->create(['nombre' => 'Sede Zulia', 'es_activa' => false]);
    CierreCaja::factory()->cerrado()->for($inactiva)->create(['fecha' => '2026-10-15', 'valor_a_consignar' => 1000]);

    $conCierre = leerCsv($this->actingAs($this->gerente)->get(route('pos.cierres.exportar', ['fecha' => '2026-10-15']))->streamedContent());
    $sinCierre = leerCsv($this->actingAs($this->gerente)->get(route('pos.cierres.exportar', ['fecha' => '2026-10-16']))->streamedContent());

    expect(collect($conCierre)->pluck(1)->all())->toContain('Sede Zulia')
        ->and(collect($sinCierre)->pluck(1)->all())->not->toContain('Sede Zulia');
});

// Issue #82, criterio 3
test('un día abierto con gastos se exporta como sin cerrar y sin cifras', function () {
    CierreCaja::factory()->for($this->sede)->create(['fecha' => '2026-10-15']);

    $filas = leerCsv($this->actingAs($this->gerente)->get(route('pos.cierres.exportar', ['fecha' => '2026-10-15']))->streamedContent());

    expect($filas[1][3])->toBe('Sin cerrar')->and($filas[1][17])->toBe('');
});

// Issue #82, criterio 4
test('solo el administrador exporta los cierres y la fecha debe ser válida', function () {
    $usuarioSede = User::factory()->paraSede($this->sede)->create();

    $this->actingAs($usuarioSede)->get(route('pos.cierres.exportar'))->assertForbidden();
    $this->actingAs($this->gerente)->get(route('pos.cierres.exportar', ['fecha' => 'ayer']))->assertSessionHasErrors('fecha');

    auth()->logout();
    $this->get(route('pos.cierres.exportar'))->assertRedirect(route('login'));
});

// Issue #138, criterio 1
test('exportar las ventas del día no consulta la sede por cada fila', function () {
    $otra = Sede::factory()->create();
    Curso::factory()->count(3)->for($this->sede)->create(['fecha' => '2026-10-15']);
    Curso::factory()->count(3)->for($otra)->create(['fecha' => '2026-10-15']);
    Soat::factory()->count(3)->for($this->sede)->create(['fecha' => '2026-10-15']);
    Soat::factory()->count(3)->for($otra)->create(['fecha' => '2026-10-15']);

    $consultasDeSedes = 0;
    DB::listen(function ($consulta) use (&$consultasDeSedes) {
        $consultasDeSedes += str_contains($consulta->sql, 'from "sedes"') ? 1 : 0;
    });

    $this->actingAs($this->gerente)->get(route('pos.cursos.exportar', ['fecha' => '2026-10-15']))->streamedContent();
    $this->actingAs($this->gerente)->get(route('pos.soats.exportar', ['fecha' => '2026-10-15']))->streamedContent();

    expect($consultasDeSedes)->toBeLessThanOrEqual(2);
});
