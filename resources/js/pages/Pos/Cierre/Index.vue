<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { FilaCierreCaja } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cierres de caja', href: '' },
        ],
    },
});

defineProps<{
    fecha: string;
    filas: FilaCierreCaja[];
    valorTotalAConsignar: number;
}>();

function filtrarPorDia(fecha: string | number): void {
    if (fecha) {
        router.get(pos.cierres.index(), { fecha }, { preserveScroll: true });
    }
}

function describir(valor: string): string {
    const numero = Number(valor);

    if (numero === 0) {
        return 'Cuadra';
    }

    return `${numero > 0 ? 'Sobrante' : 'Faltante'} ${formatearPesos(Math.abs(numero))}`;
}
</script>

<template>
    <Head title="Cierres de caja" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Cierres de caja"
                description="Estado del cierre y arqueo de cada sede en el día seleccionado."
            />
            <div class="grid gap-2">
                <Label for="fecha">Día</Label>
                <Input
                    id="fecha"
                    type="date"
                    :model-value="fecha"
                    @update:model-value="filtrarPorDia"
                />
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            Total a consignar de las sedes cerradas:
            <span class="font-semibold text-foreground">{{
                formatearPesos(valorTotalAConsignar)
            }}</span>
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Sede</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                        <th class="px-4 py-2 font-medium">Efectivo contado</th>
                        <th class="px-4 py-2 font-medium">Arqueo efectivo</th>
                        <th class="px-4 py-2 font-medium">Arqueo datáfono</th>
                        <th class="px-4 py-2 font-medium">Arqueo QR</th>
                        <th class="px-4 py-2 font-medium">A consignar</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="fila in filas" :key="fila.sede.id">
                        <td class="px-4 py-2">{{ fila.sede.nombre }}</td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="fila.cierre ? 'default' : 'outline'"
                                >{{
                                    fila.cierre ? 'Cerrado' : 'Sin cerrar'
                                }}</Badge
                            >
                        </td>
                        <template v-if="fila.cierre">
                            <td class="px-4 py-2">
                                {{
                                    formatearPesos(
                                        fila.cierre.valor_efectivo_contado,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-2">
                                {{
                                    describir(
                                        fila.cierre.valor_diferencia_efectivo,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-2">
                                {{
                                    describir(
                                        fila.cierre.valor_diferencia_datafono,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-2">
                                {{ describir(fila.cierre.valor_diferencia_qr) }}
                            </td>
                            <td class="px-4 py-2 font-semibold">
                                {{
                                    formatearPesos(
                                        fila.cierre.valor_a_consignar,
                                    )
                                }}
                            </td>
                        </template>
                        <td
                            v-else
                            colspan="5"
                            class="px-4 py-2 text-muted-foreground"
                        >
                            —
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end">
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="
                                            pos.sedes.cierre.show(
                                                fila.sede.id,
                                                {
                                                    query: { fecha },
                                                },
                                            )
                                        "
                                        >Ver</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filas.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            No hay sedes registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
