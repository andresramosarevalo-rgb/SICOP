<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import CamposEmpleado from '@/components/Nomina/CamposEmpleado.vue';
import CambiarEstadoEmpleado from '@/components/Nomina/CambiarEstadoEmpleado.vue';
import nomina from '@/routes/nomina';
import type { Area, Empleado, Opcion, OpcionTipoDocumento } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
            { title: 'Editar', href: '' },
        ],
    },
});

defineProps<{
    empleado: Empleado;
    areas: Pick<Area, 'id' | 'nombre'>[];
    tiposDocumento: OpcionTipoDocumento[];
    tiposCuentaBancaria: Opcion[];
    sedes: { id: number; nombre: string }[];
    puedeElegirSede: boolean;
}>();
</script>

<template>
    <Head title="Editar empleado" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Editar empleado
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    {{ empleado.nombres }} {{ empleado.apellidos }}
                </p>
            </div>
            <CambiarEstadoEmpleado :empleado="empleado" />
        </header>

        <FormularioNomina
            :accion="EmpleadoController.update.form(empleado.id)"
            :options="{ preserveScroll: 'errors' }"
            etiqueta-guardar="Guardar cambios"
            :href-cancelar="nomina.empleados.show(empleado.id)"
        >
            <template #default="{ errors }">
                <CamposEmpleado
                    :areas="areas"
                    :tipos-documento="tiposDocumento"
                    :tipos-cuenta-bancaria="tiposCuentaBancaria"
                    :sedes="sedes"
                    :puede-elegir-sede="puedeElegirSede"
                    :errors="errors"
                    :empleado="empleado"
                />
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
