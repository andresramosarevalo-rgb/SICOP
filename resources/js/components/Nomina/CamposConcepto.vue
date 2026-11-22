<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ConceptoNomina } from '@/types';

const props = defineProps<{
    errors: Record<string, string | undefined>;
    concepto?: ConceptoNomina;
}>();

const formaCalculo = ref(props.concepto?.forma_calculo ?? 'valor_fijo');

const claseSelect = 'campo-sicop';

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <div
        class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
    >
        <div class="flex flex-col gap-2">
            <Label for="codigo" :class="claseEtiqueta">Código</Label>
            <Input
                class="campo-sicop"
                id="codigo"
                name="codigo"
                :default-value="concepto?.codigo"
                placeholder="BONO_VENTAS"
                required
            />
            <InputError :message="errors.codigo" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="nombre" :class="claseEtiqueta">Nombre</Label>
            <Input
                class="campo-sicop"
                id="nombre"
                name="nombre"
                :default-value="concepto?.nombre"
                required
            />
            <InputError :message="errors.nombre" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="tipo" :class="claseEtiqueta">Tipo</Label>
            <select id="tipo" name="tipo" :class="claseSelect">
                <option
                    value="devengo"
                    :selected="(concepto?.tipo ?? 'devengo') === 'devengo'"
                >
                    Devengo (suma al pago)
                </option>
                <option
                    value="deduccion"
                    :selected="concepto?.tipo === 'deduccion'"
                >
                    Deducción (resta del pago)
                </option>
            </select>
            <InputError :message="errors.tipo" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="forma_calculo" :class="claseEtiqueta"
                >Forma de cálculo</Label
            >
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
        <div v-if="formaCalculo === 'valor_fijo'" class="flex flex-col gap-2">
            <Label for="valor_base" :class="claseEtiqueta">Valor</Label>
            <InputDinero
                class="campo-sicop"
                id="valor_base"
                name="valor_base"
                :default-value="concepto?.valor_base ?? ''"
            />
            <InputError :message="errors.valor_base" />
        </div>
        <div v-else class="flex flex-col gap-2">
            <Label for="porcentaje_base" :class="claseEtiqueta"
                >Porcentaje (%)</Label
            >
            <Input
                class="campo-sicop"
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
