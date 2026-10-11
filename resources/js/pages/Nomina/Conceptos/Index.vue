<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ConceptoNominaController from '@/actions/App/Http/Controllers/Nomina/ConceptoNominaController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.inicio.index()" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Conceptos de nómina"
                description="Devengos y deducciones que pueden aparecer en un recibo. Los de sistema los calcula la ley y no se modifican."
            />
            <Button as-child>
                <Link :href="nomina.conceptos.create()">Nuevo concepto</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Código</th>
                        <th class="px-4 py-2 font-medium">Nombre</th>
                        <th class="px-4 py-2 font-medium">Tipo</th>
                        <th class="px-4 py-2 font-medium">Valor</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="concepto in conceptos" :key="concepto.id">
                        <td class="px-4 py-2 font-mono text-xs">
                            {{ concepto.codigo }}
                        </td>
                        <td class="px-4 py-2">
                            {{ concepto.nombre }}
                            <Badge
                                v-if="concepto.es_sistema"
                                variant="outline"
                                class="ml-2"
                                >Sistema</Badge
                            >
                        </td>
                        <td class="px-4 py-2">
                            {{
                                concepto.tipo === 'devengo'
                                    ? 'Devengo'
                                    : 'Deducción'
                            }}
                        </td>
                        <td class="px-4 py-2">
                            {{ describirValor(concepto) }}
                        </td>
                        <td class="px-4 py-2">
                            <div
                                v-if="!concepto.es_sistema"
                                class="flex justify-end gap-2"
                            >
                                <Button variant="outline" size="sm" as-child>
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
                                        variant="ghost"
                                        size="sm"
                                        :disabled="processing"
                                        >Eliminar</Button
                                    >
                                </Form>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
