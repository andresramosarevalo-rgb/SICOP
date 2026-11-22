<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SedeController from '@/actions/App/Http/Controllers/Pos/SedeController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioSede from '@/components/Pos/FormularioSede.vue';
import pos from '@/routes/pos';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
            { title: 'Nueva sede', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva sede" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Nueva sede
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Registre la ubicación donde se venderán cursos y SOAT.
            </p>
        </header>

        <FormularioSede
            :accion="SedeController.store.form()"
            etiqueta-guardar="Guardar sede"
        />
    </PaginaPos>
</template>
