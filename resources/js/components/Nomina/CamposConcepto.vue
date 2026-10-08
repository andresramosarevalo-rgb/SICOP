<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ConceptoNomina } from '@/types';

const props = defineProps<{
    errors: Record<string, string | undefined>;
    concepto?: ConceptoNomina;
}>();

const formaCalculo = ref(props.concepto?.forma_calculo ?? 'valor_fijo');

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="codigo">Código</Label>
            <Input
                id="codigo"
                name="codigo"
                :default-value="concepto?.codigo"
                placeholder="BONO_VENTAS"
                required
            />
            <InputError :message="errors.codigo" />
        </div>
        <div class="grid gap-2">
            <Label for="nombre">Nombre</Label>
            <Input
                id="nombre"
                name="nombre"
                :default-value="concepto?.nombre"
                required
            />
            <InputError :message="errors.nombre" />
        </div>
        <div class="grid gap-2">
            <Label for="tipo">Tipo</Label>
            <select
                id="tipo"
                name="tipo"
                :class="claseSelect"
                :value="concepto?.tipo ?? 'devengo'"
            >
                <option value="devengo">Devengo (suma al pago)</option>
                <option value="deduccion">Deducción (resta del pago)</option>
            </select>
            <InputError :message="errors.tipo" />
        </div>
        <div class="grid gap-2">
            <Label for="forma_calculo">Forma de cálculo</Label>
            <select
                id="forma_calculo"
                v-model="formaCalculo"
                name="forma_calculo"
                :class="claseSelect"
            >
                <option value="valor_fijo">Valor fijo</option>
                <option value="porcentaje">Porcentaje del salario</option>
            </select>
            <InputError :message="errors.forma_calculo" />
        </div>
        <div v-if="formaCalculo === 'valor_fijo'" class="grid gap-2">
            <Label for="valor_base">Valor</Label>
            <Input
                id="valor_base"
                name="valor_base"
                type="number"
                min="0"
                step="0.01"
                :default-value="concepto?.valor_base ?? ''"
            />
            <InputError :message="errors.valor_base" />
        </div>
        <div v-else class="grid gap-2">
            <Label for="porcentaje_base">Porcentaje (%)</Label>
            <Input
                id="porcentaje_base"
                name="porcentaje_base"
                type="number"
                min="0"
                max="100"
                step="0.01"
                :default-value="concepto?.porcentaje_base ?? ''"
            />
            <InputError :message="errors.porcentaje_base" />
        </div>
        <label class="flex items-center gap-2 self-end text-sm">
            <input type="hidden" name="es_constitutivo_salario" value="0" />
            <input
                type="checkbox"
                name="es_constitutivo_salario"
                value="1"
                :checked="concepto?.es_constitutivo_salario"
            />
            Hace parte del salario (base de aportes)
        </label>
    </div>
</template>
