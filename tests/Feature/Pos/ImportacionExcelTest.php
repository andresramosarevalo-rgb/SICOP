<?php

use App\Enums\Rol;
use App\Enums\TipoImportacion;
use App\Models\CierreCaja;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\TarifaGestor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->sede = Sede::factory()->create();
    $this->cajero = User::factory()->paraSede($this->sede)->create();
});

/**
 * Arma un Excel con los encabezados de la plantilla y las filas dadas (campo => valor).
 *
 * @param  list<array<string, mixed>>  $filas
 */
function excelDe(TipoImportacion $tipo, array $filas): UploadedFile
{
    $libro = new Spreadsheet;
    $hoja = $libro->getActiveSheet()->setTitle('Datos');
    $columnas = $tipo->columnas();

    foreach ($columnas as $indice => $columna) {
        $hoja->setCellValue([$indice + 1, 1], $columna['encabezado']);

        foreach ($filas as $numero => $fila) {
            if (array_key_exists($columna['campo'], $fila)) {
                $hoja->setCellValueExplicit([$indice + 1, $numero + 2], $fila[$columna['campo']], is_numeric($fila[$columna['campo']]) ? 'n' : 's');
            }
        }
    }

    $ruta = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($libro))->save($ruta);

    return new UploadedFile($ruta, 'datos.xlsx', null, null, true);
}

function filaCurso(array $cambios = []): array
{
    return [
        'fecha' => today()->format('d/m/Y'),
        'tipo_documento' => 'CC',
        'numero_documento' => '1122924125',
        'nombre_cliente' => 'Juan Gonzalez',
        'celular' => '3163177002',
        'correo' => 'juan@example.com',
        'numero_certificado' => 'C-1',
        'numero_comparendo' => 'M-1',
        'secretaria_transito' => 'Tránsito de Cali',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'referido' => 'Gestor',
        'codigo_infraccion' => 'C',
        'valor_curso' => 300000,
        'porcentaje_descuento' => 25,
        'forma_pago' => 'Efectivo',
        ...$cambios,
    ];
}

function filaSoat(array $cambios = []): array
{
    return [
        'fecha' => today()->format('d/m/Y'),
        'tipo_documento' => 'CC',
        'numero_documento' => '99887766',
        'nombre_cliente' => 'Ana Perez',
        'celular' => '3000000000',
        'correo' => 'ana@example.com',
        'placa' => 'abc-123',
        'valor_soat' => 580000,
        'forma_pago' => 'Mixto',
        'valor_efectivo' => 280000,
        'valor_qr' => 300000,
        ...$cambios,
    ];
}

function importar(mixed $usuario, Sede $sede, TipoImportacion $tipo, UploadedFile $archivo)
{
    return test()->actingAs($usuario)->post(route('pos.sedes.importacion.store', [$sede, $tipo->value]), ['archivo' => $archivo]);
}

test('la plantilla se descarga con los encabezados del tipo', function (TipoImportacion $tipo) {
    $respuesta = $this->actingAs($this->cajero)->get(route('pos.sedes.importacion.plantilla', [$this->sede, $tipo->value]));

    $respuesta->assertOk()->assertDownload("plantilla-{$tipo->value}.xlsx");
})->with([TipoImportacion::Cursos, TipoImportacion::Soats]);

test('cada fila del Excel se guarda como un curso individual con el pago al gestor', function () {
    TarifaGestor::factory()->create(['sede_id' => null, 'codigo_infraccion' => 'C', 'valor_pago_gestor' => 20000]);

    importar($this->cajero, $this->sede, TipoImportacion::Cursos, excelDe(TipoImportacion::Cursos, [
        filaCurso(),
        filaCurso(['numero_documento' => '555', 'nombre_cliente' => 'Otra Persona', 'referido' => null, 'forma_pago' => 'Mixto', 'valor_efectivo' => 100000, 'valor_datafono' => 100000, 'valor_qr' => 100000]),
    ]))->assertRedirect(route('pos.sedes.cursos.index', $this->sede))->assertSessionHasNoErrors();

    expect(Curso::count())->toBe(2);
    expect(Curso::where('numero_documento', '1122924125')->first())
        ->sede_id->toBe($this->sede->id)
        ->valor_curso->toBe('300000.00')
        ->valor_efectivo->toBe('300000.00')
        ->valor_pago_gestor->toBe('20000.00')
        ->hora_inicio->toBe('08:00:00');
    expect(Curso::where('numero_documento', '555')->first())->valor_pago_gestor->toBe('0.00')->valor_datafono->toBe('100000.00');
});

test('cada fila del Excel se guarda como un SOAT individual y la placa queda normalizada', function () {
    importar($this->cajero, $this->sede, TipoImportacion::Soats, excelDe(TipoImportacion::Soats, [filaSoat(), filaSoat(['numero_documento' => '1', 'forma_pago' => 'QR', 'valor_efectivo' => null, 'valor_qr' => null])]))
        ->assertRedirect(route('pos.sedes.soats.index', $this->sede));

    expect(Soat::count())->toBe(2);
    expect(Soat::where('numero_documento', '99887766')->first())->placa->toBe('ABC123')->valor_efectivo->toBe('280000.00');
    expect(Soat::where('numero_documento', '1')->first())->valor_qr->toBe('580000.00');
});

test('si una fila falla no se guarda ninguna y se indica la fila y el motivo', function () {
    importar($this->cajero, $this->sede, TipoImportacion::Cursos, excelDe(TipoImportacion::Cursos, [
        filaCurso(),
        filaCurso(['nombre_cliente' => null, 'hora_fin' => '07:00']),
    ]))->assertSessionHasErrors('importacion');

    expect(Curso::count())->toBe(0);
    $mensaje = session('errors')->first('importacion');
    expect($mensaje)->toContain('Fila 3 · Nombre del cliente')->toContain('Fila 3 · Hora de fin')->not->toContain('Fila 2');
});

test('una fila de un día cerrado no se importa', function () {
    CierreCaja::factory()->for($this->sede)->create(['fecha' => today(), 'estado' => 'cerrado']);

    importar($this->cajero, $this->sede, TipoImportacion::Soats, excelDe(TipoImportacion::Soats, [filaSoat()]))
        ->assertSessionHasErrors('importacion');

    expect(Soat::count())->toBe(0);
});

test('el pago mixto debe sumar el valor total', function () {
    importar($this->cajero, $this->sede, TipoImportacion::Soats, excelDe(TipoImportacion::Soats, [filaSoat(['valor_qr' => 1])]))
        ->assertSessionHasErrors('importacion');

    expect(Soat::count())->toBe(0);
});

test('rechaza archivos que no son de la plantilla', function () {
    importar($this->cajero, $this->sede, TipoImportacion::Cursos, excelDe(TipoImportacion::Soats, [filaSoat()]))
        ->assertSessionHasErrors('importacion');

    importar($this->cajero, $this->sede, TipoImportacion::Cursos, UploadedFile::fake()->create('datos.pdf', 10))
        ->assertSessionHasErrors('archivo');

    importar($this->cajero, $this->sede, TipoImportacion::Cursos, excelDe(TipoImportacion::Cursos, []))
        ->assertSessionHasErrors('importacion');

    expect(Curso::count() + Soat::count())->toBe(0);
});

test('solo opera la sede quien tiene acceso y la sede debe estar activa', function () {
    $otra = Sede::factory()->create();
    $archivo = fn () => excelDe(TipoImportacion::Soats, [filaSoat()]);

    importar($this->cajero, $otra, TipoImportacion::Soats, $archivo())->assertForbidden();
    $this->actingAs($this->cajero)->get(route('pos.sedes.importacion.plantilla', [$otra, 'soats']))->assertForbidden();

    $this->sede->update(['es_activa' => false]);
    importar(User::factory()->conRol(Rol::SuperAdmin)->create(), $this->sede, TipoImportacion::Soats, $archivo())->assertForbidden();

    expect(Soat::count())->toBe(0);
});
