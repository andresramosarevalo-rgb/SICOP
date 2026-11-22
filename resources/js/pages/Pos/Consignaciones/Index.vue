<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import { Button } from '@/components/ui/button';
import { formatearFecha, formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { DiaPorConsignar, FilaSaldoConsignacion } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Consignaciones', href: '' },
        ],
    },
});

const props = defineProps<{
    filas: FilaSaldoConsignacion[];
    sedeId: number | null;
    dias: DiaPorConsignar[];
}>();

const sedeSeleccionada = computed(
    () => props.filas.find((fila) => fila.sede.id === props.sedeId)?.sede,
);

function pendiente(fila: FilaSaldoConsignacion): number {
    return Number(fila.valor_a_consignar) - Number(fila.valor_consignado);
}
</script>

<template>
    <Head title="Consignaciones" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Consignaciones
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Lo que cada sede debe consignar según sus cierres de caja y lo
                que ya consignó.
            </p>
        </header>

        <PanelTabla titulo="Saldos por sede" compacta relleno="16px">
            <table class="min-w-[900px]">
                <thead>
                    <tr>
                        <th scope="col">Sede</th>
                        <th scope="col" class="text-right">Por consignar</th>
                        <th scope="col" class="text-right">Consignado</th>
                        <th scope="col" class="text-right">Saldo pendiente</th>
                        <th scope="col">Días sin consignar</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="fila in filas"
                        :key="fila.sede.id"
                        :class="{ 'bg-hover-row': fila.sede.id === sedeId }"
                    >
                        <td class="font-semibold">{{ fila.sede.nombre }}</td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(fila.valor_a_consignar) }}
                        </td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(fila.valor_consignado) }}
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <span
                                class="font-heading font-bold"
                                :class="
                                    pendiente(fila) > 0
                                        ? 'text-warning'
                                        : 'text-success'
                                "
                                >{{ formatearPesos(pendiente(fila)) }}</span
                            >
                        </td>
                        <td>
                            <span
                                v-if="fila.dias_sin_consignar > 0"
                                class="inline-flex rounded-full bg-cream px-3 py-[5px] text-[13px] leading-[normal] font-semibold text-warning"
                                >{{ fila.dias_sin_consignar }}
                                {{
                                    fila.dias_sin_consignar === 1
                                        ? 'día'
                                        : 'días'
                                }}</span
                            >
                            <span v-else class="font-semibold text-success"
                                >Ninguno</span
                            >
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link
                                    :href="
                                        pos.consignaciones.index({
                                            query: { sede_id: fila.sede.id },
                                        })
                                    "
                                    >Ver días</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="filas.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            No hay sedes registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>

        <PanelTabla
            v-if="sedeId"
            :titulo="`Días cerrados de la sede · ${sedeSeleccionada?.nombre ?? ''}`"
            :indice="3"
            compacta
        >
            <table class="min-w-[800px]">
                <thead>
                    <tr>
                        <th scope="col">Día</th>
                        <th scope="col" class="text-right">Valor</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Referencia</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="dia in dias" :key="dia.id">
                        <td class="whitespace-nowrap">
                            {{ formatearFecha(dia.fecha) }}
                        </td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(dia.valor_a_consignar) }}
                        </td>
                        <td>
                            <span
                                class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                :class="
                                    dia.consignacion ||
                                    Number(dia.valor_a_consignar) <= 0
                                        ? 'bg-ok-bg text-success'
                                        : 'bg-cream text-warning'
                                "
                                >{{
                                    dia.consignacion
                                        ? 'Consignado'
                                        : Number(dia.valor_a_consignar) <= 0
                                          ? 'Nada por consignar'
                                          : 'Pendiente'
                                }}</span
                            >
                        </td>
                        <td :class="dia.consignacion ? '' : 'text-ink-3'">
                            {{ dia.consignacion?.referencia ?? '—' }}
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link
                                    :href="
                                        pos.sedes.cierre.show(sedeId, {
                                            query: { fecha: dia.fecha },
                                        })
                                    "
                                    >{{
                                        dia.consignacion ? 'Ver' : 'Consignar'
                                    }}</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="dias.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Esta sede aún no tiene días cerrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
