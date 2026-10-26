<?php

use App\Enums\CodigoInfraccion;
use App\Enums\Rol;
use App\Models\Sede;
use App\Models\TarifaGestor;
use App\Models\User;
use Database\Seeders\PosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
});

// Issue #75, criterio 5
test('la pantalla lista los diez tipos de comparendo con su tarifa base', function () {
    $this->seed(PosSeeder::class);

    $this->actingAs($this->gerente)
        ->get(route('pos.tarifas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Tarifas/Index')
            ->where('sedeId', null)
            ->has('filas', 10)
            ->where('filas.0.codigo', 'A')
            ->where('filas.0.valor_base', '0.00')
            ->where('filas.1.valor_base', '10000.00')
            ->where('filas.2.valor_base', '20000.00')
            ->where('filas.3.valor_base', '30000.00')
            ->where('filas.7.valor_base', '5000.00'));
});

// Issue #75, criterio 5
test('al elegir una sede muestra su ajuste junto a la tarifa base', function () {
    TarifaGestor::factory()->create(['codigo_infraccion' => 'C', 'valor_pago_gestor' => 20000]);
    $ajuste = TarifaGestor::factory()->paraSede($this->sede)->create(['codigo_infraccion' => 'C', 'valor_pago_gestor' => 25000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.tarifas.index', ['sede_id' => $this->sede->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sedeId', $this->sede->id)
            ->where('filas.2.valor_base', '20000.00')
            ->where('filas.2.ajuste.id', $ajuste->id)
            ->where('filas.2.ajuste.valor_pago_gestor', '25000.00')
            ->where('filas.0.ajuste', null));
});

// Issue #75, criterio 5
test('el gerente crea y actualiza la tarifa base sin duplicarla', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.tarifas.store'), ['codigo_infraccion' => 'B', 'valor_pago_gestor' => '10000'])
        ->assertRedirect(route('pos.tarifas.index'));

    $this->actingAs($this->gerente)
        ->post(route('pos.tarifas.store'), ['codigo_infraccion' => 'B', 'valor_pago_gestor' => '12000']);

    expect(TarifaGestor::count())->toBe(1)
        ->and(TarifaGestor::first())
        ->sede_id->toBeNull()
        ->codigo_infraccion->toBe(CodigoInfraccion::B)
        ->valor_pago_gestor->toBe('12000.00');
});

// Issue #75, criterio 5
test('el gerente fija un ajuste para una sede sin cambiar la base', function () {
    TarifaGestor::factory()->create(['codigo_infraccion' => 'B', 'valor_pago_gestor' => 10000]);

    $this->actingAs($this->gerente)
        ->post(route('pos.tarifas.store'), ['codigo_infraccion' => 'B', 'sede_id' => $this->sede->id, 'valor_pago_gestor' => '15000'])
        ->assertRedirect(route('pos.tarifas.index', ['sede_id' => $this->sede->id]));

    expect(TarifaGestor::count())->toBe(2)
        ->and(TarifaGestor::whereNull('sede_id')->first()->valor_pago_gestor)->toBe('10000.00')
        ->and(TarifaGestor::where('sede_id', $this->sede->id)->first()->valor_pago_gestor)->toBe('15000.00');
});

// Issue #75, criterio 5
test('el gerente quita el ajuste de una sede pero no puede eliminar la tarifa base', function () {
    $base = TarifaGestor::factory()->create();
    $ajuste = TarifaGestor::factory()->paraSede($this->sede)->create();

    $this->actingAs($this->gerente)
        ->delete(route('pos.tarifas.destroy', $ajuste))
        ->assertRedirect(route('pos.tarifas.index', ['sede_id' => $this->sede->id]));

    $this->actingAs($this->gerente)
        ->delete(route('pos.tarifas.destroy', $base))
        ->assertForbidden();

    expect(TarifaGestor::pluck('id')->all())->toBe([$base->id]);
});

// Issue #75, criterio 5
test('la tarifa necesita un tipo válido, una sede registrada y un valor no negativo', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.tarifas.store'), ['codigo_infraccion' => 'K', 'sede_id' => 999, 'valor_pago_gestor' => '-5'])
        ->assertSessionHasErrors(['codigo_infraccion', 'sede_id', 'valor_pago_gestor']);

    $this->actingAs($this->gerente)
        ->post(route('pos.tarifas.store'), [])
        ->assertSessionHasErrors(['codigo_infraccion', 'valor_pago_gestor']);

    expect(TarifaGestor::count())->toBe(0);
});

// Issue #75, criterio 6
test('el usuario de una sede no puede ver ni cambiar las tarifas del gestor', function () {
    $usuarioSede = User::factory()->paraSede($this->sede)->create();

    $this->actingAs($usuarioSede)->get(route('pos.tarifas.index'))->assertForbidden();
    $this->actingAs($usuarioSede)
        ->post(route('pos.tarifas.store'), ['codigo_infraccion' => 'B', 'valor_pago_gestor' => '1'])
        ->assertForbidden();
});

// Issue #75, criterio 5
test('el seeder carga las tarifas base de referencia sin duplicarlas', function () {
    $this->seed(PosSeeder::class);
    $this->seed(PosSeeder::class);

    expect(TarifaGestor::whereNull('sede_id')->count())->toBe(10)
        ->and(TarifaGestor::whereNull('sede_id')->where('codigo_infraccion', 'D')->first()->valor_pago_gestor)->toBe('30000.00');
});
