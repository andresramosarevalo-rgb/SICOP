<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SedeController from '@/actions/App/Http/Controllers/Pos/SedeController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposSede from '@/components/Pos/CamposSede.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { Sede } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
            { title: 'Editar sede', href: '' },
        ],
    },
});

defineProps<{
    sede: Sede;
}>();
</script>

<template>
    <Head title="Editar sede" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.sedes.index()" />

        <Heading
            title="Editar sede"
            description="Desactive la sede si ya no opera; sus registros se conservan."
        />

        <Form
            v-bind="SedeController.update.form(sede.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposSede :errors="errors" :sede="sede" />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
