<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ConceptoNominaController from '@/actions/App/Http/Controllers/Nomina/ConceptoNominaController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposConcepto from '@/components/Nomina/CamposConcepto.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { ConceptoNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Conceptos', href: nomina.conceptos.index() },
            { title: 'Editar concepto', href: '' },
        ],
    },
});

defineProps<{
    concepto: ConceptoNomina;
}>();
</script>

<template>
    <Head title="Editar concepto" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.conceptos.index()" />

        <Heading
            title="Editar concepto"
            description="Devengo o deducción de valor fijo o porcentaje del salario."
        />

        <Form
            v-bind="ConceptoNominaController.update.form(concepto.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposConcepto :errors="errors" :concepto="concepto" />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
