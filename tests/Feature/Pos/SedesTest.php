<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\TarifaGestor;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route as Rutas;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
});

function datosSede(array $sobrescribir = []): array
{
    return [
        'nombre' => 'Sede Villavicencio',
        'ciudad' => 'Villavicencio',
        'direccion' => 'Calle 15 # 30-20',
        'valor_base_caja' => '200000',
        ...$sobrescribir,
    ];
}

// Issue #68, criterio 1
test('al guardar una sede nueva queda activa y aparece en el listado', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.store'), datosSede())
        ->assertRedirect(route('pos.sedes.index'));

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Sedes/Index')
            ->has('sedes', 1)
            ->where('sedes.0.nombre', 'Sede Villavicencio')
            ->where('sedes.0.ciudad', 'Villavicencio')
            ->where('sedes.0.direccion', 'Calle 15 # 30-20')
            ->where('sedes.0.es_activa', true));
});

// Issue #68, criterio 1
test('nombre, ciudad y dirección son obligatorios', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.store'), [])
        ->assertSessionHasErrors(['nombre', 'ciudad', 'direccion']);

    expect(Sede::count())->toBe(0);
});

// Issue #68, criterio 2
test('no se puede crear una sede con un nombre que ya existe', function () {
    Sede::factory()->create(['nombre' => 'Sede Villavicencio']);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.store'), datosSede())
        ->assertSessionHasErrors(['nombre' => 'Ya existe una sede con ese nombre.']);

    expect(Sede::count())->toBe(1);
});

// Issue #68, criterio 2
test('se pueden editar los datos de la sede y conservar su propio nombre', function () {
    $sede = Sede::factory()->create(['nombre' => 'Sede Villavicencio']);

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.update', $sede), datosSede(['ciudad' => 'Acacías']))
        ->assertRedirect(route('pos.sedes.index'));

    expect($sede->fresh()->ciudad)->toBe('Acacías');
});

// Issue #68, criterio 3
test('al desactivar una sede se conserva pero deja de estar activa', function () {
    $sede = Sede::factory()->create();

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.update', $sede), datosSede(['nombre' => $sede->nombre, 'es_activa' => false]))
        ->assertRedirect(route('pos.sedes.index'));

    expect(Sede::count())->toBe(1)
        ->and(Sede::activas()->count())->toBe(0);
});

// Issue #67, criterios 1 y 2
test('solo el administrador puede gestionar el punto de venta', function (?Rol $rol, bool $puedeGestionar) {
    $user = User::factory()->create(['rol' => $rol]);

    expect(Gate::forUser($user)->allows('gestionar-pos'))->toBe($puedeGestionar);
})->with([
    'administrador' => [Rol::Administrador, true],
    'auxiliar de nómina' => [Rol::AuxiliarNomina, false],
    'contador' => [Rol::Contador, false],
    'cajero' => [Rol::Cajero, false],
    'sin rol' => [null, false],
]);

// Issue #67, criterio 1
test('el administrador ve el módulo y el menú de punto de venta', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.inicio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Inicio/Index')
            ->where('auth.permisos.gestionarPos', true));
});

// Issue #67, criterios 2 y 3
test('todas las rutas de POS responden 403 al cajero y redirigen al visitante al login', function () {
    $modelos = [
        'sede' => Sede::factory()->create(),
        'curso' => Curso::factory()->create(),
        'soat' => Soat::factory()->create(),
        'tarifa' => TarifaGestor::factory()->create(),
    ];
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $rutas = collect(Rutas::getRoutes()->getRoutes())
        ->filter(fn (Route $ruta) => str_starts_with((string) $ruta->getName(), 'pos.'))
        ->map(fn (Route $ruta) => [
            $ruta->getName(),
            collect($ruta->methods())->reject(fn (string $metodo) => $metodo === 'HEAD')->first(),
            route($ruta->getName(), collect($ruta->parameterNames())->mapWithKeys(fn (string $parametro) => [$parametro => $modelos[$parametro]])->all()),
        ]);
    $expuestas = [];

    foreach ($rutas as [$nombre, $metodo, $url]) {
        $this->app['auth']->forgetGuards();
        $comoVisitante = $this->call($metodo, $url);
        $comoCajero = $this->actingAs($cajero)->call($metodo, $url);

        if (! $comoVisitante->isRedirect(route('login')) || $comoCajero->getStatusCode() !== 403) {
            $expuestas[] = "{$metodo} {$nombre}";
        }
    }

    expect($rutas)->toHaveCount(24)
        ->and($expuestas)->toBe([]);
});

// Issue #68, criterio 4
test('el listado muestra las sedes ordenadas por nombre con su estado', function () {
    Sede::factory()->create(['nombre' => 'Sede Zulia', 'es_activa' => false]);
    Sede::factory()->create(['nombre' => 'Sede Bogotá', 'ciudad' => 'Bogotá', 'direccion' => 'Carrera 10 # 20-30']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Sedes/Index')
            ->has('sedes', 2)
            ->where('sedes.0.nombre', 'Sede Bogotá')
            ->where('sedes.0.ciudad', 'Bogotá')
            ->where('sedes.0.direccion', 'Carrera 10 # 20-30')
            ->where('sedes.0.es_activa', true)
            ->where('sedes.1.nombre', 'Sede Zulia')
            ->where('sedes.1.es_activa', false));
});
