<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
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

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    SOAT de todas las sedes
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    SOAT registrados por todas las sedes en un día.
                </p>
            </div>
            <Button
                variant="outline"
                as-child
                class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
            >
                <a
                    :href="pos.soats.exportar.url({ query: { fecha } })"
                    title="Descargar CSV"
                    >Exportar</a
                >
            </Button>
        </header>

        <div
            class="rv flex flex-wrap items-end gap-4 rounded-[20px] border bg-card px-6 py-5"
            style="--i: 2"
        >
            <div class="flex min-w-[200px] flex-col gap-2">
                <Label
                    for="fecha"
                    class="text-sm leading-[normal] font-semibold"
                    >Día</Label
                >
                <Input
                    id="fecha"
                    type="date"
                    class="campo-sicop"
                    :model-value="fecha"
                    @update:model-value="filtrarPorDia"
                />
            </div>
        </div>

        <PanelTabla titulo="Registros del día" :indice="3" ocultar-titulo>
            <table class="min-w-[900px]">
                <thead>
                    <tr>
                        <th scope="col">Sede</th>
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
                    <tr v-for="soat in soats" :key="soat.id">
                        <td class="font-semibold">{{ soat.sede?.nombre }}</td>
                        <td class="whitespace-nowrap">
                            {{ soat.tipo_documento }}
                            {{ soat.numero_documento }}
                        </td>
                        <td>{{ soat.nombre_cliente }}</td>
                        <td class="tracking-[0.04em]">{{ soat.placa }}</td>
                        <td>{{ etiquetaFormaPago(soat.forma_pago) }}</td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(soat.valor_soat) }}
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link :href="pos.soats.edit(soat.id)"
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
                            Ninguna sede registró SOAT este día.
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="soats.length > 0" class="border-t bg-secondary">
                    <tr>
                        <td colspan="5" class="py-4 font-bold">
                            Total del día · {{ soats.length }} SOAT
                        </td>
                        <td class="py-4 text-right font-heading">
                            {{ formatearPesos(valorTotal) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
