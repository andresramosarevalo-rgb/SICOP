<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import EstadoPeriodo from '@/components/Nomina/EstadoPeriodo.vue';
import { Button } from '@/components/ui/button';
import { formatearFecha, formatearPesos } from '@/lib/formato';
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

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Periodos de nómina
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Cree un periodo y liquídelo para generar los recibos de
                    pago.
                </p>
            </div>
            <Button
                as-child
                class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
            >
                <Link :href="nomina.periodos.create()">Nuevo periodo</Link>
            </Button>
        </header>

        <PanelTabla titulo="Periodos registrados" :indice="2" relleno="16px">
            <table class="min-w-[900px]">
                <thead>
                    <tr>
                        <th scope="col">Periodo</th>
                        <th scope="col">Sede</th>
                        <th scope="col">Periodicidad</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Empleados</th>
                        <th scope="col">Neto a pagar</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="periodo in periodos" :key="periodo.id">
                        <td class="font-semibold whitespace-nowrap">
                            {{ formatearFecha(periodo.fecha_inicio) }} –
                            {{ formatearFecha(periodo.fecha_fin) }}
                        </td>
                        <td>
                            {{ periodo.sede?.nombre ?? 'Todas las sedes' }}
                        </td>
                        <td class="capitalize">
                            {{ periodo.periodicidad_pago }}
                        </td>
                        <td><EstadoPeriodo :estado="periodo.estado" /></td>
                        <td class="font-heading">
                            {{ periodo.recibos_count }}
                        </td>
                        <td class="font-heading whitespace-nowrap">
                            {{
                                formatearPesos(
                                    periodo.recibos_sum_valor_neto ?? 0,
                                )
                            }}
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link :href="nomina.periodos.show(periodo.id)"
                                    >Ver periodo</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="periodos.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Todavía no hay periodos de nómina.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
