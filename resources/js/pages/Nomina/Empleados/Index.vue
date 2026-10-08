<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import Heading from '@/components/Heading.vue';
import TablaEmpleados from '@/components/Nomina/TablaEmpleados.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import nomina from '@/routes/nomina';
import type { Empleado } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
        ],
    },
});

defineProps<{
    empleados: Empleado[];
    buscar: string;
}>();
</script>

<template>
    <Head title="Empleados" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Empleados"
            description="Expedientes de los trabajadores de COVIACOL."
        />

        <Form
            v-bind="EmpleadoController.index.form()"
            class="flex max-w-xl gap-2"
        >
            <Input
                name="buscar"
                type="search"
                :default-value="buscar"
                placeholder="Buscar por documento o nombre"
                aria-label="Buscar empleados"
            />
            <Button variant="outline">Buscar</Button>
        </Form>

        <TablaEmpleados v-if="empleados.length" :empleados="empleados" />
        <p v-else class="text-sm text-muted-foreground">
            {{
                buscar
                    ? 'Ningún empleado coincide con la búsqueda.'
                    : 'Todavía no hay empleados registrados.'
            }}
        </p>
    </div>
</template>
