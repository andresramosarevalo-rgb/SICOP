<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import EstadoPeriodo from '@/components/Nomina/EstadoPeriodo.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { PeriodoNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Periodos', href: nomina.periodos.index() },
        ],
    },
});

defineProps<{
    periodos: PeriodoNomina[];
}>();
</script>

<template>
    <Head title="Periodos de nómina" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.inicio.index()" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Periodos de nómina"
                description="Cree un periodo y liquídelo para generar los recibos de pago."
            />
            <Button as-child>
                <Link :href="nomina.periodos.create()">Nuevo periodo</Link>
            </Button>
        </div>

        <div v-if="periodos.length" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Periodo</th>
                        <th class="px-4 py-2 font-medium">Periodicidad</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                        <th class="px-4 py-2 text-right font-medium">
                            Recibos
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            Total neto
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="periodo in periodos" :key="periodo.id">
                        <td class="px-4 py-2">
                            <Link
                                :href="nomina.periodos.show(periodo.id)"
                                class="font-medium underline-offset-4 hover:underline"
                            >
                                {{ periodo.fecha_inicio }} —
                                {{ periodo.fecha_fin }}
                            </Link>
                        </td>
                        <td class="px-4 py-2 capitalize">
                            {{ periodo.periodicidad_pago }}
                        </td>
                        <td class="px-4 py-2">
                            <EstadoPeriodo :estado="periodo.estado" />
                        </td>
                        <td class="px-4 py-2 text-right">
                            {{ periodo.recibos_count }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            {{
                                formatearPesos(
                                    periodo.recibos_sum_valor_neto ?? 0,
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-muted-foreground">
            Todavía no hay periodos de nómina.
        </p>
    </div>
</template>
