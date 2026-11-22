<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import NovedadController from '@/actions/App/Http/Controllers/Nomina/NovedadController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearFecha, formatearPesos } from '@/lib/formato';
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

    return novedad.fecha_fin
        ? `hasta ${formatearFecha(novedad.fecha_fin)}`
        : '1 día';
}
</script>

<template>
    <Head title="Novedades" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Novedades
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Incidencias de asistencia y pagos eventuales que entran en
                    la liquidación.
                </p>
            </div>
            <Button
                as-child
                class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
            >
                <Link :href="nomina.novedades.create()">Registrar novedad</Link>
            </Button>
        </header>

        <Form
            v-bind="NovedadController.index.form()"
            class="rv flex flex-wrap items-end gap-4 rounded-[20px] border bg-card px-6 py-5"
            style="--i: 2"
        >
            <div class="flex min-w-[180px] flex-col gap-2">
                <Label
                    for="desde"
                    class="text-sm leading-[normal] font-semibold"
                    >Desde</Label
                >
                <Input
                    id="desde"
                    name="desde"
                    type="date"
                    class="campo-sicop"
                    :default-value="filtros.desde ?? ''"
                />
            </div>
            <div class="flex min-w-[180px] flex-col gap-2">
                <Label
                    for="hasta"
                    class="text-sm leading-[normal] font-semibold"
                    >Hasta</Label
                >
                <Input
                    id="hasta"
                    name="hasta"
                    type="date"
                    class="campo-sicop"
                    :default-value="filtros.hasta ?? ''"
                />
            </div>
            <div class="flex min-w-[220px] flex-col gap-2">
                <Label
                    for="empleado_id"
                    class="text-sm leading-[normal] font-semibold"
                    >Empleado</Label
                >
                <select
                    id="empleado_id"
                    name="empleado_id"
                    :value="filtros.empleado_id ?? ''"
                    class="campo-sicop"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="empleado in empleados"
                        :key="empleado.id"
                        :value="empleado.id"
                    >
                        {{ empleado.nombres }} {{ empleado.apellidos }}
                    </option>
                </select>
            </div>
            <Button
                class="boton-plano h-[50px] rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >Filtrar</Button
            >
        </Form>

        <PanelTabla titulo="Novedades registradas" :indice="3" ocultar-titulo>
            <table class="min-w-[800px]">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Empleado</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Detalle</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="novedad in novedades" :key="novedad.id">
                        <td class="whitespace-nowrap">
                            {{ formatearFecha(novedad.fecha_inicio) }}
                        </td>
                        <td class="font-semibold">
                            {{ novedad.empleado?.nombres }}
                            {{ novedad.empleado?.apellidos }}
                        </td>
                        <td>{{ novedad.tipo_etiqueta }}</td>
                        <td>{{ describirDetalle(novedad) }}</td>
                        <td>
                            <span
                                v-if="novedad.esta_liquidada"
                                class="inline-flex rounded-full bg-ok-bg px-3 py-[5px] text-[13px] leading-[normal] font-semibold text-success"
                                >Liquidada</span
                            >
                            <div v-else class="flex gap-2">
                                <Button
                                    variant="outline"
                                    as-child
                                    class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                                >
                                    <Link
                                        :href="
                                            nomina.novedades.edit(novedad.id)
                                        "
                                        >Editar</Link
                                    >
                                </Button>
                                <Form
                                    v-bind="
                                        NovedadController.destroy.form(
                                            novedad.id,
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
                    <tr v-if="novedades.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            No hay novedades para los filtros seleccionados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
