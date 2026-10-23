<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { etiquetaFormaPago, formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { Soat } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'SOAT de todas las sedes', href: '' },
        ],
    },
});

defineProps<{
    fecha: string;
    soats: Soat[];
    valorTotal: number;
}>();

function filtrarPorDia(fecha: string | number): void {
    if (fecha) {
        router.get(pos.soats.index(), { fecha }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="SOAT de todas las sedes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="SOAT de todas las sedes"
                description="SOAT registrados por las sedes en el día seleccionado."
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
            {{ soats.length }} SOAT ·
            {{ formatearPesos(valorTotal) }}
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Sede</th>
                        <th class="px-4 py-2 font-medium">Documento</th>
                        <th class="px-4 py-2 font-medium">Cliente</th>
                        <th class="px-4 py-2 font-medium">Placa</th>
                        <th class="px-4 py-2 font-medium">Pago</th>
                        <th class="px-4 py-2 font-medium">Valor</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="soat in soats" :key="soat.id">
                        <td class="px-4 py-2">
                            {{ soat.sede?.nombre }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ soat.tipo_documento }}
                            {{ soat.numero_documento }}
                        </td>
                        <td class="px-4 py-2">{{ soat.nombre_cliente }}</td>
                        <td class="px-4 py-2">{{ soat.placa }}</td>
                        <td class="px-4 py-2">
                            {{ etiquetaFormaPago(soat.forma_pago) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ formatearPesos(soat.valor_soat) }}
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="pos.soats.edit(soat.id)"
                                        >Editar</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="soats.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Ninguna sede registró SOAT este día.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
