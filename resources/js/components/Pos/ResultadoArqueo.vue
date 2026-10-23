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
    <section class="flex flex-col gap-4 rounded-xl border p-4">
        <h2 class="font-medium">Arqueo del día</h2>
        <dl class="grid gap-3 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-muted-foreground">Efectivo contado</dt>
                <dd class="font-semibold">
                    {{ formatearPesos(cierre.valor_efectivo_contado) }}
                </dd>
                <dd class="text-xs text-muted-foreground">
                    {{ describir(cierre.valor_diferencia_efectivo) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Datáfono (BOLD)</dt>
                <dd class="font-semibold">
                    {{ formatearPesos(cierre.valor_reportado_datafono) }}
                </dd>
                <dd class="text-xs text-muted-foreground">
                    {{ describir(cierre.valor_diferencia_datafono) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">QR</dt>
                <dd class="font-semibold">
                    {{ formatearPesos(cierre.valor_reportado_qr) }}
                </dd>
                <dd class="text-xs text-muted-foreground">
                    {{ describir(cierre.valor_diferencia_qr) }}
                </dd>
            </div>
            <div class="sm:col-span-3">
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
