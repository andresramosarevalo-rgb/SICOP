<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CursoController from '@/actions/App/Http/Controllers/Pos/CursoController';
import Heading from '@/components/Heading.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposCurso from '@/components/Pos/CamposCurso.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { Opcion, OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    codigosInfraccion: Opcion[];
    formasPago: Opcion[];
    referidos: Opcion[];

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

    <PaginaPos formulario>
        <BotonAtras :respaldo="pos.sedes.cursos.index(props.sede.id)" />

        <Heading title="Registrar curso" :description="props.sede.nombre" />

        <Form
            v-bind="CursoController.store.form(props.sede.id)"
            class="flex flex-col gap-6 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
            v-slot="{ errors, processing }"
        >
            <CamposCurso
                :errors="errors"
                :tipos-documento="props.tiposDocumento"
                :codigos-infraccion="props.codigosInfraccion"
                :formas-pago="props.formasPago"
                :referidos="props.referidos"
                :porcentajes-descuento="props.porcentajesDescuento"
            />
            <div class="flex justify-end border-t pt-5">
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </PaginaPos>
</template>
