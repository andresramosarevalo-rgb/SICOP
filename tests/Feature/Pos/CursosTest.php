<?php

use App\Enums\FormaPago;
use App\Enums\Referido;
use App\Enums\Rol;
use App\Enums\TipoDocumento;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
});

function datosCurso(array $sobrescribir = []): array
{
    return [
        'fecha' => today()->toDateString(),
        'tipo_documento' => 'CC',
        'numero_documento' => '1006123456',
        'nombre_cliente' => 'Carlos Pérez',
        'celular' => '3101234567',
        'correo' => 'carlos@example.com',
        'numero_certificado' => 'CERT-001',
        'numero_comparendo' => '50001000000012345678',
        'secretaria_transito' => 'Secretaría de Tránsito de Villavicencio',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'referido' => 'gestor',
        'codigo_infraccion' => 'C',
        'valor_curso' => '350000',
        'porcentaje_descuento' => 50,
        'forma_pago' => 'efectivo',
        ...$sobrescribir,
    ];
}

// Issue #69, criterio 1
test('la sede registra un curso y este aparece en su listado', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso())
        ->assertRedirect(route('pos.sedes.cursos.index', $this->sede));

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cursos/Index')
            ->where('sede.id', $this->sede->id)
            ->has('cursos', 1)
            ->where('cursos.0.fecha', today()->toDateString())
            ->where('cursos.0.codigo_infraccion', 'C')
            ->where('cursos.0.valor_curso', '350000.00')
            ->where('cursos.0.porcentaje_descuento', '50.00')
            ->where('cursos.0.tipo_documento', 'CC')
            ->where('cursos.0.numero_documento', '1006123456')
            ->where('cursos.0.nombre_cliente', 'Carlos Pérez'));
});

// Issue #69, criterio 5
test('el listado de una sede no muestra los cursos de otra', function () {
    Curso::factory()->create();
    Curso::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('cursos', 1));
});

// Issue #69, criterio 2
test('el formulario ofrece los tipos de documento y los descuentos de 25 % y 50 %', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.create', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cursos/Create')
            ->has('tiposDocumento', count(TipoDocumento::cases()))
            ->where('porcentajesDescuento', [25, 50]));
});

// Issue #69, criterio 2
test('los datos del curso son obligatorios', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), [])
        ->assertSessionHasErrors(['fecha', 'tipo_documento', 'numero_documento', 'nombre_cliente', 'celular', 'correo', 'numero_certificado', 'numero_comparendo', 'secretaria_transito', 'hora_inicio', 'hora_fin', 'codigo_infraccion', 'valor_curso', 'porcentaje_descuento', 'forma_pago']);

    expect(Curso::count())->toBe(0);
});

// Issue #69, criterio 2
test('el descuento solo puede ser 25 % o 50 %', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['porcentaje_descuento' => 30]))
        ->assertSessionHasErrors(['porcentaje_descuento' => 'El descuento debe ser 25 % o 50 %.']);
});

// Issue #69, criterio 2
test('el valor debe ser mayor que cero y la fecha no puede ser futura', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['valor_curso' => 0, 'fecha' => today()->addDay()->toDateString()]))
        ->assertSessionHasErrors(['valor_curso', 'fecha']);
});

// Issue #69, criterio 3
test('una sede inactiva no puede registrar cursos', function () {
    $sede = Sede::factory()->create(['es_activa' => false]);

    $this->actingAs($this->gerente)->get(route('pos.sedes.cursos.create', $sede))->assertForbidden();
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $sede), datosCurso())
        ->assertForbidden();

    expect(Curso::count())->toBe(0);
});

// Issue #69, criterio 4
test('se pueden editar los datos de un curso', function () {
    $curso = Curso::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->put(route('pos.cursos.update', $curso), datosCurso(['valor_curso' => '420000', 'porcentaje_descuento' => 25]))
        ->assertRedirect(route('pos.sedes.cursos.index', $this->sede));

    expect($curso->fresh())
        ->valor_curso->toBe('420000.00')
        ->porcentaje_descuento->toBe('25.00')
        ->nombre_cliente->toBe('Carlos Pérez');
});

// Issue #67, criterio 2
test('el cajero recibe 403 en los cursos', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)->get(route('pos.sedes.cursos.index', $this->sede))->assertForbidden();
    $this->actingAs($cajero)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso())
        ->assertForbidden();
});

// Issue #69, criterio 2
test('el tipo de documento debe ser válido y el número solo lleva letras y números', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['tipo_documento' => 'XX', 'numero_documento' => '1.006 123']))
        ->assertSessionHasErrors(['tipo_documento', 'numero_documento']);
});

// Issue #73, criterio 1
test('el curso guarda los datos de contacto, el comparendo, el horario y el referido', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso())
        ->assertSessionHasNoErrors();

    expect(Curso::first())
        ->celular->toBe('3101234567')
        ->correo->toBe('carlos@example.com')
        ->numero_certificado->toBe('CERT-001')
        ->numero_comparendo->toBe('50001000000012345678')
        ->secretaria_transito->toBe('Secretaría de Tránsito de Villavicencio')
        ->hora_inicio->toStartWith('08:00')
        ->hora_fin->toStartWith('10:00')
        ->referido->toBe(Referido::Gestor);
});

// Issue #73, criterio 3
test('el referido es opcional pero debe ser R5 o Gestor', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['referido' => null]))
        ->assertSessionHasNoErrors();

    expect(Curso::first()->referido)->toBeNull();

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['referido' => 'otro']))
        ->assertSessionHasErrors('referido');
});

// Issue #73, criterios 2 y 4
test('la hora de fin debe ser posterior a la de inicio y el correo válido', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['hora_inicio' => '10:00', 'hora_fin' => '08:00', 'correo' => 'no-es-correo']))
        ->assertSessionHasErrors(['hora_fin' => 'La hora de fin debe ser posterior a la de inicio.', 'correo']);
});

// Issue #74, criterio 1
test('con una forma de pago simple todo el valor va al medio elegido', function (string $forma, array $esperado) {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['forma_pago' => $forma]))
        ->assertSessionHasNoErrors();

    expect(Curso::first())
        ->forma_pago->toBe(FormaPago::from($forma))
        ->valor_efectivo->toBe($esperado[0])
        ->valor_datafono->toBe($esperado[1])
        ->valor_qr->toBe($esperado[2]);
})->with([
    'efectivo' => ['efectivo', ['350000.00', '0.00', '0.00']],
    'datáfono' => ['datafono', ['0.00', '350000.00', '0.00']],
    'QR' => ['qr', ['0.00', '0.00', '350000.00']],
]);

// Issue #74, criterio 2
test('el pago mixto guarda el valor por medio y debe sumar el valor del curso', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso([
            'forma_pago' => 'mixto', 'valor_efectivo' => 100000, 'valor_datafono' => 150000, 'valor_qr' => 100000,
        ]))
        ->assertSessionHasNoErrors();

    expect(Curso::first())
        ->valor_efectivo->toBe('100000.00')
        ->valor_datafono->toBe('150000.00')
        ->valor_qr->toBe('100000.00');
});

// Issue #74, criterio 2
test('el pago mixto con una suma distinta al valor del curso se rechaza', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso([
            'forma_pago' => 'mixto', 'valor_efectivo' => 100000, 'valor_datafono' => 100000, 'valor_qr' => 100000,
        ]))
        ->assertSessionHasErrors(['forma_pago' => 'Los valores por medio deben sumar el valor total.']);

    expect(Curso::count())->toBe(0);
});

// Issue #74, criterio 2
test('el pago mixto exige los valores por medio y la forma de pago debe ser válida', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['forma_pago' => 'mixto']))
        ->assertSessionHasErrors(['valor_efectivo', 'valor_datafono', 'valor_qr']);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['forma_pago' => 'cheque']))
        ->assertSessionHasErrors('forma_pago');
});

// Issue #74, criterio 1
test('al editar el curso cambiando a una forma simple se reasignan los valores por medio', function () {
    $curso = Curso::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->put(route('pos.cursos.update', $curso), datosCurso(['forma_pago' => 'qr', 'valor_curso' => '420000']))
        ->assertSessionHasNoErrors();

    expect($curso->fresh())
        ->valor_efectivo->toBe('0.00')
        ->valor_qr->toBe('420000.00');
});

// Issue #75, criterio 1
test('el tipo de comparendo debe ser una letra de la A a la J', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['codigo_infraccion' => 'K']))
        ->assertSessionHasErrors('codigo_infraccion');

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCurso(['codigo_infraccion' => 'J']))
        ->assertSessionHasNoErrors();
});

// Issue #74, criterio 5
test('el listado de cursos muestra la forma de pago de cada registro', function () {
    Curso::factory()->for($this->sede)->create(['forma_pago' => 'qr', 'valor_efectivo' => 0, 'valor_qr' => 350000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cursos.0.forma_pago', 'qr')
            ->where('cursos.0.valor_qr', '350000.00'));
});

test('el listado muestra los cursos de la sede, los más recientes primero', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-14', 'nombre_cliente' => 'Antiguo', 'valor_curso' => 350000, 'porcentaje_descuento' => 50]);
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => 'Reciente']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cursos/Index')
            ->where('sede.id', $this->sede->id)
            ->has('cursos', 2)
            ->where('cursos.0.nombre_cliente', 'Reciente')
            ->where('cursos.1.nombre_cliente', 'Antiguo')
            ->where('cursos.1.valor_curso', '350000.00')
            ->where('cursos.1.porcentaje_descuento', '50.00'));
});
