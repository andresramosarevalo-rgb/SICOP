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

const pasos = [
    {
        titulo: 'Registrar empleados',
        detalle: 'Con sus datos y su contrato.',
        href: nomina.empleados.create(),
    },
    {
        titulo: 'Registrar novedades',
        detalle: 'Horas extra, faltas, incapacidades o vacaciones.',
        href: nomina.novedades.index(),
    },
    {
        titulo: 'Liquidar el periodo',
        detalle: 'Cree el periodo y presione Liquidar.',
        href: nomina.periodos.index(),
    },
    {
        titulo: 'Revisar y enviar comprobantes',
        detalle: 'Ábralos, cierre el periodo y envíelos por correo.',
        href: nomina.periodos.index(),
    },
];

const secciones = [
    {
        titulo: 'Empleados',
        detalle: 'Expedientes y contratos de los trabajadores.',
        href: nomina.empleados.index(),
    },
    {
        titulo: 'Novedades',
        detalle: 'Faltas, horas extra, incapacidades y vacaciones.',
        href: nomina.novedades.index(),
    },
    {
        titulo: 'Periodos y liquidación',
        detalle: 'Liquidar la nómina y ver los comprobantes de pago.',
        href: nomina.periodos.index(),
    },
    {
        titulo: 'Áreas',
        detalle: 'Departamentos de los empleados.',
        href: nomina.areas.index(),
    },
    {
        titulo: 'Conceptos',
        detalle: 'Devengos y deducciones del comprobante.',
        href: nomina.conceptos.index(),
    },
    {
        titulo: 'Parámetros legales',
        detalle: 'SMMLV, UVT, aportes y recargos por año.',
        href: nomina.parametros.index(),
    },
];
</script>

<template>
    <Head title="Nómina" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Nómina"
            description="Expedientes de trabajadores, novedades y liquidación de la nómina."
        />

        <section class="rounded-xl border bg-muted/50 p-4">
            <h2 class="mb-3 font-medium">Cómo liquidar la nómina</h2>
            <ol class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <li
                    v-for="(paso, indice) in pasos"
                    :key="paso.titulo"
                    class="flex gap-3"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground"
                    >
                        {{ indice + 1 }}
                    </span>
                    <span>
                        <Link
                            :href="paso.href"
                            class="font-medium underline-offset-4 hover:underline"
                        >
                            {{ paso.titulo }}
                        </Link>
                        <span class="block text-muted-foreground">{{
                            paso.detalle
                        }}</span>
                    </span>
                </li>
            </ol>
        </section>

        <nav class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="seccion in secciones"
                :key="seccion.titulo"
                :href="seccion.href"
                class="rounded-xl border p-4 transition-colors hover:bg-accent"
            >
                <p class="font-medium">{{ seccion.titulo }}</p>
                <p class="text-sm text-muted-foreground">
                    {{ seccion.detalle }}
                </p>
            </Link>
        </nav>
    </div>
</template>
