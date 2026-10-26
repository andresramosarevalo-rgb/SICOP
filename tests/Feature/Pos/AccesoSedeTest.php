<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sede = Sede::factory()->create();
    $this->otraSede = Sede::factory()->create();
    $this->usuarioSede = User::factory()->paraSede($this->sede)->create();
});

// Issue #71, criterios 3 y 4
test('solo el administrador y el usuario de una sede pueden usar POS', function (?Rol $rol, bool $conSede, bool $puedeUsar) {
    $user = User::factory()->create(['rol' => $rol, 'sede_id' => $conSede ? $this->otraSede->id : null]);

    expect(Gate::forUser($user)->allows('usar-pos'))->toBe($puedeUsar);
})->with([
    'administrador' => [Rol::Administrador, false, true],
    'cajero con sede' => [Rol::Cajero, true, true],
    'cajero sin sede' => [Rol::Cajero, false, false],
    'contador' => [Rol::Contador, false, false],
    'auxiliar de nómina' => [Rol::AuxiliarNomina, false, false],
]);

// Issue #71, criterio 4
test('el usuario de una sede solo opera su propia sede y el administrador cualquiera', function () {
    $gerente = User::factory()->conRol(Rol::Administrador)->create();

    expect(Gate::forUser($this->usuarioSede)->allows('operar-sede', $this->sede))->toBeTrue()
        ->and(Gate::forUser($this->usuarioSede)->allows('operar-sede', $this->otraSede))->toBeFalse()
        ->and(Gate::forUser($gerente)->allows('operar-sede', $this->otraSede))->toBeTrue();
});

// Issue #71, criterio 3
test('al entrar a POS el usuario de la sede va directo a los cursos de su sede', function () {
    $this->actingAs($this->usuarioSede)
        ->get(route('pos.inicio.index'))
        ->assertRedirect(route('pos.sedes.cursos.index', $this->sede));
});

// Issue #71, criterio 3
test('al entrar al dashboard el usuario de la sede es llevado a POS', function () {
    $this->actingAs($this->usuarioSede)
        ->get(route('dashboard'))
        ->assertRedirect(route('pos.inicio.index'));
});

// Issue #71, criterio 3
test('el usuario de la sede ve el menú de POS y su sede', function () {
    $this->actingAs($this->usuarioSede)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cursos/Index')
            ->where('auth.permisos.usarPos', true)
            ->where('auth.permisos.gestionarPos', false)
            ->where('auth.permisos.gestionarNomina', false)
            ->where('auth.sedeId', $this->sede->id));
});

// Issue #71, criterio 4
test('el usuario de la sede solo ve los cursos y SOAT de su sede', function () {
    Curso::factory()->for($this->sede)->create();
    Curso::factory()->for($this->otraSede)->create();
    Soat::factory()->for($this->sede)->count(2)->create();
    Soat::factory()->for($this->otraSede)->create();

    $this->actingAs($this->usuarioSede)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('cursos', 1));

    $this->actingAs($this->usuarioSede)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('soats', 2));
});

// Issue #71, criterio 4
test('el usuario de la sede registra y edita cursos y SOAT de su sede', function () {
    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cursos.store', $this->sede), [
            'fecha' => today()->toDateString(),
            'tipo_documento' => 'CC',
            'numero_documento' => '1006123456',
            'nombre_cliente' => 'Carlos Pérez',
            'tipo_comparendo' => 'Tipo C',
            'valor_curso' => '350000',
            'porcentaje_descuento' => 50,
        ])
        ->assertRedirect(route('pos.sedes.cursos.index', $this->sede));

    $soat = Soat::factory()->for($this->sede)->create();

    $this->actingAs($this->usuarioSede)
        ->put(route('pos.soats.update', $soat), [
            'fecha' => today()->toDateString(),
            'tipo_documento' => 'CC',
            'numero_documento' => '1006123456',
            'nombre_cliente' => 'Carlos Pérez',
            'valor_soat' => '600000',
        ])
        ->assertRedirect(route('pos.sedes.soats.index', $this->sede));

    expect(Curso::where('sede_id', $this->sede->id)->count())->toBe(1)
        ->and($soat->fresh()->valor_soat)->toBe('600000.00');
});

// Issue #71, criterio 4
test('el usuario de la sede recibe 403 en otra sede', function () {
    $cursoAjeno = Curso::factory()->for($this->otraSede)->create();
    $soatAjeno = Soat::factory()->for($this->otraSede)->create();

    $this->actingAs($this->usuarioSede)->get(route('pos.sedes.cursos.index', $this->otraSede))->assertForbidden();
    $this->actingAs($this->usuarioSede)->get(route('pos.sedes.soats.create', $this->otraSede))->assertForbidden();
    $this->actingAs($this->usuarioSede)->get(route('pos.cursos.edit', $cursoAjeno))->assertForbidden();
    $this->actingAs($this->usuarioSede)->put(route('pos.soats.update', $soatAjeno), [])->assertForbidden();
});

// Issue #71, criterio 3
test('el usuario de la sede no accede a la gestión de sedes ni a nómina', function () {
    $this->actingAs($this->usuarioSede)->get(route('pos.sedes.index'))->assertForbidden();
    $this->actingAs($this->usuarioSede)->get(route('pos.sedes.edit', $this->sede))->assertForbidden();
    $this->actingAs($this->usuarioSede)
        ->put(route('pos.sedes.acceso.update', $this->sede), ['email' => 'x@example.com', 'password' => 'Clave-segura-123', 'password_confirmation' => 'Clave-segura-123'])
        ->assertForbidden();
    $this->actingAs($this->usuarioSede)->get(route('nomina.inicio.index'))->assertForbidden();
});

// Issue #71, criterio 4
test('el administrador sigue accediendo a todas las sedes', function () {
    $gerente = User::factory()->conRol(Rol::Administrador)->create();

    $this->actingAs($gerente)->get(route('pos.sedes.cursos.index', $this->otraSede))->assertOk();
    $this->actingAs($gerente)->get(route('pos.inicio.index'))->assertOk();
});

// Issue #71, criterio 5
test('al desactivar la sede su usuario pierde el acceso a POS', function () {
    $this->sede->update(['es_activa' => false]);
    $usuario = $this->usuarioSede->fresh();

    expect(Gate::forUser($usuario)->allows('usar-pos'))->toBeFalse()
        ->and(Gate::forUser($usuario)->allows('operar-sede', $this->sede->fresh()))->toBeFalse();

    $this->actingAs($usuario)->get(route('pos.sedes.cursos.index', $this->sede))->assertForbidden();
    $this->actingAs($usuario)->get(route('pos.inicio.index'))->assertForbidden();
});
