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
                <div class="min-w-0 flex-1 wrap-anywhere">
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
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button variant="ghost" size="sm" :disabled="processing"
                        >Retirar</Button
                    >
                </Form>
            </li>
        </ul>

        <p
            class="rounded-lg bg-muted/50 p-4 text-sm leading-relaxed text-muted-foreground"
        >
            Aquí se asignan conceptos personalizados activos. Los conceptos del
            sistema, como salario, salud y pensión, se calculan en la
            liquidación; las horas extra, vacaciones y otras incidencias se
            registran en Novedades.
        </p>

        <p v-if="!asignaciones.length" class="text-sm text-muted-foreground">
            Este empleado no tiene conceptos recurrentes asignados.
        </p>

        <p
            v-if="!conceptosDisponibles.length"
            class="text-sm text-muted-foreground"
        >
            No hay conceptos personalizados activos disponibles para asignar.
        </p>

        <Form
            v-else
            v-bind="AsignacionConceptoController.store.form(empleadoId)"
            :options="{ preserveScroll: true }"
            class="grid gap-4 rounded-xl border p-4 sm:grid-cols-2"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <p class="text-sm text-muted-foreground sm:col-span-2">
                Deje el valor vacío para utilizar el valor o porcentaje del
                concepto.
            </p>
            <div class="flex min-w-0 flex-col gap-2 sm:col-span-2">
                <Label for="concepto_nomina_id">Concepto</Label>
                <select
                    id="concepto_nomina_id"
                    name="concepto_nomina_id"
                    :aria-invalid="!!errors.concepto_nomina_id"
                    :aria-describedby="
                        errors.concepto_nomina_id
                            ? 'asignacion_concepto_nomina_id_error'
                            : undefined
                    "
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
                <InputError
                    reservar-espacio
                    id="asignacion_concepto_nomina_id_error"
                    :message="errors.concepto_nomina_id"
                />
            </div>
            <div class="flex min-w-0 flex-col gap-2">
                <Label for="valor_asignado">Valor (opcional)</Label>
                <Input
                    id="valor_asignado"
                    name="valor_asignado"
                    :aria-invalid="!!errors.valor_asignado"
                    :aria-describedby="
                        errors.valor_asignado
                            ? 'asignacion_valor_asignado_error'
                            : undefined
                    "
                    type="number"
                    min="0"
                    step="0.01"
                />
                <InputError
                    reservar-espacio
                    id="asignacion_valor_asignado_error"
                    :message="errors.valor_asignado"
                />
            </div>
            <div class="flex min-w-0 flex-col gap-2">
                <Label for="fecha_inicio_asignacion">Desde</Label>
                <Input
                    id="fecha_inicio_asignacion"
                    name="fecha_inicio"
                    :aria-invalid="!!errors.fecha_inicio"
                    :aria-describedby="
                        errors.fecha_inicio
                            ? 'asignacion_fecha_inicio_error'
                            : undefined
                    "
                    type="date"
                    required
                />
                <InputError
                    reservar-espacio
                    id="asignacion_fecha_inicio_error"
                    :message="errors.fecha_inicio"
                />
            </div>
            <div class="flex min-w-0 flex-col gap-2">
                <Label for="fecha_fin_asignacion">Hasta (opcional)</Label>
                <Input
                    id="fecha_fin_asignacion"
                    name="fecha_fin"
                    :aria-invalid="!!errors.fecha_fin"
                    :aria-describedby="
                        errors.fecha_fin
                            ? 'asignacion_fecha_fin_error'
                            : undefined
                    "
                    type="date"
                />
                <InputError
                    reservar-espacio
                    id="asignacion_fecha_fin_error"
                    :message="errors.fecha_fin"
                />
            </div>
            <div class="self-start sm:pt-[22px]">
                <Button :disabled="processing">Asignar concepto</Button>
            </div>
        </Form>
    </section>
</template>
