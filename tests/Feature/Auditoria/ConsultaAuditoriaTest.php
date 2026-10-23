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
test('se filtra por módulo, sede, usuario, tipo de registro y acción', function () {
    $sede = Sede::factory()->create();
    Auditoria::query()->delete();
    Auditoria::factory()->create(['descripcion' => 'POS curso', 'modulo' => 'pos', 'etiqueta' => 'Curso', 'evento' => 'modificado', 'sede_id' => $sede->id, 'user_id' => $this->gerente->id]);
    Auditoria::factory()->create(['descripcion' => 'Nómina área', 'modulo' => 'nomina', 'etiqueta' => 'Área', 'evento' => 'creado']);

    $consultar = fn (array $filtros) => $this->actingAs($this->gerente)->get(route('admin.auditoria.index', $filtros));

    $consultar(['modulo' => 'nomina'])->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 1)->where('auditorias.data.0.descripcion', 'Nómina área'));
    $consultar(['sede_id' => $sede->id])->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 1)->where('auditorias.data.0.descripcion', 'POS curso'));
    $consultar(['user_id' => $this->gerente->id])->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 1)->where('auditorias.data.0.descripcion', 'POS curso'));
    $consultar(['etiqueta' => 'Área'])->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 1)->where('auditorias.data.0.descripcion', 'Nómina área'));
    $consultar(['evento' => 'creado'])->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 1)->where('auditorias.data.0.descripcion', 'Nómina área'));
    $consultar(['modulo' => 'pos', 'evento' => 'creado'])->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 0));
});

// Issue #81, criterio 4
test('se filtra por rango de fechas incluyendo los dos extremos', function () {
    Auditoria::factory()->create(['descripcion' => 'Día 1', 'created_at' => '2026-10-10 08:00:00']);
    Auditoria::factory()->create(['descripcion' => 'Día 2', 'created_at' => '2026-10-11 23:30:00']);
    Auditoria::factory()->create(['descripcion' => 'Día 3', 'created_at' => '2026-10-12 09:00:00']);

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['desde' => '2026-10-11', 'hasta' => '2026-10-11']))
        ->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 1)->where('auditorias.data.0.descripcion', 'Día 2'));

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['desde' => '2026-10-11']))
        ->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 2));
});

// Issue #81, criterio 4
test('los filtros inválidos se rechazan', function () {
    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['modulo' => 'otro', 'evento' => 'x', 'sede_id' => 999, 'desde' => 'ayer']))
        ->assertSessionHasErrors(['modulo', 'evento', 'sede_id', 'desde']);

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['desde' => '2026-10-12', 'hasta' => '2026-10-10']))
        ->assertSessionHasErrors(['hasta' => 'La fecha final no puede ser anterior a la inicial.']);
});

// Issue #81, criterio 4
test('el historial se pagina de 50 en 50 y conserva los filtros', function () {
    Auditoria::factory()->count(55)->create(['modulo' => 'pos']);

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['modulo' => 'pos']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('auditorias.data', 50)
            ->where('auditorias.prev_page_url', null)
            ->where('auditorias.next_page_url', fn (string $url) => str_contains($url, 'modulo=pos')));

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index', ['modulo' => 'pos', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 5)->where('auditorias.next_page_url', null));
});

// Issue #81, criterio 4
test('ofrece las sedes, los usuarios y los tipos de registro para filtrar', function () {
    Sede::factory()->create(['nombre' => 'Sede Bogotá']);
    Auditoria::query()->delete();
    Auditoria::factory()->create(['etiqueta' => 'SOAT']);
    Auditoria::factory()->create(['etiqueta' => 'Curso']);

    $this->actingAs($this->gerente)
        ->get(route('admin.auditoria.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sedes.0.nombre', 'Sede Bogotá')
            ->where('etiquetas', ['Curso', 'SOAT'])
            ->where('usuarios.0.id', $this->gerente->id));
});

// Issue #81, criterio 5
test('consultar la auditoría no genera nuevos registros de auditoría', function () {
    $this->actingAs($this->gerente)->get(route('admin.auditoria.index'))->assertOk();

    expect(Auditoria::count())->toBe(0);
});
