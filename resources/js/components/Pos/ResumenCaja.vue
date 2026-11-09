<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { ResumenCaja } from '@/types';

defineProps<{
    resumen: ResumenCaja;
    valorGastos: number;
    valorEfectivoEsperado: number;
}>();
</script>

<template>
    <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <dt class="text-sm text-muted-foreground">Base de caja</dt>
            <dd class="mt-2 text-xl font-semibold tabular-nums">
                {{ formatearPesos(resumen.valor_base_caja) }}
            </dd>
        </div>
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <dt class="text-sm text-muted-foreground">Ventas en efectivo</dt>
            <dd class="mt-2 text-xl font-semibold tabular-nums">
                {{ formatearPesos(resumen.valor_efectivo_ventas) }}
            </dd>
            <dd class="text-xs text-muted-foreground">
                {{ resumen.cantidad_cursos }} cursos ·
                {{ resumen.cantidad_soats }} SOAT
            </dd>
        </div>
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <dt class="text-sm text-muted-foreground">Ventas en datáfono</dt>
            <dd class="mt-2 text-xl font-semibold tabular-nums">
                {{ formatearPesos(resumen.valor_ventas_datafono) }}
            </dd>
        </div>
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <dt class="text-sm text-muted-foreground">Ventas en QR</dt>
            <dd class="mt-2 text-xl font-semibold tabular-nums">
                {{ formatearPesos(resumen.valor_ventas_qr) }}
            </dd>
        </div>
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <dt class="text-sm text-muted-foreground">Pago a gestores</dt>
            <dd class="mt-2 text-xl font-semibold tabular-nums">
                − {{ formatearPesos(resumen.valor_pago_gestores) }}
            </dd>
        </div>
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <dt class="text-sm text-muted-foreground">Gastos</dt>
            <dd class="mt-2 text-xl font-semibold tabular-nums">
                − {{ formatearPesos(valorGastos) }}
            </dd>
        </div>
        <div
            class="rounded-xl border border-brand-gold/40 bg-accent/50 p-5 sm:col-span-2"
        >
            <dt class="text-sm text-muted-foreground">
                Efectivo que debe haber en caja
            </dt>
            <dd class="mt-2 text-2xl font-semibold tabular-nums">
                {{ formatearPesos(valorEfectivoEsperado) }}
            </dd>
        </div>
    </dl>
</template>
