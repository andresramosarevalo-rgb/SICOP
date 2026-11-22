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

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <div class="flex flex-col gap-5 rounded-[22px] border bg-card px-7 py-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <h3 class="font-heading text-base leading-[normal] font-bold">
                    Referencia histórica de vacaciones
                </h3>
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
                class="boton-plano min-h-9 rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
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
            class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
            v-slot="{ errors, processing }"
            @success="esEdicion = false"
        >
            <p
                id="ayuda-vacaciones-historicas"
                class="col-span-full text-sm leading-relaxed text-muted-foreground"
            >
                Informe el total de días hábiles ya disfrutados hasta el corte,
                incluidos los que también estén registrados en SICOP. Las
                novedades solo añadirán consumo desde el día siguiente al corte.
                El saldo disponible se calculará a partir de los contratos y
                estos consumos.
            </p>
            <div class="flex min-w-0 flex-col gap-2">
                <Label for="dias_vacaciones_historicos" :class="claseEtiqueta">
                    Días hábiles disfrutados hasta el corte
                </Label>
                <Input
                    class="campo-sicop"
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
                <Label for="fecha_corte_vacaciones" :class="claseEtiqueta">
                    Fecha de corte histórico (inclusive)
                </Label>
                <Input
                    class="campo-sicop"
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
            <div class="col-span-full flex flex-wrap gap-2">
                <Button
                    :disabled="processing"
                    class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                    >Guardar referencia</Button
                >
                <Button
                    type="button"
                    variant="outline"
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                    :disabled="processing"
                    @click="esEdicion = false"
                >
                    Cancelar
                </Button>
            </div>
        </Form>
    </div>
</template>
