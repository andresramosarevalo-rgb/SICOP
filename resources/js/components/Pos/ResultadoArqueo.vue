<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { CierreCaja } from '@/types';

defineProps<{
    cierre: CierreCaja;
}>();

function describir(valor: string): string {
    const numero = Number(valor);

    if (numero === 0) {
        return 'Cuadra';
    }

    return `${numero > 0 ? 'Sobrante' : 'Faltante'} ${formatearPesos(Math.abs(numero))}`;
}
</script>

<template>
    <section
        class="flex flex-col gap-5 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
    >
        <h2 class="text-base font-semibold">Arqueo del día</h2>
        <dl class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border bg-muted/30 p-4">
                <dt class="text-sm text-muted-foreground">Efectivo contado</dt>
                <dd class="mt-2 font-semibold tabular-nums">
                    {{ formatearPesos(cierre.valor_efectivo_contado) }}
                </dd>
                <dd
                    class="mt-2 text-sm font-medium"
                    :class="
                        Number(cierre.valor_diferencia_efectivo) === 0
                            ? 'text-success'
                            : Number(cierre.valor_diferencia_efectivo) < 0
                              ? 'text-destructive'
                              : 'text-warning'
                    "
                >
                    {{ describir(cierre.valor_diferencia_efectivo) }}
                </dd>
            </div>
            <div class="rounded-lg border bg-muted/30 p-4">
                <dt class="text-sm text-muted-foreground">Datáfono (BOLD)</dt>
                <dd class="mt-2 font-semibold tabular-nums">
                    {{ formatearPesos(cierre.valor_reportado_datafono) }}
                </dd>
                <dd
                    class="mt-2 text-sm font-medium"
                    :class="
                        Number(cierre.valor_diferencia_datafono) === 0
                            ? 'text-success'
                            : Number(cierre.valor_diferencia_datafono) < 0
                              ? 'text-destructive'
                              : 'text-warning'
                    "
                >
                    {{ describir(cierre.valor_diferencia_datafono) }}
                </dd>
            </div>
            <div class="rounded-lg border bg-muted/30 p-4">
                <dt class="text-sm text-muted-foreground">QR</dt>
                <dd class="mt-2 font-semibold tabular-nums">
                    {{ formatearPesos(cierre.valor_reportado_qr) }}
                </dd>
                <dd
                    class="mt-2 text-sm font-medium"
                    :class="
                        Number(cierre.valor_diferencia_qr) === 0
                            ? 'text-success'
                            : Number(cierre.valor_diferencia_qr) < 0
                              ? 'text-destructive'
                              : 'text-warning'
                    "
                >
                    {{ describir(cierre.valor_diferencia_qr) }}
                </dd>
            </div>
            <div
                class="rounded-lg border border-brand-gold/30 bg-accent/50 p-4 sm:col-span-3"
            >
                <dt class="text-sm text-muted-foreground">A consignar</dt>
                <dd class="text-xl font-semibold">
                    {{ formatearPesos(cierre.valor_a_consignar) }}
                </dd>
            </div>
            <div v-if="cierre.justificacion" class="sm:col-span-3">
                <dt class="text-sm text-muted-foreground">Justificación</dt>
                <dd class="whitespace-pre-line">{{ cierre.justificacion }}</dd>
            </div>
        </dl>
        <p v-if="cierre.cerrado_por" class="text-xs text-muted-foreground">
            Cerrado por {{ cierre.cerrado_por.name }}
        </p>
    </section>
</template>
