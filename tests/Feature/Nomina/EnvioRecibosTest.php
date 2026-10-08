<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\EstadoPeriodo;
use App\Enums\Rol;
use App\Mail\ReciboNominaMail;
use App\Models\Contrato;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->seed(NominaSeeder::class);
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->contratos = Contrato::factory()->count(3)->create();
    $this->periodo = PeriodoNomina::factory()->create();
    app(LiquidarPeriodoNomina::class)->ejecutar($this->periodo, $this->auxiliar);
});

// Issue #17, criterio 1
test('al enviar los recibos de un periodo cerrado sale un correo por empleado y se registra la fecha de envío', function () {
    $this->periodo->forceFill(['estado' => EstadoPeriodo::Cerrado])->save();

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.enviar', $this->periodo))
        ->assertRedirect(route('nomina.periodos.show', $this->periodo));

    Mail::assertQueuedCount(3);
    foreach ($this->contratos as $contrato) {
        Mail::assertQueued(ReciboNominaMail::class, fn (ReciboNominaMail $correo) => $correo->hasTo($contrato->empleado->email)
            && $correo->recibo->empleado_id === $contrato->empleado_id);
    }
    expect(ReciboNomina::whereNull('fecha_envio')->count())->toBe(0);
});

// Issue #17, criterio 2
test('no se pueden enviar los recibos de un periodo que no está cerrado', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.enviar', $this->periodo))
        ->assertSessionHasErrors(['periodo' => 'Solo se pueden enviar los recibos de un periodo cerrado.']);

    Mail::assertNothingQueued();
    expect(ReciboNomina::whereNotNull('fecha_envio')->count())->toBe(0);
});

// Issue #17, criterio 3
test('un recibo ya enviado no se reenvía al enviar de nuevo los del periodo', function () {
    $this->periodo->forceFill(['estado' => EstadoPeriodo::Cerrado])->save();
    $enviado = ReciboNomina::first();
    $enviado->forceFill(['fecha_envio' => now()->subDay()])->save();

    $this->actingAs($this->auxiliar)->post(route('nomina.periodos.enviar', $this->periodo));

    Mail::assertQueuedCount(2);
    Mail::assertNotQueued(ReciboNominaMail::class, fn (ReciboNominaMail $correo) => $correo->recibo->is($enviado));
});

// Issue #17, criterio 3
test('un recibo ya enviado se puede reenviar a petición', function () {
    $this->periodo->forceFill(['estado' => EstadoPeriodo::Cerrado])->save();
    $enviado = ReciboNomina::first();
    $enviado->forceFill(['fecha_envio' => now()->subDay()])->save();

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.recibos.reenviar', $enviado))
        ->assertRedirect(route('nomina.periodos.show', $this->periodo));

    Mail::assertQueuedCount(1);
    Mail::assertQueued(ReciboNominaMail::class, fn (ReciboNominaMail $correo) => $correo->recibo->is($enviado));
    expect($enviado->fresh()->fecha_envio->isToday())->toBeTrue();
});

test('el correo del recibo muestra el desglose y el neto a pagar', function () {
    $recibo = ReciboNomina::first();

    $correo = new ReciboNominaMail($recibo);

    $correo->assertHasSubject('Recibo de pago de nómina 2026-02-01 a 2026-02-28');
    $correo->assertSeeInHtml('Salario');
    $correo->assertSeeInHtml('Aporte a salud');
    $correo->assertSeeInHtml('Neto a pagar: $ '.number_format((float) $recibo->valor_neto, 0, ',', '.'));
});
