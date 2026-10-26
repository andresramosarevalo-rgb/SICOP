<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CursoController from '@/actions/App/Http/Controllers/Pos/CursoController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposCurso from '@/components/Pos/CamposCurso.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];

    porcentajesDescuento: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cursos', href: '' },
            { title: 'Registrar curso', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Registrar curso" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.sedes.cursos.index(props.sede.id)" />

        <Heading title="Registrar curso" :description="props.sede.nombre" />

        <Form
            v-bind="CursoController.store.form(props.sede.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposCurso
                :errors="errors"
                :tipos-documento="props.tiposDocumento"
                :porcentajes-descuento="props.porcentajesDescuento"
            />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
