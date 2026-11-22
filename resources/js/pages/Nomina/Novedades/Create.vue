<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NovedadController from '@/actions/App/Http/Controllers/Nomina/NovedadController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioNovedad from '@/components/Nomina/FormularioNovedad.vue';
import nomina from '@/routes/nomina';
import type { ConceptoNomina, Empleado, OpcionTipoNovedad } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Novedades', href: nomina.novedades.index() },
            { title: 'Registrar novedad', href: '' },
        ],
    },
});

defineProps<{
    empleados: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>[];
    tipos: OpcionTipoNovedad[];
    conceptos: Pick<ConceptoNomina, 'id' | 'nombre'>[];
}>();
</script>

<template>
    <Head title="Registrar novedad" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Registrar novedad
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Incidencias y pagos eventuales de un empleado para un rango de
                fechas.
            </p>
        </header>

        <FormularioNovedad
            :accion="NovedadController.store.form()"
            :empleados="empleados"
            :tipos="tipos"
            :conceptos="conceptos"
            limpiar-al-guardar
        />
    </PaginaPos>
</template>
