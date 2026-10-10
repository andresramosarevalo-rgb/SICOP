<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label :for="id('tipo_contrato')">Tipo de contrato</Label>
            <select
                :id="id('tipo_contrato')"
                :name="nombre('tipo_contrato')"
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
            <InputError :message="error('tipo_contrato')" />
        </div>
        <div class="grid gap-2">
            <Label :for="id('periodicidad_pago')">Periodicidad de pago</Label>
            <select
                :id="id('periodicidad_pago')"
                :name="nombre('periodicidad_pago')"
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
            <InputError :message="error('periodicidad_pago')" />
        </div>
        <div class="grid gap-2">
            <Label :for="id('fecha_inicio')">Fecha de inicio</Label>
            <Input
                :id="id('fecha_inicio')"
                :name="nombre('fecha_inicio')"
                type="date"
                required
            />
            <InputError :message="error('fecha_inicio')" />
        </div>
        <div class="grid gap-2">
            <Label :for="id('fecha_fin')"
                >Fecha de fin (obligatoria si es a término fijo)</Label
            >
            <Input
                :id="id('fecha_fin')"
                :name="nombre('fecha_fin')"
                type="date"
            />
            <InputError :message="error('fecha_fin')" />
        </div>
        <div class="grid gap-2">
            <Label :for="id('valor_salario_base')">Salario base mensual</Label>
            <Input
                :id="id('valor_salario_base')"
                :name="nombre('valor_salario_base')"
                type="number"
                min="0"
                step="0.01"
                required
            />
            <InputError :message="error('valor_salario_base')" />
        </div>
    </div>
</template>
