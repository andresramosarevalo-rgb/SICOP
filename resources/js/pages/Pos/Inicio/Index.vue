<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Building2,
    GraduationCap,
    CarFront,
    HandCoins,
    Wallet,
    Landmark,
} from '@lucide/vue';
import { computed } from 'vue';
import pos from '@/routes/pos';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Punto de venta',
                href: pos.inicio.index(),
            },
        ],
    },
});

const page = usePage();

const todasLasSecciones = [
    {
        titulo: 'Sedes',
        soloSuperAdmin: true,
        icono: Building2,
        detalle: 'Sedes de COVIACOL con su ciudad y dirección.',
        href: pos.sedes.index(),
    },
    {
        titulo: 'Cursos de todas las sedes',
        icono: GraduationCap,
        detalle: 'Cursos registrados por todas las sedes en un día.',
        href: pos.cursos.index(),
    },
    {
        titulo: 'SOAT de todas las sedes',
        icono: CarFront,
        detalle: 'SOAT registrados por todas las sedes en un día.',
        href: pos.soats.index(),
    },
    {
        titulo: 'Tarifas del gestor',
        icono: HandCoins,
        detalle: 'Pago al gestor por tipo de comparendo, base y por sede.',
        href: pos.tarifas.index(),
    },
    {
        titulo: 'Cierres de caja',
        icono: Wallet,
        detalle: 'Estado del cierre y arqueo de todas las sedes en un día.',
        href: pos.cierres.index(),
    },
    {
        titulo: 'Consignaciones',
        icono: Landmark,
        detalle: 'Lo que cada sede debe consignar y lo que ya consignó.',
        href: pos.consignaciones.index(),
    },
];

const secciones = computed(() =>
    todasLasSecciones.filter(
        (seccion) =>
            !seccion.soloSuperAdmin || page.props.auth.permisos.gestionarSedes,
    ),
);
</script>

<template>
    <Head title="Punto de venta" />

    <div
        class="mx-auto flex w-full max-w-[1280px] flex-1 flex-col gap-7 px-4 pt-7 pb-12 md:px-10"
    >
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Punto de venta
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Sedes y registro de cursos y SOAT vendidos.
            </p>
        </header>

        <nav
            aria-label="Secciones de punto de venta"
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
