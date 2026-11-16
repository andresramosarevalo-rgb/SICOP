<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Empleado } from '@/types';

defineProps<{
    empleado: Empleado;
    fechaMaxima: string;
}>();

const esEdicion = ref(false);
</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl border p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <h3 class="font-medium">Referencia histórica de vacaciones</h3>
                <p
                    v-if="empleado.fecha_corte_vacaciones !== null"
                    class="text-sm text-muted-foreground"
                >
                    {{ empleado.dias_vacaciones_historicos }} días hábiles
                    disfrutados hasta el
                    <time :datetime="empleado.fecha_corte_vacaciones">
                        {{
                            empleado.fecha_corte_vacaciones
                                .split('-')
                                .reverse()
                                .join('/')
                        }} </time
                    >, incluido ese día.
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    No hay una referencia histórica registrada.
                </p>
            </div>
            <Button
                v-if="!esEdicion"
                type="button"
                variant="outline"
                :aria-expanded="esEdicion"
                aria-controls="formulario-vacaciones-historicas"
                @click="esEdicion = true"
            >
                {{
                    empleado.fecha_corte_vacaciones === null
                        ? 'Registrar referencia'
                        : 'Corregir referencia'
                }}
            </Button>
        </div>

        <Form
            v-if="esEdicion"
            id="formulario-vacaciones-historicas"
            v-bind="
                EmpleadoController.actualizarSaldoInicialVacaciones.form(
                    empleado.id,
                )
            "
            :options="{ preserveScroll: true }"
            class="grid gap-4 sm:grid-cols-2"
            v-slot="{ errors, processing }"
            @success="esEdicion = false"
        >
            <p
                id="ayuda-vacaciones-historicas"
                class="text-sm leading-relaxed text-muted-foreground sm:col-span-2"
            >
                Informe el total de días hábiles ya disfrutados hasta el corte,
                incluidos los que también estén registrados en SICOP. Las
                novedades solo añadirán consumo desde el día siguiente al corte.
                El saldo disponible se calculará a partir de los contratos y
                estos consumos.
            </p>
            <div class="flex min-w-0 flex-col gap-2">
                <Label for="dias_vacaciones_historicos">
                    Días hábiles disfrutados hasta el corte
                </Label>
                <Input
                    id="dias_vacaciones_historicos"
                    name="dias_vacaciones_historicos"
                    type="number"
                    min="0"
                    max="2147483647"
                    step="1"
                    required
                    :default-value="empleado.dias_vacaciones_historicos ?? ''"
                    :aria-invalid="!!errors.dias_vacaciones_historicos"
                    aria-describedby="ayuda-vacaciones-historicas error-dias-vacaciones-historicos"
                />
                <InputError
                    id="error-dias-vacaciones-historicos"
                    reservar-espacio
                    :message="errors.dias_vacaciones_historicos"
                />
            </div>
            <div class="flex min-w-0 flex-col gap-2">
                <Label for="fecha_corte_vacaciones">
                    Fecha de corte histórico (inclusive)
                </Label>
                <Input
                    id="fecha_corte_vacaciones"
                    name="fecha_corte_vacaciones"
                    type="date"
                    required
                    :max="fechaMaxima"
                    :default-value="
                        empleado.fecha_corte_vacaciones ?? fechaMaxima
                    "
                    :aria-invalid="!!errors.fecha_corte_vacaciones"
                    aria-describedby="error-fecha-corte-vacaciones"
                />
                <InputError
                    id="error-fecha-corte-vacaciones"
                    reservar-espacio
                    :message="errors.fecha_corte_vacaciones"
                />
            </div>
            <div class="flex flex-wrap gap-2 sm:col-span-2">
                <Button :disabled="processing">Guardar referencia</Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="processing"
                    @click="esEdicion = false"
                >
                    Cancelar
                </Button>
            </div>
        </Form>
    </div>
</template>
