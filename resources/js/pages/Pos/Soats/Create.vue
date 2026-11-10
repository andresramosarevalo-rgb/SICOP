<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SoatController from '@/actions/App/Http/Controllers/Pos/SoatController';
import Heading from '@/components/Heading.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposSoat from '@/components/Pos/CamposSoat.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { Opcion, OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    formasPago: Opcion[];
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

    <PaginaPos formulario>
        <BotonAtras :respaldo="pos.sedes.soats.index(props.sede.id)" />

        <Heading title="Registrar SOAT" :description="props.sede.nombre" />

        <Form
            v-bind="SoatController.store.form(props.sede.id)"
            class="flex flex-col gap-6 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
            v-slot="{ errors, processing }"
        >
            <CamposSoat
                :errors="errors"
                :tipos-documento="props.tiposDocumento"
                :formas-pago="props.formasPago"
            />
            <div class="flex justify-end border-t pt-5">
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </PaginaPos>
</template>
