<script setup lang="ts">
import { nextTick, ref, useAttrs, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { formatearMiles, normalizarDinero } from '@/lib/formato';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    name?: string;
    modelValue?: string | number | null;
    defaultValue?: string | number | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', valor: string): void;
}>();

const attrs = useAttrs();

/**
 * Valor que llega del servidor, por ejemplo `590000.00`, sin ceros decimales sobrantes.
 */
function desdeServidor(valor: string | number | null | undefined): string {
    return valor === null || valor === undefined || valor === ''
        ? ''
        : normalizarDinero(Number(valor));
}

const crudo = ref(desdeServidor(props.modelValue ?? props.defaultValue));
const visible = ref(formatearMiles(crudo.value));

watch(
    () => props.modelValue,
    (valor) => {
        const nuevo =
            valor === crudo.value ? crudo.value : desdeServidor(valor);

        if (nuevo !== crudo.value) {
            crudo.value = nuevo;
            visible.value = formatearMiles(nuevo);
        }
    },
);

/**
 * Da formato de miles mientras se digita y conserva la posición del cursor.
 */
function alEscribir(evento: Event): void {
    const elemento = evento.target as HTMLInputElement;
    const cursor = elemento.selectionStart ?? elemento.value.length;
    const significativosAntes = elemento.value
        .slice(0, cursor)
        .replace(/[^\d,]/g, '').length;

    crudo.value = normalizarDinero(
        elemento.value.replace(/\./g, '').replace(',', '.'),
    );
    visible.value = formatearMiles(crudo.value);
    elemento.value = visible.value;
    emit('update:modelValue', crudo.value);

    nextTick(() => {
        let vistos = 0;
        let posicion = 0;

        while (
            posicion < visible.value.length &&
            vistos < significativosAntes
        ) {
            if (/[\d,]/.test(visible.value[posicion])) {
                vistos++;
            }

            posicion++;
        }

        elemento.setSelectionRange(posicion, posicion);
    });
}
</script>

<template>
    <div class="contents">
        <Input
            v-bind="attrs"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            :model-value="visible"
            @input="alEscribir"
        />
        <input v-if="name" type="hidden" :name="name" :value="crudo" />
    </div>
</template>
