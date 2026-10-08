<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import AsignacionConceptoController from '@/actions/App/Http/Controllers/Nomina/AsignacionConceptoController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
import type { AsignacionConcepto, ConceptoNomina } from '@/types';

defineProps<{
    empleadoId: number;
    asignaciones: AsignacionConcepto[];
    conceptosDisponibles: ConceptoNomina[];
}>();

function describirValor(asignacion: AsignacionConcepto): string {
    if (asignacion.valor_asignado !== null) {
        return formatearPesos(asignacion.valor_asignado);
    }

    return asignacion.concepto.forma_calculo === 'porcentaje'
        ? `${asignacion.concepto.porcentaje_base} % del salario`
        : formatearPesos(asignacion.concepto.valor_base ?? 0);
}

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <section class="flex flex-col gap-4">
        <Heading
            variant="small"
            title="Conceptos recurrentes"
            description="Bonos o descuentos que se aplican en cada liquidación mientras estén vigentes."
        />

        <ul v-if="asignaciones.length" class="divide-y rounded-xl border px-4">
            <li
                v-for="asignacion in asignaciones"
                :key="asignacion.id"
                class="flex flex-wrap items-center justify-between gap-3 py-3"
            >
                <div>
                    <p class="font-medium">
                        {{ asignacion.concepto.nombre }} ·
                        {{
                            asignacion.concepto.tipo === 'devengo'
                                ? 'Devengo'
                                : 'Deducción'
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ describirValor(asignacion) }} · desde
                        {{ asignacion.fecha_inicio }}
                        {{
                            asignacion.fecha_fin
                                ? `hasta ${asignacion.fecha_fin}`
                                : ''
                        }}
                    </p>
                </div>
                <Form
                    v-bind="
                        AsignacionConceptoController.destroy.form(asignacion.id)
                    "
                    v-slot="{ processing }"
                >
                    <Button variant="ghost" size="sm" :disabled="processing"
                        >Retirar</Button
                    >
                </Form>
            </li>
        </ul>

        <Form
            v-bind="AsignacionConceptoController.store.form(empleadoId)"
            class="grid gap-4 rounded-xl border p-4 sm:grid-cols-2"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2 sm:col-span-2">
                <Label for="concepto_nomina_id">Concepto</Label>
                <select
                    id="concepto_nomina_id"
                    name="concepto_nomina_id"
                    :class="claseSelect"
                    required
                >
                    <option value="">Seleccione un concepto</option>
                    <option
                        v-for="concepto in conceptosDisponibles"
                        :key="concepto.id"
                        :value="concepto.id"
                    >
                        {{ concepto.nombre }}
                    </option>
                </select>
                <InputError :message="errors.concepto_nomina_id" />
            </div>
            <div class="grid gap-2">
                <Label for="valor_asignado"
                    >Valor (vacío: el del concepto)</Label
                >
                <Input
                    id="valor_asignado"
                    name="valor_asignado"
                    type="number"
                    min="0"
                    step="0.01"
                />
                <InputError :message="errors.valor_asignado" />
            </div>
            <div class="grid gap-2">
                <Label for="fecha_inicio_asignacion">Desde</Label>
                <Input
                    id="fecha_inicio_asignacion"
                    name="fecha_inicio"
                    type="date"
                    required
                />
                <InputError :message="errors.fecha_inicio" />
            </div>
            <div class="grid gap-2">
                <Label for="fecha_fin_asignacion">Hasta (opcional)</Label>
                <Input id="fecha_fin_asignacion" name="fecha_fin" type="date" />
                <InputError :message="errors.fecha_fin" />
            </div>
            <div class="self-end">
                <Button :disabled="processing">Asignar concepto</Button>
            </div>
        </Form>
    </section>
</template>
