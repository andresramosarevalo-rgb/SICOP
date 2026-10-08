<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ParametroNomina } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    parametro?: ParametroNomina;
}>();

const campos: {
    nombre: keyof ParametroNomina;
    etiqueta: string;
    paso: string;
}[] = [
    { nombre: 'anio', etiqueta: 'Año', paso: '1' },
    {
        nombre: 'valor_salario_minimo',
        etiqueta: 'Salario mínimo mensual (SMMLV)',
        paso: '0.01',
    },
    {
        nombre: 'valor_auxilio_transporte',
        etiqueta: 'Auxilio de transporte mensual',
        paso: '0.01',
    },
    { nombre: 'valor_uvt', etiqueta: 'Valor de la UVT', paso: '0.01' },
    {
        nombre: 'porcentaje_salud_empleado',
        etiqueta: 'Salud a cargo del empleado (%)',
        paso: '0.01',
    },
    {
        nombre: 'porcentaje_pension_empleado',
        etiqueta: 'Pensión a cargo del empleado (%)',
        paso: '0.01',
    },
    {
        nombre: 'horas_mensuales',
        etiqueta: 'Horas laborales al mes',
        paso: '1',
    },
    {
        nombre: 'porcentaje_recargo_hora_extra_diurna',
        etiqueta: 'Recargo hora extra diurna (%)',
        paso: '0.01',
    },
    {
        nombre: 'porcentaje_recargo_hora_extra_nocturna',
        etiqueta: 'Recargo hora extra nocturna (%)',
        paso: '0.01',
    },
    {
        nombre: 'porcentaje_recargo_nocturno',
        etiqueta: 'Recargo nocturno (%)',
        paso: '0.01',
    },
    {
        nombre: 'porcentaje_recargo_dominical_festivo',
        etiqueta: 'Recargo dominical o festivo (%)',
        paso: '0.01',
    },
    {
        nombre: 'porcentaje_incapacidad',
        etiqueta: 'Pago de incapacidad (%)',
        paso: '0.01',
    },
];
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="campo in campos" :key="campo.nombre" class="grid gap-2">
            <Label :for="campo.nombre">{{ campo.etiqueta }}</Label>
            <Input
                :id="campo.nombre"
                :name="campo.nombre"
                type="number"
                min="0"
                :step="campo.paso"
                :default-value="parametro?.[campo.nombre]"
                required
            />
            <InputError :message="errors[campo.nombre]" />
        </div>
    </div>
</template>
