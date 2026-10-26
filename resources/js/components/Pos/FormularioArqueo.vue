<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CierreCajaController from '@/actions/App/Http/Controllers/Pos/CierreCajaController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
import type { ResumenCaja } from '@/types';

const props = defineProps<{
    sedeId: number;
    fecha: string;
    resumen: ResumenCaja;
    valorEfectivoEsperado: number;
}>();

const efectivoContado = ref('');
const reportadoDatafono = ref('');
const reportadoQr = ref('');

function diferencia(digitado: string, esperado: number): number | null {
    return digitado === ''
        ? null
        : Math.round((Number(digitado) - esperado) * 100) / 100;
}

const diferenciaEfectivo = computed(() =>
    diferencia(efectivoContado.value, props.valorEfectivoEsperado),
);
const diferenciaDatafono = computed(() =>
    diferencia(
        reportadoDatafono.value,
        Number(props.resumen.valor_ventas_datafono),
    ),
);
const diferenciaQr = computed(() =>
    diferencia(reportadoQr.value, Number(props.resumen.valor_ventas_qr)),
);
const hayDiferencias = computed(() =>
    [
        diferenciaEfectivo.value,
        diferenciaDatafono.value,
        diferenciaQr.value,
    ].some((valor) => valor !== null && valor !== 0),
);
const valorAConsignar = computed(() =>
    efectivoContado.value === ''
        ? null
        : Number(efectivoContado.value) - Number(props.resumen.valor_base_caja),
);

function describir(valor: number | null): string {
    if (valor === null) {
        return '—';
    }

    if (valor === 0) {
        return 'Cuadra';
    }

    return `${valor > 0 ? 'Sobrante' : 'Faltante'} ${formatearPesos(Math.abs(valor))}`;
}
</script>

<template>
    <Form
        v-bind="CierreCajaController.store.form(sedeId)"
        class="grid gap-4 sm:grid-cols-3"
        v-slot="{ errors, processing }"
    >
        <input type="hidden" name="fecha" :value="fecha" />

        <div class="grid gap-2">
            <Label for="valor_efectivo_contado">Efectivo contado en caja</Label>
            <Input
                id="valor_efectivo_contado"
                v-model="efectivoContado"
                name="valor_efectivo_contado"
                type="number"
                min="0"
                step="0.01"
                required
            />
            <p class="text-xs text-muted-foreground">
                Todo el efectivo que hay, base incluida:
                {{ describir(diferenciaEfectivo) }}
            </p>
            <InputError :message="errors.valor_efectivo_contado" />
        </div>
        <div class="grid gap-2">
            <Label for="valor_reportado_datafono"
                >Total del datáfono (BOLD)</Label
            >
            <Input
                id="valor_reportado_datafono"
                v-model="reportadoDatafono"
                name="valor_reportado_datafono"
                type="number"
                min="0"
                step="0.01"
                required
            />
            <p class="text-xs text-muted-foreground">
                {{ describir(diferenciaDatafono) }}
            </p>
            <InputError :message="errors.valor_reportado_datafono" />
        </div>
        <div class="grid gap-2">
            <Label for="valor_reportado_qr">Total recibido por QR</Label>
            <Input
                id="valor_reportado_qr"
                v-model="reportadoQr"
                name="valor_reportado_qr"
                type="number"
                min="0"
                step="0.01"
                required
            />
            <p class="text-xs text-muted-foreground">
                {{ describir(diferenciaQr) }}
            </p>
            <InputError :message="errors.valor_reportado_qr" />
        </div>

        <div class="grid gap-2 sm:col-span-3">
            <Label for="justificacion">
                Justificación{{ hayDiferencias ? ' (obligatoria)' : '' }}
            </Label>
            <textarea
                id="justificacion"
                name="justificacion"
                rows="3"
                maxlength="1000"
                :required="hayDiferencias"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
            />
            <InputError :message="errors.justificacion" />
        </div>

        <div class="flex flex-wrap items-center gap-4 sm:col-span-3">
            <Button :disabled="processing">Cerrar día</Button>
            <p v-if="valorAConsignar !== null" class="text-sm">
                A consignar:
                <span class="font-semibold">{{
                    formatearPesos(valorAConsignar)
                }}</span>
            </p>
        </div>
    </Form>
</template>
