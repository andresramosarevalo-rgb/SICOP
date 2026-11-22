<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    etiquetaFormaPago,
    formatearFecha,
    formatearPesos,
} from '@/lib/formato';
import pos from '@/routes/pos';
import type { Curso } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cursos de todas las sedes', href: '' },
        ],
    },
});

defineProps<{
    fecha: string;
    cursos: Curso[];
    valorTotal: number;
}>();

function filtrarPorDia(fecha: string | number): void {
    if (fecha) {
        router.get(pos.cursos.index(), { fecha }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Cursos de todas las sedes" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Cursos de todas las sedes
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Cursos registrados por todas las sedes en un día.
                </p>
            </div>
            <Button
                variant="outline"
                as-child
                class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
            >
                <a
                    :href="pos.cursos.exportar.url({ query: { fecha } })"
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
                        <th scope="col">Fecha</th>
                        <th scope="col">Documento</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">N.º comparendo</th>
                        <th scope="col">Pago</th>
                        <th scope="col" class="text-right">Pago gestor</th>
                        <th scope="col" class="text-right">Valor</th>
                        <th scope="col">Descuento</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="curso in cursos" :key="curso.id">
                        <td class="font-semibold">{{ curso.sede?.nombre }}</td>
                        <td class="whitespace-nowrap">
                            {{ formatearFecha(curso.fecha) }}
                        </td>
                        <td class="whitespace-nowrap">
                            {{ curso.tipo_documento }}
                            {{ curso.numero_documento }}
                        </td>
                        <td>{{ curso.nombre_cliente }}</td>
                        <td>{{ curso.codigo_infraccion }}</td>
                        <td>{{ curso.numero_comparendo }}</td>
                        <td>{{ etiquetaFormaPago(curso.forma_pago) }}</td>
                        <td class="text-right whitespace-nowrap">
                            {{
                                Number(curso.valor_pago_gestor) > 0
                                    ? formatearPesos(
                                          curso.valor_pago_gestor ?? 0,
                                      )
                                    : '—'
                            }}
                        </td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(curso.valor_curso) }}
                        </td>
                        <td>
                            <span
                                v-if="Number(curso.porcentaje_descuento) > 0"
                                class="inline-flex rounded-full bg-cream px-2.5 py-1 text-xs font-semibold text-warning"
                                >{{
                                    Number(curso.porcentaje_descuento)
                                }}
                                %</span
                            >
                            <template v-else>—</template>
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-10 rounded-[10px] border-line-strong bg-transparent px-4 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link :href="pos.cursos.edit(curso.id)"
                                    >Editar</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="cursos.length === 0">
                        <td
                            colspan="11"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Ninguna sede registró cursos este día.
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="cursos.length > 0" class="border-t bg-secondary">
                    <tr>
                        <td colspan="8" class="py-4 font-bold">
                            Total del día · {{ cursos.length }}
                            {{ cursos.length === 1 ? 'curso' : 'cursos' }}
                        </td>
                        <td class="py-4 text-right font-heading">
                            {{ formatearPesos(valorTotal) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
