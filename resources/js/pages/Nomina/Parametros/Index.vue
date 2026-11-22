<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { ParametroNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Parámetros legales', href: nomina.parametros.index() },
        ],
    },
});

defineProps<{
    parametros: ParametroNomina[];
}>();
</script>

<template>
    <Head title="Parámetros legales" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Parámetros legales
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Valores de ley por año que usa el cálculo de la nómina.
                    Confírmelos con el contador.
                </p>
            </div>
            <Button
                as-child
                class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
            >
                <Link :href="nomina.parametros.create()">Nuevo año</Link>
            </Button>
        </header>

        <PanelTabla
            titulo="Parámetros por año"
            :indice="2"
            relleno="16px"
            ocultar-titulo
        >
            <table class="min-w-[800px]">
                <thead>
                    <tr>
                        <th scope="col">Año</th>
                        <th scope="col" class="text-right">
                            Salario mínimo (SMMLV)
                        </th>
                        <th scope="col" class="text-right">
                            Auxilio de transporte
                        </th>
                        <th scope="col" class="text-right">UVT</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="parametro in parametros" :key="parametro.id">
                        <td class="font-semibold">{{ parametro.anio }}</td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(parametro.valor_salario_minimo) }}
                        </td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{
                                formatearPesos(
                                    parametro.valor_auxilio_transporte,
                                )
                            }}
                        </td>
                        <td class="text-right font-heading whitespace-nowrap">
                            {{ formatearPesos(parametro.valor_uvt) }}
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link
                                    :href="nomina.parametros.edit(parametro.id)"
                                    >Editar</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="parametros.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Todavía no hay parámetros registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
