<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PeriodoNominaController from '@/actions/App/Http/Controllers/Nomina/PeriodoNominaController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import EstadoPeriodo from '@/components/Nomina/EstadoPeriodo.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
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

const totalNeto = computed(() =>
    props.recibos.reduce(
        (total, recibo) => total + Number(recibo.valor_neto),
        0,
    ),
);
</script>

<template>
    <Head title="Periodo de nómina" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="`Periodo ${periodo.fecha_inicio} — ${periodo.fecha_fin}`"
                :description="`Nómina ${periodo.periodicidad_pago}${periodo.liquidador ? ` · liquidada por ${periodo.liquidador.name}` : ''}`"
            />
            <EstadoPeriodo :estado="periodo.estado" />
        </div>

        <div v-if="periodo.estado !== 'cerrado'" class="flex flex-col gap-2">
            <div class="flex flex-wrap gap-2">
                <Form
                    v-bind="PeriodoNominaController.liquidar.form(periodo.id)"
                    v-slot="{ processing }"
                >
                    <Button :disabled="processing">
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
                    <Button variant="outline" :disabled="processing"
                        >Cerrar periodo</Button
                    >
                </Form>
            </div>
            <InputError :message="page.props.errors.periodo" />
        </div>

        <div v-if="recibos.length" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Empleado</th>
                        <th class="px-4 py-2 text-right font-medium">Días</th>
                        <th class="px-4 py-2 text-right font-medium">
                            Devengado
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            Deducciones
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            Neto a pagar
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="recibo in recibos" :key="recibo.id">
                        <td class="px-4 py-2">
                            <Link
                                :href="nomina.recibos.show(recibo.id)"
                                class="font-medium underline-offset-4 hover:underline"
                            >
                                {{ recibo.empleado?.apellidos }},
                                {{ recibo.empleado?.nombres }}
                            </Link>
                        </td>
                        <td class="px-4 py-2 text-right">
                            {{ recibo.dias_liquidados }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            {{ formatearPesos(recibo.valor_total_devengado) }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            {{ formatearPesos(recibo.valor_total_deducciones) }}
                        </td>
                        <td class="px-4 py-2 text-right font-medium">
                            {{ formatearPesos(recibo.valor_neto) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot class="border-t font-medium">
                    <tr>
                        <td class="px-4 py-2" colspan="4">Total a pagar</td>
                        <td class="px-4 py-2 text-right">
                            {{ formatearPesos(totalNeto) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p v-else class="text-sm text-muted-foreground">
            El periodo aún no tiene recibos. Liquídelo para generarlos.
        </p>
    </div>
</template>
