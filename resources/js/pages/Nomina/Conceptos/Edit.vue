<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ConceptoNominaController from '@/actions/App/Http/Controllers/Nomina/ConceptoNominaController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import CamposConcepto from '@/components/Nomina/CamposConcepto.vue';
import nomina from '@/routes/nomina';
import type { ConceptoNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Conceptos', href: nomina.conceptos.index() },
            { title: 'Editar concepto', href: '' },
        ],
    },
});

defineProps<{
    concepto: ConceptoNomina;
}>();
</script>

<template>
    <Head title="Editar concepto" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Editar concepto
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Defina un devengado o una deducción para usar en los recibos.
            </p>
        </header>

        <FormularioNomina
            :accion="ConceptoNominaController.update.form(concepto.id)"
            etiqueta-guardar="Guardar"
            :href-cancelar="nomina.conceptos.index()"
        >
            <template #default="{ errors }">
                <CamposConcepto :errors="errors" :concepto="concepto" />
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
