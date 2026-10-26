<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;

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
