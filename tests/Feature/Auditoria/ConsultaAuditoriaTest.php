<?php

use App\Enums\Rol;
use App\Models\Auditoria;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    Auditoria::query()->delete();
});

// Issue #81, criterio 5
test('solo el administrador puede ver la auditoría', function (?Rol $rol, bool $puedeVer) {
    $user = User::factory()->create(['rol' => $rol]);

    expect(Gate::forUser($user)->allows('ver-auditoria'))->toBe($puedeVer);
})->with([
    'administrador' => [Rol::Administrador, true],
    'auxiliar de nómina' => [Rol::AuxiliarNomina, false],
    'contador' => [Rol::Contador, false],
    'cajero' => [Rol::Cajero, false],
    'sin rol' => [null, false],
]);

// Issue #81, criterio 4
test('el administrador ve el historial y el menú de auditoría', function () {
    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Auditoria/Index')
            ->where('auth.permisos.verAuditoria', true));
});

// Issue #81, criterio 5
test('el usuario de una sede, el contador y el auxiliar reciben 403 y el visitante va al login', function () {
    $sede = Sede::factory()->create();

    $this->actingAs(User::factory()->paraSede($sede)->create())->get(route('admin.auditoria.index'))->assertForbidden();
    $this->actingAs(User::factory()->conRol(Rol::Contador)->create())->get(route('admin.auditoria.index'))->assertForbidden();
    $this->actingAs(User::factory()->conRol(Rol::AuxiliarNomina)->create())->get(route('admin.auditoria.index'))->assertForbidden();

    auth()->logout();
    $this->get(route('admin.auditoria.index'))->assertRedirect(route('login'));
});

// Issue #81, criterio 4
test('el historial muestra lo más reciente primero con el usuario, la sede y los cambios', function () {
    $sede = Sede::factory()->create(['nombre' => 'Sede Villavicencio']);
    Auditoria::query()->delete();
    Auditoria::factory()->create(['descripcion' => 'Antiguo', 'created_at' => now()->subDays(2)]);
    Auditoria::factory()->create([
        'descripcion' => 'Reciente',
        'user_id' => $this->gerente->id,
        'sede_id' => $sede->id,
        'cambios' => ['valor_curso' => ['antes' => '350000.00', 'despues' => '300000.00']],
        'created_at' => now(),
    ]);

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('auditorias.data', 2)
            ->where('auditorias.data.0.descripcion', 'Reciente')
            ->where('auditorias.data.0.usuario.name', $this->gerente->name)
            ->where('auditorias.data.0.sede.nombre', 'Sede Villavicencio')
            ->where('auditorias.data.0.cambios.valor_curso.antes', '350000.00')
            ->where('auditorias.data.0.cambios.valor_curso.despues', '300000.00')
            ->where('auditorias.data.1.descripcion', 'Antiguo')
            ->where('auditorias.data.1.usuario', null));
});

// Issue #81, criterio 4
test('el historial se pagina de 50 en 50', function () {
    Auditoria::factory()->count(55)->create(['modulo' => 'pos']);

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('auditorias.data', 50)
            ->where('auditorias.prev_page_url', null)
            ->where('auditorias.next_page_url', fn (string $url) => str_contains($url, 'page=2')));

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 5)->where('auditorias.next_page_url', null));
});

// Issue #81, criterio 5
test('consultar la auditoría no genera nuevos registros de auditoría', function () {
    $this->actingAs($this->gerente)->get(route('admin.auditoria.index'))->assertOk();

    expect(Auditoria::count())->toBe(0);
});
