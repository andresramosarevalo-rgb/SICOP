<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import Heading from '@/components/Heading.vue';
import TablaContratos from '@/components/Nomina/TablaContratos.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { Contrato, Empleado } from '@/types';

const props = defineProps<{
    empleado: Empleado;
    tipoDocumento: string;
    contratos: Contrato[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
            { title: 'Expediente', href: '' },
        ],
    },
});

const datos = computed(() => [
    {
        etiqueta: 'Documento',
        valor: `${props.tipoDocumento} ${props.empleado.numero_documento}`,
    },
    { etiqueta: 'Correo electrónico', valor: props.empleado.email },
    { etiqueta: 'Teléfono', valor: props.empleado.telefono },
    { etiqueta: 'Dirección', valor: props.empleado.direccion },
    { etiqueta: 'Fecha de nacimiento', valor: props.empleado.fecha_nacimiento },
    { etiqueta: 'Área', valor: props.empleado.area?.nombre },
    { etiqueta: 'Cargo', valor: props.empleado.cargo },
]);
</script>

<template>
    <Head :title="`${empleado.nombres} ${empleado.apellidos}`" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="`${empleado.nombres} ${empleado.apellidos}`"
                description="Expediente del empleado."
            />
            <Badge :variant="empleado.es_activo ? 'secondary' : 'outline'">
                {{ empleado.es_activo ? 'Activo' : 'Inactivo' }}
            </Badge>
        </div>

        <dl class="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
            <div v-for="dato in datos" :key="dato.etiqueta">
                <dt class="text-sm text-muted-foreground">
                    {{ dato.etiqueta }}
                </dt>
                <dd class="font-medium">{{ dato.valor || '—' }}</dd>
            </div>
        </dl>

        <div class="flex flex-wrap gap-2">
            <Button as-child>
                <Link :href="nomina.empleados.edit(empleado.id)">Editar</Link>
            </Button>
            <Form
                v-bind="EmpleadoController.cambiarEstado.form(empleado.id)"
                v-slot="{ processing }"
            >
                <input
                    type="hidden"
                    name="es_activo"
                    :value="empleado.es_activo ? 0 : 1"
                />
                <Button variant="outline" :disabled="processing">
                    {{ empleado.es_activo ? 'Desactivar' : 'Activar' }}
                </Button>
            </Form>
        </div>

        <section class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <Heading
                    variant="small"
                    title="Contratos"
                    description="El más reciente primero."
                />
                <Button variant="outline" as-child>
                    <Link :href="nomina.empleados.contratos.create(empleado.id)"
                        >Nuevo contrato</Link
                    >
                </Button>
            </div>
            <TablaContratos v-if="contratos.length" :contratos="contratos" />
            <p v-else class="text-sm text-muted-foreground">
                El empleado no tiene contratos registrados.
            </p>
        </section>
    </div>
</template>
