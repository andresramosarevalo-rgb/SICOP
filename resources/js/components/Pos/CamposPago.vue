<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import InputDinero from '@/components/InputDinero.vue';
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

const claseSelect = 'campo-sicop';

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label for="forma_pago" :class="claseEtiqueta">Forma de pago</Label>
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
        <div
            v-for="medio in medios"
            :key="medio.campo"
            class="flex flex-col gap-2"
        >
            <Label :for="medio.campo" :class="claseEtiqueta">{{
                medio.etiqueta
            }}</Label>
            <InputDinero
                class="campo-sicop"
                :id="medio.campo"
                :name="medio.campo"
                :default-value="Number(medio.valor ?? 0)"
                required
            />
            <InputError :message="errors[medio.campo]" />
        </div>
    </template>
</template>
