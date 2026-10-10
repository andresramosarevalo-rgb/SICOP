<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposEmpleado from '@/components/Nomina/CamposEmpleado.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { Area, OpcionTipoDocumento } from '@/types';

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
}>();
</script>

<template>
    <Head title="Registrar empleado" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.empleados.index()" />

        <Heading
            title="Registrar empleado"
            description="Datos personales y área del trabajador."
        />

        <Form
            v-bind="EmpleadoController.store.form()"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposEmpleado
                :areas="areas"
                :tipos-documento="tiposDocumento"
                :errors="errors"
            />
            <div>
                <Button :disabled="processing">Registrar empleado</Button>
            </div>
        </Form>
    </div>
</template>
