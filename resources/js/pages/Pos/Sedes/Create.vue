<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SedeController from '@/actions/App/Http/Controllers/Pos/SedeController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposSede from '@/components/Pos/CamposSede.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
            { title: 'Nueva sede', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva sede" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.sedes.index()" />

        <Heading
            title="Nueva sede"
            description="Nombre, ciudad y dirección de la sede."
        />

        <Form
            v-bind="SedeController.store.form()"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposSede :errors="errors" />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
