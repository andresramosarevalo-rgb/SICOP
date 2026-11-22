<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, nextTick, reactive } from 'vue';
import CamposCurso from '@/components/Pos/CamposCurso.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { Curso, Opcion, OpcionTipoDocumento, Sede } from '@/types';

const props = defineProps<{
    accion: { action: string; method: 'post' };
    sede: Sede;
    tiposDocumento: OpcionTipoDocumento[];
    codigosInfraccion: Opcion[];
    formasPago: Opcion[];
    referidos: Opcion[];
    porcentajesDescuento: number[];
    curso?: Curso;
    etiquetaGuardar: string;
}>();

const cobro = reactive({
    valor: Number(props.curso?.valor_curso ?? 0),
    descuento: Number(
        props.curso?.porcentaje_descuento ?? props.porcentajesDescuento[0] ?? 0,
    ),
    formaPago: props.curso?.forma_pago ?? props.formasPago[0]?.valor ?? '',
});

const etiquetaFormaPago = computed(
    () =>
        props.formasPago.find((opcion) => opcion.valor === cobro.formaPago)
            ?.etiqueta ?? '—',
);

/**
 * Mantiene el resumen del cobro al día con lo que se escribe en el formulario.
 */
function alEscribir(evento: Event): void {
    const formulario = evento.currentTarget as HTMLFormElement;

    // El valor en dinero vive en un campo oculto que se actualiza en el siguiente ciclo.
    nextTick(() => {
        const datos = new FormData(formulario);

        cobro.valor = Number(datos.get('valor_curso')) || 0;
        cobro.descuento = Number(datos.get('porcentaje_descuento')) || 0;
        cobro.formaPago = String(datos.get('forma_pago') ?? '');
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
            @change="alEscribir"
        >
            <CamposCurso
                :errors="errors"
                :tipos-documento="tiposDocumento"
                :codigos-infraccion="codigosInfraccion"
                :formas-pago="formasPago"
                :referidos="referidos"
                :porcentajes-descuento="porcentajesDescuento"
                :curso="curso"
            />
            <div
                class="flex flex-wrap justify-end gap-3 border-t border-line-faint pt-3"
            >
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="pos.sedes.cursos.index(sede.id)"
                        >Cancelar</Link
                    >
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
            aria-labelledby="resumen-cobro-title"
        >
            <h2
                id="resumen-cobro-title"
                class="font-heading text-lg leading-[normal] font-bold"
            >
                Resumen del cobro
            </h2>
            <div
                class="flex justify-between gap-3 text-[15px] leading-[normal]"
            >
                <span class="text-muted-foreground">Valor del curso</span>
                <span class="font-heading font-bold">{{
                    formatearPesos(cobro.valor)
                }}</span>
            </div>
            <div
                class="flex justify-between gap-3 text-[15px] leading-[normal]"
            >
                <span class="text-muted-foreground">Descuento obtenido</span>
                <span class="font-heading font-bold"
                    >{{ cobro.descuento }} %</span
                >
            </div>
            <div
                class="flex justify-between gap-3 text-[15px] leading-[normal]"
            >
                <span class="text-muted-foreground">Forma de pago</span>
                <span class="font-heading font-bold">{{
                    etiquetaFormaPago
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
