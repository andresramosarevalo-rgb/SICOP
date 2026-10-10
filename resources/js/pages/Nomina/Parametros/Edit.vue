<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ParametroNominaController from '@/actions/App/Http/Controllers/Nomina/ParametroNominaController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposParametros from '@/components/Nomina/CamposParametros.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { ParametroNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Parámetros legales', href: nomina.parametros.index() },
            { title: 'Editar parámetros', href: '' },
        ],
    },
});

defineProps<{
    parametro: ParametroNomina;
}>();
</script>

<template>
    <Head title="Editar parámetros" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.parametros.index()" />

        <Heading
            title="Editar parámetros"
            description="Valores de ley usados por el cálculo de la nómina."
        />

        <Form
            v-bind="ParametroNominaController.update.form(parametro.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposParametros :errors="errors" :parametro="parametro" />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
