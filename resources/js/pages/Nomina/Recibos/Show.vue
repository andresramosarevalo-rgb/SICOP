<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ReciboNominaController from '@/actions/App/Http/Controllers/Nomina/ReciboNominaController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import TablaDetallesRecibo from '@/components/Nomina/TablaDetallesRecibo.vue';
import { Button } from '@/components/ui/button';
import { formatearFecha, formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type {
    DetalleReciboNomina,
    Empleado,
    PeriodoNomina,
    ReciboNomina,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Periodos', href: nomina.periodos.index() },
            { title: 'Comprobante de pago', href: '' },
        ],
    },
});

defineProps<{
    recibo: ReciboNomina;
    periodo: Pick<
        PeriodoNomina,
        'id' | 'periodicidad_pago' | 'fecha_inicio' | 'fecha_fin' | 'estado'
    >;
    empleado: Pick<
        Empleado,
        | 'nombres'
        | 'apellidos'
        | 'tipo_documento'
        | 'numero_documento'
        | 'cargo'
        | 'email'
    > & {
        area: string;
    };
    devengos: DetalleReciboNomina[];
    deducciones: DetalleReciboNomina[];
}>();

const claseBotonSecundario =
    'boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card';

function imprimir(): void {
    window.print();
}
</script>

<template>
    <Head
        :title="`Comprobante de pago - ${empleado.nombres} ${empleado.apellidos}`"
    />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Recibo de nómina
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    {{ empleado.nombres }} {{ empleado.apellidos }} ·
                    {{ empleado.tipo_documento }}
                    {{ empleado.numero_documento }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5 print:hidden">
                <Button
                    variant="outline"
                    as-child
                    :class="claseBotonSecundario"
                >
                    <Link :href="nomina.periodos.show(periodo.id)"
                        >Volver al periodo</Link
                    >
                </Button>
                <Button
                    variant="outline"
                    :class="claseBotonSecundario"
                    @click="imprimir"
                    >Imprimir</Button
                >
                <Form
                    v-if="
                        periodo.estado === 'cerrado' &&
                        recibo.fecha_envio &&
                        recibo.es_vigente
                    "
                    v-bind="ReciboNominaController.reenviar.form(recibo.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        :disabled="processing"
                        class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
                        >Reenviar por correo</Button
                    >
                </Form>
            </div>
        </header>

        <p
            v-if="!recibo.es_vigente"
            class="rv rounded-[18px] border border-brand-gold/55 bg-cream px-5 py-4 text-sm leading-[1.6]"
            style="--i: 2"
        >
            <strong>Liquidación anterior.</strong> Este comprobante fue
            reemplazado
            <template v-if="recibo.reemplazado_en"
                >el {{ formatearFecha(recibo.reemplazado_en) }}</template
            >
            por una nueva liquidación del periodo y se conserva solo para
            consulta. El comprobante vigente está en el periodo.
        </p>

        <section
            class="rv rounded-[22px] border bg-card px-7 py-6"
            style="--i: 2"
        >
            <dl
                class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-5"
            >
                <div>
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        Periodo
                    </dt>
                    <dd class="mt-1 text-base leading-[normal] font-semibold">
                        {{ formatearFecha(periodo.fecha_inicio) }} –
                        {{ formatearFecha(periodo.fecha_fin) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        Días pagados
                    </dt>
                    <dd class="mt-1 text-base leading-[normal] font-semibold">
                        {{ recibo.dias_liquidados }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        Cargo y área
                    </dt>
                    <dd class="mt-1 text-base leading-[normal] font-semibold">
                        {{ empleado.cargo }} · {{ empleado.area }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        Salario base mensual
                    </dt>
                    <dd class="mt-1 text-base leading-[normal] font-semibold">
                        {{ formatearPesos(recibo.valor_salario_base) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        Comprobante
                    </dt>
                    <dd class="mt-1">
                        <span
                            class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                            :class="
                                recibo.fecha_envio
                                    ? 'bg-ok-bg text-success'
                                    : 'bg-cream text-warning'
                            "
                            >{{
                                recibo.fecha_envio ? 'Enviado' : 'Pendiente'
                            }}</span
                        >
                    </dd>
                </div>
            </dl>
        </section>

        <div class="flex flex-wrap gap-6">
            <TablaDetallesRecibo
                titulo="Devengados"
                :detalles="devengos"
                :indice="3"
            />
            <TablaDetallesRecibo
                titulo="Deducciones"
                :detalles="deducciones"
                :indice="4"
            />
        </div>

        <section class="rv flex flex-wrap items-stretch gap-4" style="--i: 5">
            <div
                class="min-w-0 flex-[1_1_220px] rounded-[18px] border bg-card p-5"
            >
                <p class="text-sm text-muted-foreground">Total devengado</p>
                <p
                    class="mt-1.5 font-heading text-2xl leading-[normal] font-bold"
                >
                    {{ formatearPesos(recibo.valor_total_devengado) }}
                </p>
            </div>
            <div
                class="min-w-0 flex-[1_1_220px] rounded-[18px] border bg-card p-5"
            >
                <p class="text-sm text-muted-foreground">Total deducciones</p>
                <p
                    class="mt-1.5 font-heading text-2xl leading-[normal] font-bold"
                >
                    {{ formatearPesos(recibo.valor_total_deducciones) }}
                </p>
            </div>
            <div
                class="min-w-0 flex-[1_1_220px] rounded-[18px] border border-brand-gold/55 bg-cream p-5"
            >
                <p class="text-sm text-muted-foreground">Neto a pagar</p>
                <p
                    class="mt-1.5 font-heading text-[28px] leading-[normal] font-extrabold"
                >
                    {{ formatearPesos(recibo.valor_neto) }}
                </p>
            </div>
        </section>
    </PaginaPos>
</template>
