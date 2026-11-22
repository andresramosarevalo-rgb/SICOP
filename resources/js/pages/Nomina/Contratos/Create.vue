<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ContratoController from '@/actions/App/Http/Controllers/Nomina/ContratoController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import CamposContrato from '@/components/Nomina/CamposContrato.vue';
import { formatearFecha, formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { Contrato, Empleado, Opcion } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
            { title: 'Nuevo contrato', href: '' },
        ],
    },
});

defineProps<{
    empleado: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>;
    contratoVigente: Contrato | null;
    tiposContrato: Opcion[];
    periodicidades: Opcion[];
}>();
</script>

<template>
    <Head title="Registrar contrato" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Registrar contrato
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                {{ empleado.nombres }} {{ empleado.apellidos }}
            </p>
        </header>

        <FormularioNomina
            :accion="ContratoController.store.form(empleado.id)"
            :options="{ preserveScroll: 'errors' }"
            etiqueta-guardar="Registrar contrato"
            :href-cancelar="nomina.empleados.show(empleado.id)"
        >
            <template #default="{ errors }">
                <CamposContrato
                    :errors="errors"
                    :tipos-contrato="tiposContrato"
                    :periodicidades="periodicidades"
                />
            </template>
            <template #lateral>
                <aside
                    class="rv flex min-w-0 flex-[1_1_280px] flex-col gap-3.5 rounded-[22px] border border-brand-gold/55 bg-cream p-7"
                    style="--i: 3"
                    aria-labelledby="contrato-vigente-title"
                >
                    <h2
                        id="contrato-vigente-title"
                        class="font-heading text-lg leading-[normal] font-bold"
                    >
                        Contrato vigente
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Los contratos anteriores se conservan en el expediente
                        del empleado, el más reciente primero. El contrato
                        vigente se cerrará el día anterior al inicio del nuevo.
                    </p>
                    <div
                        v-if="contratoVigente"
                        class="rounded-2xl border border-line-cream bg-card p-4 text-sm"
                    >
                        <span class="text-muted-foreground"
                            >Vigente actual:
                        </span>
                        <strong
                            >Desde
                            {{ formatearFecha(contratoVigente.fecha_inicio) }} ·
                            salario
                            {{
                                formatearPesos(
                                    contratoVigente.valor_salario_base,
                                )
                            }}</strong
                        >
                    </div>
                </aside>
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
