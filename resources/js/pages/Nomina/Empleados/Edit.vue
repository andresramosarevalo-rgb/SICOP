<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposEmpleado from '@/components/Nomina/CamposEmpleado.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { Area, Empleado, OpcionTipoDocumento } from '@/types';

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
}>();
</script>

<template>
    <Head title="Editar empleado" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.empleados.show(empleado.id)" />

        <Heading
            title="Editar empleado"
            :description="`${empleado.nombres} ${empleado.apellidos}`"
        />

        <Form
            v-bind="EmpleadoController.update.form(empleado.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposEmpleado
                :areas="areas"
                :tipos-documento="tiposDocumento"
                :errors="errors"
                :empleado="empleado"
            />
            <div>
                <Button :disabled="processing">Guardar cambios</Button>
            </div>
        </Form>
    </div>
</template>
