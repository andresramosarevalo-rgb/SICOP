<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
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

defineProps<{
    filas: FilaSaldoConsignacion[];
    sedeId: number | null;
    dias: DiaPorConsignar[];
}>();

function pendiente(fila: FilaSaldoConsignacion): number {
    return Number(fila.valor_a_consignar) - Number(fila.valor_consignado);
}
</script>

<template>
    <Head title="Consignaciones" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <Heading
            title="Consignaciones"
            description="Lo que cada sede debe consignar según sus cierres de caja y lo que ya consignó."
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Sede</th>
                        <th class="px-4 py-2 font-medium">Por consignar</th>
                        <th class="px-4 py-2 font-medium">Consignado</th>
                        <th class="px-4 py-2 font-medium">Saldo pendiente</th>
                        <th class="px-4 py-2 font-medium">
                            Días sin consignar
                        </th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="fila in filas"
                        :key="fila.sede.id"
                        :class="{ 'bg-muted/50': fila.sede.id === sedeId }"
                    >
                        <td class="px-4 py-2">{{ fila.sede.nombre }}</td>
                        <td class="px-4 py-2">
                            {{ formatearPesos(fila.valor_a_consignar) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ formatearPesos(fila.valor_consignado) }}
                        </td>
                        <td class="px-4 py-2 font-semibold">
                            {{ formatearPesos(pendiente(fila)) }}
                        </td>
                        <td class="px-4 py-2">{{ fila.dias_sin_consignar }}</td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end">
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="
                                            pos.consignaciones.index({
                                                query: {
                                                    sede_id: fila.sede.id,
                                                },
                                            })
                                        "
                                        >Ver días</Link
                                    >
                                </Button>
                            </div>
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
        </div>

        <section v-if="sedeId" class="flex flex-col gap-3">
            <h2 class="font-medium">Días cerrados de la sede</h2>
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Día</th>
                            <th class="px-4 py-2 font-medium">Por consignar</th>
                            <th class="px-4 py-2 font-medium">Estado</th>
                            <th class="px-4 py-2 font-medium">Referencia</th>
                            <th class="px-4 py-2">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="dia in dias" :key="dia.id">
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ dia.fecha }}
                            </td>
                            <td class="px-4 py-2">
                                {{ formatearPesos(dia.valor_a_consignar) }}
                            </td>
                            <td class="px-4 py-2">
                                <Badge
                                    :variant="
                                        dia.consignacion ||
                                        Number(dia.valor_a_consignar) <= 0
                                            ? 'default'
                                            : 'outline'
                                    "
                                    >{{
                                        dia.consignacion
                                            ? 'Consignado'
                                            : Number(dia.valor_a_consignar) <= 0
                                              ? 'Nada por consignar'
                                              : 'Pendiente'
                                    }}</Badge
                                >
                            </td>
                            <td class="px-4 py-2">
                                {{ dia.consignacion?.referencia ?? '—' }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex justify-end">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                pos.sedes.cierre.show(sedeId, {
                                                    query: {
                                                        fecha: dia.fecha,
                                                    },
                                                })
                                            "
                                            >{{
                                                dia.consignacion
                                                    ? 'Ver'
                                                    : 'Registrar'
                                            }}</Link
                                        >
                                    </Button>
                                </div>
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
            </div>
        </section>
    </div>
</template>
