<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ImportarExcel from '@/components/Pos/ImportarExcel.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import { Button } from '@/components/ui/button';
import {
    etiquetaFormaPago,
    formatearFecha,
    formatearPesos,
} from '@/lib/formato';
import pos from '@/routes/pos';
import type { Soat, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    soats: Soat[];
}>();

const hoy = new Date().toLocaleDateString('en-CA');

const soatsDeHoy = computed(() =>
    props.soats.filter((soat) => soat.fecha === hoy),
);

const resumen = computed(() => [
    { titulo: 'SOAT registrados hoy', valor: String(soatsDeHoy.value.length) },
    {
        titulo: 'Valor del día',
        valor: formatearPesos(
            soatsDeHoy.value.reduce(
                (total, soat) => total + Number(soat.valor_soat),
                0,
            ),
        ),
    },
    {
        titulo: 'Pagados con datáfono',
        valor: String(
            soatsDeHoy.value.filter((soat) => soat.forma_pago === 'datafono')
                .length,
        ),
    },
]);

const claseBotonSecundario =
    'boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'SOAT', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`SOAT · ${props.sede.nombre}`" />

    <PaginaPos>
        <div
            class="rv flex flex-wrap items-start justify-between gap-5 rounded-[22px] border border-brand-gold/55 bg-cream px-7 py-6"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    SOAT
                </h1>
                <p class="text-base leading-[1.6] text-muted-foreground">
                    {{ props.sede.nombre }} · {{ props.sede.ciudad }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <Button
                    variant="outline"
                    as-child
                    :class="claseBotonSecundario"
                >
                    <Link :href="pos.sedes.cierre.show(props.sede.id)"
                        >Cierre de caja</Link
                    >
                </Button>
                <Button
                    variant="outline"
                    as-child
                    :class="claseBotonSecundario"
                >
                    <Link :href="pos.sedes.cursos.index(props.sede.id)"
                        >Cursos</Link
                    >
                </Button>
                <Button
                    v-if="props.sede.es_activa"
                    as-child
                    class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
                >
                    <Link :href="pos.sedes.soats.create(props.sede.id)"
                        >Registrar SOAT</Link
                    >
                </Button>
            </div>
        </div>

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
                    class="mt-1.5 font-heading text-[32px] leading-[normal] font-bold"
                >
                    {{ dato.valor }}
                </p>
            </div>
        </div>

        <ImportarExcel
            v-if="props.sede.es_activa"
            :sede-id="props.sede.id"
            tipo="soats"
            etiqueta="SOAT"
        />

        <PanelTabla titulo="SOAT registrados" :indice="5">
            <table class="min-w-[900px]">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Documento</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Placa</th>
                        <th scope="col">Pago</th>
                        <th scope="col" class="text-right">Valor</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="registro in soats" :key="registro.id">
                        <td class="whitespace-nowrap">
                            {{ formatearFecha(registro.fecha) }}
                        </td>
                        <td class="whitespace-nowrap">
                            {{ registro.tipo_documento }}
                            {{ registro.numero_documento }}
                        </td>
                        <td>{{ registro.nombre_cliente }}</td>
                        <td class="font-semibold tracking-[0.04em]">
                            {{ registro.placa }}
                        </td>
                        <td>{{ etiquetaFormaPago(registro.forma_pago) }}</td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(registro.valor_soat) }}
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link :href="pos.soats.edit(registro.id)"
                                    >Editar</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="soats.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Esta sede aún no ha registrado SOAT.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
