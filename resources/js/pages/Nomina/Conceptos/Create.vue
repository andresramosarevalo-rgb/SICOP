<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ConceptoNominaController from '@/actions/App/Http/Controllers/Nomina/ConceptoNominaController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import CamposConcepto from '@/components/Nomina/CamposConcepto.vue';
import nomina from '@/routes/nomina';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Conceptos', href: nomina.conceptos.index() },
            { title: 'Nuevo concepto', href: '' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo concepto" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Nuevo concepto
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Defina un devengado o una deducción para usar en los recibos.
            </p>
        </header>

        <FormularioNomina
            :accion="ConceptoNominaController.store.form()"
            etiqueta-guardar="Guardar"
            :href-cancelar="nomina.conceptos.index()"
        >
            <template #default="{ errors }">
                <CamposConcepto :errors="errors" />
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
