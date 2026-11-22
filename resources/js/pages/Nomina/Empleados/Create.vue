<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import FormularioEmpleado from '@/components/Nomina/FormularioEmpleado.vue';
import nomina from '@/routes/nomina';
import type { Area, Opcion, OpcionTipoDocumento } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
            { title: 'Registrar', href: nomina.empleados.create() },
        ],
    },
});

defineProps<{
    areas: Pick<Area, 'id' | 'nombre'>[];
    tiposDocumento: OpcionTipoDocumento[];
    tiposCuentaBancaria: Opcion[];
    sedes: { id: number; nombre: string }[];
    puedeElegirSede: boolean;
    tiposContrato: Opcion[];
    periodicidades: Opcion[];
}>();
</script>

<template>
    <Head title="Registrar empleado" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Registrar empleado
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Primero los datos de la persona y después su primer contrato.
                Sin contrato no se puede liquidar su nómina.
            </p>
        </header>

        <FormularioEmpleado
            :areas="areas"
            :tipos-documento="tiposDocumento"
            :tipos-cuenta-bancaria="tiposCuentaBancaria"
            :sedes="sedes"
            :puede-elegir-sede="puedeElegirSede"
            :tipos-contrato="tiposContrato"
            :periodicidades="periodicidades"
        />
    </PaginaPos>
</template>
