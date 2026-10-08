<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import nomina from '@/routes/nomina';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Nómina',
                href: nomina.inicio.index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Nómina" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <Heading
            title="Nómina"
            description="Expedientes de trabajadores, novedades y liquidación de la nómina."
        />

        <nav class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                :href="nomina.empleados.index()"
                class="rounded-xl border p-4 transition-colors hover:bg-accent"
            >
                <p class="font-medium">Empleados</p>
                <p class="text-sm text-muted-foreground">
                    Expedientes de los trabajadores.
                </p>
            </Link>
            <Link
                :href="nomina.areas.index()"
                class="rounded-xl border p-4 transition-colors hover:bg-accent"
            >
                <p class="font-medium">Áreas</p>
                <p class="text-sm text-muted-foreground">
                    Departamentos de los empleados.
                </p>
            </Link>
            <Link
                :href="nomina.parametros.index()"
                class="rounded-xl border p-4 transition-colors hover:bg-accent"
            >
                <p class="font-medium">Parámetros legales</p>
                <p class="text-sm text-muted-foreground">
                    SMMLV, UVT, aportes y recargos por año.
                </p>
            </Link>
            <Link
                :href="nomina.conceptos.index()"
                class="rounded-xl border p-4 transition-colors hover:bg-accent"
            >
                <p class="font-medium">Conceptos</p>
                <p class="text-sm text-muted-foreground">
                    Devengos y deducciones del recibo.
                </p>
            </Link>
        </nav>
    </div>
</template>
