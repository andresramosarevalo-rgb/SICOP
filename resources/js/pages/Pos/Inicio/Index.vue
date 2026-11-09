<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Building2,
    GraduationCap,
    CarFront,
    HandCoins,
    Wallet,
    Landmark,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
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

const secciones = [
    {
        titulo: 'Sedes',
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
</script>

<template>
    <Head title="Punto de venta" />

    <PaginaPos>
        <Heading
            title="Punto de venta"
            description="Sedes y registro de cursos y SOAT vendidos."
        />

        <nav
            aria-label="Secciones de punto de venta"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="seccion in secciones"
                :key="seccion.titulo"
                :href="seccion.href"
                class="group flex flex-col rounded-xl border bg-card p-6 shadow-sm transition-colors hover:border-primary/40 hover:bg-accent/30 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
                <span
                    class="mb-5 flex size-11 items-center justify-center rounded-xl bg-secondary text-secondary-foreground"
                    ><component
                        :is="seccion.icono"
                        class="size-5"
                        aria-hidden="true"
                /></span>
                <h2 class="text-base font-semibold">{{ seccion.titulo }}</h2>
                <p
                    class="mt-2 flex-1 text-sm leading-relaxed text-muted-foreground"
                >
                    {{ seccion.detalle }}
                </p>
                <span
                    class="mt-6 flex items-center justify-between text-sm font-semibold text-primary"
                    >Abrir sección
                    <ArrowUpRight class="size-4" aria-hidden="true"
                /></span>
            </Link>
        </nav>
    </PaginaPos>
</template>
