<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
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

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.inicio.index()" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Parámetros legales"
                description="Valores de ley por año que usa el cálculo de la nómina. Confírmelos con el contador."
            />
            <Button as-child>
                <Link :href="nomina.parametros.create()">Nuevo año</Link>
            </Button>
        </div>

        <ul v-if="parametros.length" class="divide-y rounded-xl border px-4">
            <li
                v-for="parametro in parametros"
                :key="parametro.id"
                class="flex flex-wrap items-center justify-between gap-3 py-3"
            >
                <div>
                    <p class="font-medium">{{ parametro.anio }}</p>
                    <p class="text-sm text-muted-foreground">
                        SMMLV
                        {{ formatearPesos(parametro.valor_salario_minimo) }} ·
                        UVT
                        {{ formatearPesos(parametro.valor_uvt) }}
                    </p>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="nomina.parametros.edit(parametro.id)"
                        >Editar</Link
                    >
                </Button>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">
            Todavía no hay parámetros registrados.
        </p>
    </div>
</template>
