<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ParametroNominaController from '@/actions/App/Http/Controllers/Nomina/ParametroNominaController';
import Heading from '@/components/Heading.vue';
import CamposParametros from '@/components/Nomina/CamposParametros.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Parámetros legales', href: nomina.parametros.index() },
            { title: 'Nuevo año', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo año" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Nuevo año"
            description="Valores de ley usados por el cálculo de la nómina."
        />

        <Form
            v-bind="ParametroNominaController.store.form()"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposParametros :errors="errors" />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
