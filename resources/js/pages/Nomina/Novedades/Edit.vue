<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import NovedadController from '@/actions/App/Http/Controllers/Nomina/NovedadController';
import Heading from '@/components/Heading.vue';
import CamposNovedad from '@/components/Nomina/CamposNovedad.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type {
    ConceptoNomina,
    Empleado,
    Novedad,
    OpcionTipoNovedad,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Novedades', href: nomina.novedades.index() },
            { title: 'Editar novedad', href: '' },
        ],
    },
});

defineProps<{
    novedad: Novedad;
    empleados: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>[];
    tipos: OpcionTipoNovedad[];
    conceptos: Pick<ConceptoNomina, 'id' | 'nombre'>[];
}>();
</script>

<template>
    <Head title="Editar novedad" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Editar novedad"
            description="Faltas, retardos, horas extra, recargos, incapacidades, vacaciones o conceptos eventuales."
        />

        <Form
            v-bind="NovedadController.update.form(novedad.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposNovedad
                :errors="errors"
                :empleados="empleados"
                :tipos="tipos"
                :conceptos="conceptos"
                :novedad="novedad"
            />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
