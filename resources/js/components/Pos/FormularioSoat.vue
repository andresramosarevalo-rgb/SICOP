<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { nextTick, reactive } from 'vue';
import CamposSoat from '@/components/Pos/CamposSoat.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { Opcion, OpcionTipoDocumento, Sede, Soat } from '@/types';

const props = defineProps<{
    accion: { action: string; method: 'post' };
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    formasPago: Opcion[];
    soat?: Soat;
    etiquetaGuardar: string;
}>();

const cobro = reactive({ valor: Number(props.soat?.valor_soat ?? 0) });

/**
 * Mantiene el resumen del cobro al día con el valor que se escribe.
 */
function alEscribir(evento: Event): void {
    const formulario = evento.currentTarget as HTMLFormElement;

    // El valor en dinero vive en un campo oculto que se actualiza en el siguiente ciclo.
    nextTick(() => {
        cobro.valor = Number(new FormData(formulario).get('valor_soat')) || 0;
    });
}
</script>

<template>
    <div class="flex flex-wrap items-start gap-6">
        <Form
            v-bind="accion"
            class="rv flex min-w-0 flex-[2_1_560px] flex-col gap-[22px] rounded-[22px] border bg-card p-8"
            style="--i: 2"
            v-slot="{ errors, processing }"
            @input="alEscribir"
        >
            <CamposSoat
                :errors="errors"
                :tipos-documento="tiposDocumento"
                :formas-pago="formasPago"
                :soat="soat"
            />
            <div
                class="flex flex-wrap justify-end gap-3 border-t border-line-faint pt-3"
            >
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="pos.sedes.soats.index(sede.id)">Cancelar</Link>
                </Button>
                <Button
                    :disabled="processing"
                    class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >
                    {{ etiquetaGuardar }}
                </Button>
            </div>
        </Form>

        <aside
            class="rv flex min-w-0 flex-[1_1_280px] flex-col gap-[18px] rounded-[22px] border border-brand-gold/55 bg-cream p-7"
            style="--i: 3"
            aria-labelledby="resumen-cobro-soat-title"
        >
            <h2
                id="resumen-cobro-soat-title"
                class="font-heading text-lg leading-[normal] font-bold"
            >
                Resumen del cobro
            </h2>
            <div
                class="flex justify-between gap-3 text-[15px] leading-[normal]"
            >
                <span class="text-muted-foreground">Valor del SOAT</span>
                <span class="font-heading font-bold">{{
                    formatearPesos(cobro.valor)
                }}</span>
            </div>
            <div
                class="flex justify-between gap-3 border-t border-line-cream pt-3.5 text-base leading-[normal]"
            >
                <span class="font-semibold">Total a cobrar</span>
                <span class="font-heading font-extrabold">{{
                    formatearPesos(cobro.valor)
                }}</span>
            </div>
        </aside>
    </div>
</template>
