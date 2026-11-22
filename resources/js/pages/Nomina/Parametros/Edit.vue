<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ParametroNominaController from '@/actions/App/Http/Controllers/Nomina/ParametroNominaController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import CamposParametros from '@/components/Nomina/CamposParametros.vue';
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

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Editar parámetros
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Valores de ley usados por el cálculo de la nómina.
            </p>
        </header>

        <FormularioNomina
            :accion="ParametroNominaController.update.form(parametro.id)"
            etiqueta-guardar="Guardar"
            :href-cancelar="nomina.parametros.index()"
        >
            <template #default="{ errors }">
                <CamposParametros :errors="errors" :parametro="parametro" />
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
