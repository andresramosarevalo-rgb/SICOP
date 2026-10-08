<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import NovedadController from '@/actions/App/Http/Controllers/Nomina/NovedadController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type { Empleado, Novedad } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Novedades', href: nomina.novedades.index() },
        ],
    },
});

defineProps<{
    novedades: Novedad[];
    filtros: { empleado_id?: string; desde?: string; hasta?: string };
    empleados: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>[];
}>();

function describirDetalle(novedad: Novedad): string {
    if (novedad.valor_eventual !== null) {
        return `${novedad.concepto?.nombre ?? ''} · ${formatearPesos(novedad.valor_eventual)}`;
    }

    if (novedad.cantidad !== null) {
        return novedad.tipo === 'retardo'
            ? `${novedad.cantidad} min`
            : `${novedad.cantidad} h`;
    }

    return novedad.fecha_fin ? `hasta ${novedad.fecha_fin}` : '1 día';
}
</script>

<template>
    <Head title="Novedades" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Novedades"
                description="Incidencias de asistencia y pagos eventuales que entran en la liquidación."
            />
            <Button as-child>
                <Link :href="nomina.novedades.create()">Registrar novedad</Link>
            </Button>
        </div>

        <Form
            v-bind="NovedadController.index.form()"
            class="flex flex-wrap items-end gap-2"
        >
            <select
                name="empleado_id"
                :value="filtros.empleado_id ?? ''"
                aria-label="Empleado"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
            >
                <option value="">Todos los empleados</option>
                <option
                    v-for="empleado in empleados"
                    :key="empleado.id"
                    :value="empleado.id"
                >
                    {{ empleado.apellidos }}, {{ empleado.nombres }}
                </option>
            </select>
            <Input
                name="desde"
                type="date"
                :default-value="filtros.desde ?? ''"
                aria-label="Desde"
                class="w-auto"
            />
            <Input
                name="hasta"
                type="date"
                :default-value="filtros.hasta ?? ''"
                aria-label="Hasta"
                class="w-auto"
            />
            <Button variant="outline">Filtrar</Button>
        </Form>

        <div v-if="novedades.length" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Empleado</th>
                        <th class="px-4 py-2 font-medium">Tipo</th>
                        <th class="px-4 py-2 font-medium">Detalle</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="novedad in novedades" :key="novedad.id">
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ novedad.fecha_inicio }}
                        </td>
                        <td class="px-4 py-2">
                            {{ novedad.empleado?.apellidos }},
                            {{ novedad.empleado?.nombres }}
                        </td>
                        <td class="px-4 py-2">{{ novedad.tipo_etiqueta }}</td>
                        <td class="px-4 py-2">
                            {{ describirDetalle(novedad) }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                v-if="novedad.esta_liquidada"
                                variant="outline"
                                >Liquidada</Badge
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-muted-foreground">
            No hay novedades para los filtros seleccionados.
        </p>
    </div>
</template>
