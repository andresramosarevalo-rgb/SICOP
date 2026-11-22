<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SedeController from '@/actions/App/Http/Controllers/Pos/SedeController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioSede from '@/components/Pos/FormularioSede.vue';
import pos from '@/routes/pos';
import type { Sede } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
            { title: 'Editar sede', href: '' },
        ],
    },
});

defineProps<{
    sede: Sede;
}>();
</script>

<template>
    <Head title="Editar sede" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Editar sede
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Desactive la sede si ya no opera: sus registros se conservan y
                sus cajeros no podrán iniciar sesión.
            </p>
        </header>

        <FormularioSede
            :accion="SedeController.update.form(sede.id)"
            :sede="sede"
            etiqueta-guardar="Guardar sede"
        />

        <p class="text-sm text-muted-foreground">
            Los cajeros y sus credenciales se administran desde Usuarios.
        </p>
    </PaginaPos>
</template>
