<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SoatController from '@/actions/App/Http/Controllers/Pos/SoatController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioSoat from '@/components/Pos/FormularioSoat.vue';
import pos from '@/routes/pos';
import type { Opcion, OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    formasPago: Opcion[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'SOAT', href: '' },
            { title: 'Registrar SOAT', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Registrar SOAT" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Registrar SOAT
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                {{ props.sede.nombre }} · {{ props.sede.ciudad }}
            </p>
        </header>

        <FormularioSoat
            :accion="SoatController.store.form(props.sede.id)"
            :sede="props.sede"
            :tipos-documento="props.tiposDocumento"
            :formas-pago="props.formasPago"
            etiqueta-guardar="Guardar"
        />
    </PaginaPos>
</template>
