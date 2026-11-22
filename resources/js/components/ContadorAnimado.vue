<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { formatearPesos } from '@/lib/formato';

const props = defineProps<{
    valor: number | string;
    moneda?: boolean;
}>();

const DURACION_MS = 1200;

const mostrado = ref(0);
let animacion: number | null = null;

const reducirMovimiento = (): boolean =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Lleva el número mostrado desde su valor actual hasta el nuevo con una curva suave.
 */
function animarHasta(destino: number): void {
    if (animacion !== null) {
        cancelAnimationFrame(animacion);
    }

    if (reducirMovimiento()) {
        mostrado.value = destino;

        return;
    }

    const origen = mostrado.value;
    const inicio = performance.now();

    const paso = (ahora: number): void => {
        const avance = Math.min((ahora - inicio) / DURACION_MS, 1);
        const suavizado = 1 - Math.pow(1 - avance, 3);

        mostrado.value = origen + (destino - origen) * suavizado;
        animacion = avance < 1 ? requestAnimationFrame(paso) : null;
    };

    animacion = requestAnimationFrame(paso);
}

onMounted(() => animarHasta(Number(props.valor)));
watch(
    () => props.valor,
    (nuevo) => animarHasta(Number(nuevo)),
);
onBeforeUnmount(() => {
    if (animacion !== null) {
        cancelAnimationFrame(animacion);
    }
});

const texto = computed(() =>
    props.moneda
        ? formatearPesos(Math.round(mostrado.value))
        : String(Math.round(mostrado.value)),
);
</script>

<template>
    <span>
        <span class="tabular-nums" aria-hidden="true">{{ texto }}</span>
        <span class="sr-only">{{
            moneda ? formatearPesos(valor) : valor
        }}</span>
    </span>
</template>
