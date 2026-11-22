<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import TablaEmpleados from '@/components/Nomina/TablaEmpleados.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import nomina from '@/routes/nomina';
import type { Empleado } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
        ],
    },
});

defineProps<{
    empleados: Empleado[];
    buscar: string;
    estado: string;
}>();
</script>

<template>
    <Head title="Empleados" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Empleados
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Expedientes de los trabajadores de COVIACOL.
                </p>
            </div>
            <Button
                as-child
                class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
            >
                <Link :href="nomina.empleados.create()"
                    >Registrar empleado</Link
                >
            </Button>
        </header>

        <Form
            v-bind="EmpleadoController.index.form()"
            class="rv flex flex-wrap items-end gap-4 rounded-[20px] border bg-card px-6 py-5"
            style="--i: 2"
        >
            <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-2">
                <Label
                    for="buscar"
                    class="text-sm leading-[normal] font-semibold"
                    >Buscar</Label
                >
                <Input
                    id="buscar"
                    name="buscar"
                    class="campo-sicop"
                    type="search"
                    :default-value="buscar"
                    placeholder="Buscar por documento o nombre"
                />
            </div>
            <div class="flex min-w-[200px] flex-col gap-2">
                <Label
                    for="estado"
                    class="text-sm leading-[normal] font-semibold"
                    >Estado</Label
                >
                <select
                    id="estado"
                    name="estado"
                    :value="estado"
                    class="campo-sicop"
                >
                    <option value="">Todos</option>
                    <option value="activos">Activos</option>
                    <option value="inactivos">Inactivos</option>
                </select>
            </div>
            <Button
                class="boton-plano h-[50px] rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >Buscar</Button
            >
        </Form>

        <PanelTabla titulo="Empleados registrados" :indice="3" ocultar-titulo>
            <TablaEmpleados v-if="empleados.length" :empleados="empleados" />
            <p
                v-else
                class="px-6 py-10 text-center text-sm text-muted-foreground"
            >
                {{
                    buscar || estado
                        ? 'Ningún empleado coincide con los filtros seleccionados.'
                        : 'Todavía no hay empleados registrados.'
                }}
            </p>
        </PanelTabla>
    </PaginaPos>
</template>
