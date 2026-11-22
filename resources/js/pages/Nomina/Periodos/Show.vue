<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PeriodoNominaController from '@/actions/App/Http/Controllers/Nomina/PeriodoNominaController';
import ReciboNominaController from '@/actions/App/Http/Controllers/Nomina/ReciboNominaController';
import InputError from '@/components/InputError.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import EstadoPeriodo from '@/components/Nomina/EstadoPeriodo.vue';
import { Button } from '@/components/ui/button';
import { formatearFecha, formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { PeriodoNomina, ReciboNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Periodos', href: nomina.periodos.index() },
            { title: 'Periodo', href: '' },
        ],
    },
});

const props = defineProps<{
    periodo: PeriodoNomina;
    recibos: ReciboNomina[];
}>();

const page = usePage();

const sumar = (
    campo: 'valor_total_devengado' | 'valor_total_deducciones' | 'valor_neto',
) => props.recibos.reduce((total, recibo) => total + Number(recibo[campo]), 0);

const totalNeto = computed(() => sumar('valor_neto'));

const resumen = computed(() => [
    { titulo: 'Total devengado', valor: sumar('valor_total_devengado') },
    { titulo: 'Total deducciones', valor: sumar('valor_total_deducciones') },
    { titulo: 'Neto a pagar', valor: totalNeto.value },
]);

const claseBotonSecundario =
    'boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card';
</script>

<template>
    <Head title="Periodo de nómina" />

    <PaginaPos>
        <div
            class="rv flex flex-wrap items-start justify-between gap-5 rounded-[22px] border border-brand-gold/55 bg-cream px-7 py-6"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2.5">
                <div class="flex flex-wrap items-center gap-3">
                    <h1
                        class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                    >
                        Periodo de nómina
                    </h1>
                    <EstadoPeriodo :estado="periodo.estado" />
                </div>
                <p class="text-base leading-[1.6] text-muted-foreground">
                    {{ formatearFecha(periodo.fecha_inicio) }} –
                    {{ formatearFecha(periodo.fecha_fin) }} ·
                    {{ periodo.sede?.nombre ?? 'Todas las sedes' }} ·
                    <span class="capitalize">{{
                        periodo.periodicidad_pago
                    }}</span
                    ><template v-if="periodo.liquidador">
                        · Liquidada por {{ periodo.liquidador.name }}</template
                    >
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <Form
                    v-if="periodo.estado === 'cerrado'"
                    v-bind="
                        PeriodoNominaController.enviarRecibos.form(periodo.id)
                    "
                    v-slot="{ processing }"
                >
                    <Button
                        variant="outline"
                        :disabled="processing"
                        :class="claseBotonSecundario"
                        >Enviar comprobantes por correo</Button
                    >
                </Form>
                <Form
                    v-else
                    v-bind="PeriodoNominaController.liquidar.form(periodo.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        variant="outline"
                        :disabled="processing"
                        :class="claseBotonSecundario"
                    >
                        {{
                            periodo.estado === 'borrador'
                                ? 'Liquidar periodo'
                                : 'Volver a liquidar'
                        }}
                    </Button>
                </Form>
                <Form
                    v-if="periodo.estado === 'liquidado'"
                    v-bind="PeriodoNominaController.cerrar.form(periodo.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        :disabled="processing"
                        class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
                        >Cerrar periodo</Button
                    >
                </Form>
            </div>
        </div>

        <InputError :message="page.props.errors.periodo" />

        <div
            class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4"
        >
            <div
                v-for="(dato, indice) in resumen"
                :key="dato.titulo"
                class="lift rv rounded-[18px] border bg-card p-[22px]"
                :style="{ '--i': indice + 2 }"
            >
                <p class="text-sm text-muted-foreground">{{ dato.titulo }}</p>
                <p
                    class="mt-1.5 font-heading text-[28px] leading-[normal] font-bold"
                >
                    {{ formatearPesos(dato.valor) }}
                </p>
            </div>
        </div>

        <PanelTabla titulo="Recibos del periodo" :indice="5">
            <table class="min-w-[900px]">
                <thead>
                    <tr>
                        <th scope="col">Empleado</th>
                        <th scope="col">Días</th>
                        <th scope="col">Devengado</th>
                        <th scope="col">Deducciones</th>
                        <th scope="col">Neto</th>
                        <th scope="col">Comprobante</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="recibo in recibos" :key="recibo.id">
                        <td class="font-semibold">
                            {{ recibo.empleado?.nombres }}
                            {{ recibo.empleado?.apellidos }}
                        </td>
                        <td>{{ recibo.dias_liquidados }}</td>
                        <td class="font-heading whitespace-nowrap">
                            {{ formatearPesos(recibo.valor_total_devengado) }}
                        </td>
                        <td class="font-heading whitespace-nowrap">
                            {{ formatearPesos(recibo.valor_total_deducciones) }}
                        </td>
                        <td class="font-heading font-bold whitespace-nowrap">
                            {{ formatearPesos(recibo.valor_neto) }}
                        </td>
                        <td>
                            <span
                                v-if="periodo.estado === 'cerrado'"
                                class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                :class="
                                    recibo.fecha_envio
                                        ? 'bg-ok-bg text-success'
                                        : 'bg-cream text-warning'
                                "
                                >{{
                                    recibo.fecha_envio
                                        ? `Enviado ${formatearFecha(recibo.fecha_envio.slice(0, 10))}`
                                        : 'Pendiente'
                                }}</span
                            >
                            <span v-else class="text-ink-3">—</span>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    as-child
                                    class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                                >
                                    <Link :href="nomina.recibos.show(recibo.id)"
                                        >Ver</Link
                                    >
                                </Button>
                                <Form
                                    v-if="
                                        periodo.estado === 'cerrado' &&
                                        recibo.fecha_envio
                                    "
                                    v-bind="
                                        ReciboNominaController.reenviar.form(
                                            recibo.id,
                                        )
                                    "
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        variant="outline"
                                        :disabled="processing"
                                        class="boton-plano min-h-9 rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                                        >Reenviar</Button
                                    >
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="recibos.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            El periodo aún no tiene comprobantes. Liquídelo para
                            generarlos.
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="recibos.length > 0" class="border-t bg-secondary">
                    <tr>
                        <td colspan="4" class="py-4 font-bold">
                            Total a pagar
                        </td>
                        <td class="py-4 font-heading">
                            {{ formatearPesos(totalNeto) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
