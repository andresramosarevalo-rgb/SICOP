<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ConceptoNominaController from '@/actions/App/Http/Controllers/Nomina/ConceptoNominaController';
import Heading from '@/components/Heading.vue';
import CamposConcepto from '@/components/Nomina/CamposConcepto.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Conceptos', href: nomina.conceptos.index() },
            { title: 'Nuevo concepto', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo concepto" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Nuevo concepto"
            description="Devengo o deducción de valor fijo o porcentaje del salario."
        />

        <Form
            v-bind="ConceptoNominaController.store.form()"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposConcepto :errors="errors" />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
