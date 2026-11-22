<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Opcion } from '@/types';

const props = withDefaults(
    defineProps<{
        errors: Record<string, string | undefined>;
        tiposContrato: Opcion[];
        periodicidades: Opcion[];
        /** Grupo del formulario donde va el contrato, por ejemplo "contrato" al registrarlo junto con el empleado. */
        prefijo?: string;
    }>(),
    { prefijo: '' },
);

function nombre(campo: string): string {
    return props.prefijo ? `${props.prefijo}[${campo}]` : campo;
}

function error(campo: string): string | undefined {
    return props.errors[props.prefijo ? `${props.prefijo}.${campo}` : campo];
}

function id(campo: string): string {
    return props.prefijo ? `${props.prefijo}_${campo}` : campo;
}

const claseSelect = 'campo-sicop';

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <div
        class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
    >
        <p class="col-span-full text-sm text-muted-foreground">
            En contratos a término fijo, la fecha de fin es obligatoria.
        </p>
        <div class="flex min-w-0 flex-col gap-2">
            <Label :for="id('tipo_contrato')" :class="claseEtiqueta"
                >Tipo de contrato</Label
            >
            <select
                :id="id('tipo_contrato')"
                :name="nombre('tipo_contrato')"
                :aria-invalid="!!error('tipo_contrato')"
                :aria-describedby="
                    error('tipo_contrato')
                        ? id('tipo_contrato_error')
                        : undefined
                "
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
            <InputError
                reservar-espacio
                :id="id('tipo_contrato_error')"
                :message="error('tipo_contrato')"
            />
        </div>
        <div class="flex min-w-0 flex-col gap-2">
            <Label :for="id('periodicidad_pago')" :class="claseEtiqueta"
                >Periodicidad de pago</Label
            >
            <select
                :id="id('periodicidad_pago')"
                :name="nombre('periodicidad_pago')"
                :aria-invalid="!!error('periodicidad_pago')"
                :aria-describedby="
                    error('periodicidad_pago')
                        ? id('periodicidad_pago_error')
                        : undefined
                "
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
            <InputError
                reservar-espacio
                :id="id('periodicidad_pago_error')"
                :message="error('periodicidad_pago')"
            />
        </div>
        <div class="flex min-w-0 flex-col gap-2">
            <Label :for="id('fecha_inicio')" :class="claseEtiqueta"
                >Fecha de inicio</Label
            >
            <Input
                class="campo-sicop"
                :id="id('fecha_inicio')"
                :name="nombre('fecha_inicio')"
                :aria-invalid="!!error('fecha_inicio')"
                :aria-describedby="
                    error('fecha_inicio') ? id('fecha_inicio_error') : undefined
                "
                type="date"
                required
            />
            <InputError
                reservar-espacio
                :id="id('fecha_inicio_error')"
                :message="error('fecha_inicio')"
            />
        </div>
        <div class="flex min-w-0 flex-col gap-2">
            <Label :for="id('fecha_fin')" :class="claseEtiqueta"
                >Fecha de fin</Label
            >
            <Input
                class="campo-sicop"
                :id="id('fecha_fin')"
                :name="nombre('fecha_fin')"
                :aria-invalid="!!error('fecha_fin')"
                :aria-describedby="
                    error('fecha_fin') ? id('fecha_fin_error') : undefined
                "
                type="date"
            />
            <InputError
                reservar-espacio
                :id="id('fecha_fin_error')"
                :message="error('fecha_fin')"
            />
        </div>
        <div class="flex min-w-0 flex-col gap-2">
            <Label :for="id('valor_salario_base')" :class="claseEtiqueta"
                >Salario base mensual</Label
            >
            <InputDinero
                class="campo-sicop"
                :id="id('valor_salario_base')"
                :name="nombre('valor_salario_base')"
                :aria-invalid="!!error('valor_salario_base')"
                :aria-describedby="
                    error('valor_salario_base')
                        ? id('valor_salario_base_error')
                        : undefined
                "
                required
            />
            <InputError
                reservar-espacio
                :id="id('valor_salario_base_error')"
                :message="error('valor_salario_base')"
            />
        </div>
    </div>
</template>
