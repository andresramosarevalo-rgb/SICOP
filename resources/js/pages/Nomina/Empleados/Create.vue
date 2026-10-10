<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposContrato from '@/components/Nomina/CamposContrato.vue';
import CamposEmpleado from '@/components/Nomina/CamposEmpleado.vue';
import { Button } from '@/components/ui/button';
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
    tiposContrato: Opcion[];
    periodicidades: Opcion[];
}>();
</script>

<template>
    <Head title="Registrar empleado" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.empleados.index()" />

        <Heading
            title="Registrar empleado"
            description="Registre los datos del trabajador y su contrato. Sin contrato no se puede liquidar su nómina."
        />

        <Form
            v-bind="EmpleadoController.store.form()"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <section class="flex flex-col gap-4">
                <Heading variant="small" title="1. Datos del empleado" />
                <CamposEmpleado
                    :areas="areas"
                    :tipos-documento="tiposDocumento"
                    :errors="errors"
                />
            </section>
            <section class="flex flex-col gap-4 border-t pt-6">
                <Heading
                    variant="small"
                    title="2. Contrato"
                    description="Define el salario y cada cuánto se le paga (semanal, quincenal o mensual)."
                />
                <CamposContrato
                    prefijo="contrato"
                    :errors="errors"
                    :tipos-contrato="tiposContrato"
                    :periodicidades="periodicidades"
                />
            </section>
            <div>
                <Button :disabled="processing"
                    >Registrar empleado y contrato</Button
                >
            </div>
        </Form>
    </div>
</template>
