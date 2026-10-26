<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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
        detalle: 'Sedes de COVIACOL con su ciudad y dirección.',
        href: pos.sedes.index(),
    },
    {
        titulo: 'Cursos de todas las sedes',
        detalle: 'Cursos registrados por todas las sedes en un día.',
        href: pos.cursos.index(),
    },
    {
        titulo: 'SOAT de todas las sedes',
        detalle: 'SOAT registrados por todas las sedes en un día.',
        href: pos.soats.index(),
    },
    {
        titulo: 'Tarifas del gestor',
        detalle: 'Pago al gestor por tipo de comparendo, base y por sede.',
        href: pos.tarifas.index(),
    },
    {
        titulo: 'Cierres de caja',
        detalle: 'Estado del cierre y arqueo de todas las sedes en un día.',
        href: pos.cierres.index(),
    },
    {
        titulo: 'Consignaciones',
        detalle: 'Lo que cada sede debe consignar y lo que ya consignó.',
        href: pos.consignaciones.index(),
    },
];
</script>

<template>
    <Head title="Punto de venta" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Punto de venta"
            description="Sedes y registro de cursos y SOAT vendidos."
        />

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
