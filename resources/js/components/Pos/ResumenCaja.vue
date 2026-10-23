<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { ResumenCaja } from '@/types';

defineProps<{
    resumen: ResumenCaja;
    valorEfectivoEsperado: number;
}>();
</script>

<template>
    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border p-4">
            <dt class="text-sm text-muted-foreground">Base de caja</dt>
            <dd class="text-lg font-semibold">
                {{ formatearPesos(resumen.valor_base_caja) }}
            </dd>
        </div>
        <div class="rounded-xl border p-4">
            <dt class="text-sm text-muted-foreground">Ventas en efectivo</dt>
            <dd class="text-lg font-semibold">
                {{ formatearPesos(resumen.valor_efectivo_ventas) }}
            </dd>
            <dd class="text-xs text-muted-foreground">
                {{ resumen.cantidad_cursos }} cursos ·
                {{ resumen.cantidad_soats }} SOAT
            </dd>
        </div>
        <div class="rounded-xl border p-4">
            <dt class="text-sm text-muted-foreground">Ventas en datáfono</dt>
            <dd class="text-lg font-semibold">
                {{ formatearPesos(resumen.valor_ventas_datafono) }}
            </dd>
        </div>
        <div class="rounded-xl border p-4">
            <dt class="text-sm text-muted-foreground">Ventas en QR</dt>
            <dd class="text-lg font-semibold">
                {{ formatearPesos(resumen.valor_ventas_qr) }}
            </dd>
        </div>
        <div class="rounded-xl border p-4">
            <dt class="text-sm text-muted-foreground">Pago a gestores</dt>
            <dd class="text-lg font-semibold">
                − {{ formatearPesos(resumen.valor_pago_gestores) }}
            </dd>
        </div>
        <div class="rounded-xl border bg-muted/50 p-4 sm:col-span-2">
            <dt class="text-sm text-muted-foreground">
                Efectivo que debe haber en caja
            </dt>
            <dd class="text-xl font-semibold">
                {{ formatearPesos(valorEfectivoEsperado) }}
            </dd>
        </div>
    </dl>
</template>
