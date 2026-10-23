<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Opcion } from '@/types';

const props = defineProps<{
    errors: Record<string, string | undefined>;
    formasPago: Opcion[];
    formaPago?: string;
    valorEfectivo?: string;
    valorDatafono?: string;
    valorQr?: string;
}>();

const forma = ref(props.formaPago ?? props.formasPago[0]?.valor ?? '');

const medios = [
    {
        campo: 'valor_efectivo',
        etiqueta: 'Valor en efectivo',
        valor: props.valorEfectivo,
        forma: 'efectivo',
    },
    {
        campo: 'valor_datafono',
        etiqueta: 'Valor en datáfono',
        valor: props.valorDatafono,
        forma: 'datafono',
    },
    {
        campo: 'valor_qr',
        etiqueta: 'Valor en QR',
        valor: props.valorQr,
        forma: 'qr',
    },
].filter((medio) => props.formasPago.some((o) => o.valor === medio.forma));

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <div class="grid gap-2">
        <Label for="forma_pago">Forma de pago</Label>
        <select
            id="forma_pago"
            v-model="forma"
            name="forma_pago"
            :class="claseSelect"
            required
        >
            <option
                v-for="opcion in formasPago"
                :key="opcion.valor"
                :value="opcion.valor"
            >
                {{ opcion.etiqueta }}
            </option>
        </select>
        <InputError :message="errors.forma_pago" />
    </div>
    <template v-if="forma === 'mixto'">
        <div v-for="medio in medios" :key="medio.campo" class="grid gap-2">
            <Label :for="medio.campo">{{ medio.etiqueta }}</Label>
            <Input
                :id="medio.campo"
                :name="medio.campo"
                type="number"
                min="0"
                step="0.01"
                :default-value="Number(medio.valor ?? 0)"
                required
            />
            <InputError :message="errors[medio.campo]" />
        </div>
    </template>
</template>
