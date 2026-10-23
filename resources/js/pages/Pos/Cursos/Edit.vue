<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CursoController from '@/actions/App/Http/Controllers/Pos/CursoController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import CamposCurso from '@/components/Pos/CamposCurso.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { OpcionTipoDocumento, Curso, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    curso: Curso;
    porcentajesDescuento: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
            { title: 'Curso', href: '' },
            { title: 'Editar Curso', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Editar Curso" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.sedes.cursos.index(props.sede.id)" />

        <Heading title="Editar Curso" :description="props.sede.nombre" />

        <Form
            v-bind="CursoController.update.form(props.curso.id)"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <CamposCurso
                :errors="errors"
                :tipos-documento="props.tiposDocumento"
                :porcentajes-descuento="props.porcentajesDescuento"
                :curso="props.curso"
            />
            <div>
                <Button :disabled="processing">Guardar</Button>
            </div>
        </Form>
    </div>
</template>
