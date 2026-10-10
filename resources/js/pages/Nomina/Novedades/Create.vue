<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import NovedadController from '@/actions/App/Http/Controllers/Nomina/NovedadController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposNovedad from '@/components/Nomina/CamposNovedad.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.novedades.index()" />

        <Heading
            title="Registrar novedad"
            description="Faltas, retardos, horas extra, recargos, incapacidades, vacaciones o conceptos eventuales."
        />

        <Form
            v-bind="NovedadController.store.form()"
            class="flex flex-col gap-6"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <CamposNovedad
                :errors="errors"
                :empleados="empleados"
                :tipos="tipos"
                :conceptos="conceptos"
            />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
