<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ContratoController from '@/actions/App/Http/Controllers/Nomina/ContratoController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
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

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <Head title="Nuevo contrato" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Nuevo contrato"
            :description="`${empleado.nombres} ${empleado.apellidos}`"
        />

        <p
            v-if="contratoVigente"
            class="rounded-xl border bg-muted/50 p-4 text-sm"
        >
            El contrato vigente (desde {{ contratoVigente.fecha_inicio }},
            salario {{ formatearPesos(contratoVigente.valor_salario_base) }}) se
            cerrará el día anterior al inicio del nuevo.
        </p>

        <Form
            v-bind="ContratoController.store.form(empleado.id)"
            class="grid gap-4 sm:grid-cols-2"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="tipo_contrato">Tipo de contrato</Label>
                <select
                    id="tipo_contrato"
                    name="tipo_contrato"
                    :class="claseSelect"
                    required
                >
                    <option
                        v-for="tipo in tiposContrato"
                        :key="tipo.valor"
                        :value="tipo.valor"
                    >
                        {{ tipo.etiqueta }}
                    </option>
                </select>
                <InputError :message="errors.tipo_contrato" />
            </div>
            <div class="grid gap-2">
                <Label for="periodicidad_pago">Periodicidad de pago</Label>
                <select
                    id="periodicidad_pago"
                    name="periodicidad_pago"
                    :class="claseSelect"
                    required
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
                <Label for="fecha_inicio">Fecha de inicio</Label>
                <Input
                    id="fecha_inicio"
                    name="fecha_inicio"
                    type="date"
                    required
                />
                <InputError :message="errors.fecha_inicio" />
            </div>
            <div class="grid gap-2">
                <Label for="fecha_fin"
                    >Fecha de fin (obligatoria si es a término fijo)</Label
                >
                <Input id="fecha_fin" name="fecha_fin" type="date" />
                <InputError :message="errors.fecha_fin" />
            </div>
            <div class="grid gap-2">
                <Label for="valor_salario_base">Salario base mensual</Label>
                <Input
                    id="valor_salario_base"
                    name="valor_salario_base"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                />
                <InputError :message="errors.valor_salario_base" />
            </div>
            <div class="sm:col-span-2">
                <Button :disabled="processing">Registrar contrato</Button>
            </div>
        </Form>
    </div>
</template>
