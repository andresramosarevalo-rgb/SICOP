<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PeriodoNominaController from '@/actions/App/Http/Controllers/Nomina/PeriodoNominaController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import nomina from '@/routes/nomina';
import type { Opcion } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Periodos', href: nomina.periodos.index() },
            { title: 'Nuevo periodo', href: nomina.periodos.create() },
        ],
    },
});

defineProps<{
    periodicidades: Opcion[];
    sedes: { id: number; nombre: string }[];
    puedeElegirSede: boolean;
}>();
</script>

<template>
    <Head title="Nuevo periodo" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.periodos.index()" />

        <Heading
            title="Nuevo periodo"
            description="Se liquidarán los empleados con contrato vigente de esta periodicidad."
        />

        <Form
            v-bind="PeriodoNominaController.store.form()"
            class="grid gap-4 sm:grid-cols-3"
            v-slot="{ errors, processing }"
        >
            <div v-if="puedeElegirSede" class="grid gap-2 sm:col-span-3">
                <Label for="sede_id">Sede</Label>
                <select
                    id="sede_id"
                    name="sede_id"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm dark:bg-input/30"
                >
                    <option value="">Todas las sedes</option>
                    <option
                        v-for="sede in sedes"
                        :key="sede.id"
                        :value="sede.id"
                    >
                        {{ sede.nombre }}
                    </option>
                </select>
                <InputError :message="errors.sede_id" />
            </div>
            <p
                v-else-if="sedes.length > 0"
                class="text-sm text-muted-foreground sm:col-span-3"
            >
                Periodo de la sede:
                <span class="font-medium text-foreground">{{
                    sedes[0].nombre
                }}</span>
            </p>
            <div class="grid gap-2">
                <Label for="periodicidad_pago">Periodicidad</Label>
                <select
                    id="periodicidad_pago"
                    name="periodicidad_pago"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm dark:bg-input/30"
                >
                    <option
                        v-for="periodicidad in periodicidades"
                        :key="periodicidad.valor"
                        :value="periodicidad.valor"
                    >
                        {{ periodicidad.etiqueta }}
                    </option>
                </select>
                <InputError :message="errors.periodicidad_pago" />
            </div>
            <div class="grid gap-2">
                <Label for="fecha_inicio">Desde</Label>
                <Input
                    id="fecha_inicio"
                    name="fecha_inicio"
                    type="date"
                    required
                />
                <InputError :message="errors.fecha_inicio" />
            </div>
            <div class="grid gap-2">
                <Label for="fecha_fin">Hasta</Label>
                <Input id="fecha_fin" name="fecha_fin" type="date" required />
                <InputError :message="errors.fecha_fin" />
            </div>
            <div class="sm:col-span-3">
                <Button :disabled="processing">Crear periodo</Button>
            </div>
        </Form>
    </div>
</template>
