<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PeriodoNominaController from '@/actions/App/Http/Controllers/Nomina/PeriodoNominaController';
import Heading from '@/components/Heading.vue';
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
}>();
</script>

<template>
    <Head title="Nuevo periodo" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Nuevo periodo"
            description="Se liquidarán los empleados con contrato vigente de esta periodicidad."
        />

        <Form
            v-bind="PeriodoNominaController.store.form()"
            class="grid gap-4 sm:grid-cols-3"
            v-slot="{ errors, processing }"
        >
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
