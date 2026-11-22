<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ConceptoNominaController from '@/actions/App/Http/Controllers/Nomina/ConceptoNominaController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { ConceptoNomina } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Conceptos', href: nomina.conceptos.index() },
        ],
    },
});

defineProps<{
    conceptos: ConceptoNomina[];
}>();

function describirValor(concepto: ConceptoNomina): string {
    if (concepto.forma_calculo === 'valor_fijo') {
        return formatearPesos(concepto.valor_base ?? 0);
    }

    if (concepto.forma_calculo === 'porcentaje') {
        return `${concepto.porcentaje_base} % del salario`;
    }

    return 'Calculado por ley';
}
</script>

<template>
    <Head title="Conceptos de nómina" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Conceptos de nómina
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Devengos y deducciones que pueden aparecer en un recibo. Los
                    de sistema los calcula la ley y no se modifican.
                </p>
            </div>
            <Button
                as-child
                class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
            >
                <Link :href="nomina.conceptos.create()">Nuevo concepto</Link>
            </Button>
        </header>

        <PanelTabla titulo="Conceptos registrados" :indice="2">
            <table class="min-w-[800px]">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Valor</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="concepto in conceptos" :key="concepto.id">
                        <td class="font-semibold tracking-[0.02em]">
                            {{ concepto.codigo }}
                        </td>
                        <td>
                            {{ concepto.nombre }}
                            <span
                                v-if="concepto.es_sistema"
                                class="ml-2 inline-flex rounded-full bg-cream px-2.5 py-1 text-xs leading-[normal] font-semibold text-warning"
                                >Sistema</span
                            >
                        </td>
                        <td>
                            <span
                                class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                :class="
                                    concepto.tipo === 'devengo'
                                        ? 'bg-ok-bg text-success'
                                        : 'bg-danger-bg text-danger'
                                "
                                >{{
                                    concepto.tipo === 'devengo'
                                        ? 'Devengado'
                                        : 'Deducción'
                                }}</span
                            >
                        </td>
                        <td class="font-heading">
                            {{ describirValor(concepto) }}
                        </td>
                        <td>
                            <div v-if="!concepto.es_sistema" class="flex gap-2">
                                <Button
                                    variant="outline"
                                    as-child
                                    class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                                >
                                    <Link
                                        :href="
                                            nomina.conceptos.edit(concepto.id)
                                        "
                                        >Editar</Link
                                    >
                                </Button>
                                <Form
                                    v-bind="
                                        ConceptoNominaController.destroy.form(
                                            concepto.id,
                                        )
                                    "
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        variant="outline"
                                        :disabled="processing"
                                        class="boton-plano min-h-9 rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold text-danger shadow-none hover:bg-card hover:text-danger dark:bg-card dark:hover:bg-card"
                                        >Eliminar</Button
                                    >
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="conceptos.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Todavía no hay conceptos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
