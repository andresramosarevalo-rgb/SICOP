<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CursoController from '@/actions/App/Http/Controllers/Pos/CursoController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioCurso from '@/components/Pos/FormularioCurso.vue';
import pos from '@/routes/pos';
import type { Opcion, OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    codigosInfraccion: Opcion[];
    formasPago: Opcion[];
    referidos: Opcion[];

    porcentajesDescuento: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cursos', href: '' },
            { title: 'Registrar curso', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Registrar curso" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Registrar curso
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                {{ props.sede.nombre }} · {{ props.sede.ciudad }}
            </p>
        </header>

        <FormularioCurso
            :accion="CursoController.store.form(props.sede.id)"
            :sede="props.sede"
            :tipos-documento="props.tiposDocumento"
            :codigos-infraccion="props.codigosInfraccion"
            :formas-pago="props.formasPago"
            :referidos="props.referidos"
            :porcentajes-descuento="props.porcentajesDescuento"
            etiqueta-guardar="Registrar curso"
        />
    </PaginaPos>
</template>
