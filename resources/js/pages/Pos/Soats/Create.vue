<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SoatController from '@/actions/App/Http/Controllers/Pos/SoatController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposSoat from '@/components/Pos/CamposSoat.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'SOAT', href: '' },
            { title: 'Registrar SOAT', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Registrar SOAT" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.sedes.soats.index(props.sede.id)" />

        <Heading title="Registrar SOAT" :description="props.sede.nombre" />

        <Form
            v-bind="SoatController.store.form(props.sede.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposSoat
                :errors="errors"
                :tipos-documento="props.tiposDocumento"
            />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
