<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Building2,
    Calendar,
    Scale,
    Tag,
    Users,
    Wallet,
} from '@lucide/vue';
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

const secciones = [
    {
        titulo: 'Empleados',
        icono: Users,
        detalle: 'Expedientes y contratos de los trabajadores.',
        href: nomina.empleados.index(),
    },
    {
        titulo: 'Novedades',
        icono: Calendar,
        detalle: 'Ausencias, horas extra, incapacidades y vacaciones.',
        href: nomina.novedades.index(),
    },
    {
        titulo: 'Periodos y liquidación',
        icono: Wallet,
        detalle: 'Liquidar la nómina y ver los comprobantes de pago.',
        href: nomina.periodos.index(),
    },
    {
        titulo: 'Áreas',
        icono: Building2,
        detalle: 'Departamentos de los empleados.',
        href: nomina.areas.index(),
    },
    {
        titulo: 'Conceptos',
        icono: Tag,
        detalle: 'Devengos y deducciones del comprobante.',
        href: nomina.conceptos.index(),
    },
    {
        titulo: 'Parámetros legales',
        icono: Scale,
        detalle: 'SMMLV, UVT, aportes y recargos por año.',
        href: nomina.parametros.index(),
    },
];
</script>

<template>
    <Head title="Nómina" />

    <div
        class="mx-auto flex w-full max-w-[1280px] flex-1 flex-col gap-7 px-4 pt-7 pb-12 md:px-10"
    >
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Nómina
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Expedientes de trabajadores, novedades y liquidación de la
                nómina.
            </p>
        </header>

        <nav
            aria-label="Secciones de nómina"
            class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="(seccion, indice) in secciones"
                :key="seccion.titulo"
                :href="seccion.href"
                class="lift-modulo rv flex flex-col gap-5 rounded-[20px] border bg-card p-[26px] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                :style="{ '--i': indice + 2 }"
            >
                <div class="flex items-start justify-between">
                    <span
                        class="bub flex size-[52px] items-center justify-center rounded-[14px] bg-secondary text-foreground"
                        ><component
                            :is="seccion.icono"
                            class="size-6"
                            aria-hidden="true"
                    /></span>
                    <ArrowUpRight
                        class="arr size-[22px] text-ink-3"
                        aria-hidden="true"
                    />
                </div>
                <div>
                    <h2
                        class="font-heading text-[19px] leading-[normal] font-bold"
                    >
                        {{ seccion.titulo }}
                    </h2>
                    <p
                        class="mt-2 text-[15px] leading-[1.6] text-muted-foreground"
                    >
                        {{ seccion.detalle }}
                    </p>
                </div>
            </Link>
        </nav>
    </div>
</template>
