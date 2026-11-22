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
    <section class="rv rounded-[22px] border bg-card px-7 py-6" style="--i: 2">
        <dl
            class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-5"
        >
            <div>
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Base de caja
                </dt>
                <dd
                    class="mt-1 font-heading text-[22px] leading-[normal] font-bold"
                >
                    {{ formatearPesos(resumen.valor_base_caja) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Ventas en efectivo
                </dt>
                <dd
                    class="mt-1 font-heading text-[22px] leading-[normal] font-bold"
                >
                    {{ formatearPesos(resumen.valor_efectivo_ventas) }}
                </dd>
                <dd class="mt-1 text-xs text-ink-3">
                    {{ resumen.cantidad_cursos }} cursos ·
                    {{ resumen.cantidad_soats }} SOAT
                </dd>
            </div>
            <div>
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Ventas en datáfono
                </dt>
                <dd
                    class="mt-1 font-heading text-[22px] leading-[normal] font-bold"
                >
                    {{ formatearPesos(resumen.valor_ventas_datafono) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Ventas en QR
                </dt>
                <dd
                    class="mt-1 font-heading text-[22px] leading-[normal] font-bold"
                >
                    {{ formatearPesos(resumen.valor_ventas_qr) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Pago a gestores
                </dt>
                <dd
                    class="mt-1 font-heading text-[22px] leading-[normal] font-bold"
                >
                    {{ formatearPesos(resumen.valor_pago_gestores) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Gastos
                </dt>
                <dd
                    class="mt-1 font-heading text-[22px] leading-[normal] font-bold"
                >
                    − {{ formatearPesos(valorGastos) }}
                </dd>
            </div>
            <div class="col-span-full border-t border-line-faint pt-4">
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    Efectivo que debe haber en caja
                </dt>
                <dd
                    class="mt-1 font-heading text-[26px] leading-[normal] font-extrabold"
                >
                    {{ formatearPesos(valorEfectivoEsperado) }}
                </dd>
            </div>
        </dl>
    </section>
</template>
