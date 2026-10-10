<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ContratoController from '@/actions/App/Http/Controllers/Nomina/ContratoController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposContrato from '@/components/Nomina/CamposContrato.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { Contrato, Empleado, Opcion } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
            { title: 'Nuevo contrato', href: '' },
        ],
    },
});

defineProps<{
    empleado: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>;
    contratoVigente: Contrato | null;
    tiposContrato: Opcion[];
    periodicidades: Opcion[];
}>();
</script>

<template>
    <Head title="Nuevo contrato" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.empleados.show(empleado.id)" />

        <Heading
            title="Nuevo contrato"
            :description="`${empleado.nombres} ${empleado.apellidos}`"
        />

        <p
            v-if="contratoVigente"
            class="rounded-xl border bg-muted/50 p-4 text-sm"
        >
            El contrato vigente (desde {{ contratoVigente.fecha_inicio }},
            salario {{ formatearPesos(contratoVigente.valor_salario_base) }}) se
            cerrará el día anterior al inicio del nuevo.
        </p>

        <Form
            v-bind="ContratoController.store.form(empleado.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposContrato
                :errors="errors"
                :tipos-contrato="tiposContrato"
                :periodicidades="periodicidades"
            />
            <div>
                <Button :disabled="processing">Registrar contrato</Button>
            </div>
        </Form>
    </div>
</template>
