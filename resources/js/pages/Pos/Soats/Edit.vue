<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SoatController from '@/actions/App/Http/Controllers/Pos/SoatController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioSoat from '@/components/Pos/FormularioSoat.vue';
import pos from '@/routes/pos';
import type { Opcion, OpcionTipoDocumento, Soat, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    formasPago: Opcion[];
    soat: Soat;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'SOAT', href: '' },
            { title: 'Editar SOAT', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Editar SOAT" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Editar SOAT
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                {{ props.sede.nombre }} · {{ props.sede.ciudad }}
            </p>
        </header>

        <FormularioSoat
            :accion="SoatController.update.form(props.soat.id)"
            :sede="props.sede"
            :tipos-documento="props.tiposDocumento"
            :formas-pago="props.formasPago"
            :soat="props.soat"
            etiqueta-guardar="Guardar"
        />
    </PaginaPos>
</template>
