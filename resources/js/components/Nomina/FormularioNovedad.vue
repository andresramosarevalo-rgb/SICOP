<script setup lang="ts">
import { nextTick, reactive, ref } from 'vue';
import CamposNovedad from '@/components/Nomina/CamposNovedad.vue';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type {
    ConceptoNomina,
    Empleado,
    Novedad,
    OpcionTipoNovedad,
} from '@/types';

const props = defineProps<{
    accion: { action: string; method: 'post' };
    empleados: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>[];
    tipos: OpcionTipoNovedad[];
    conceptos: Pick<ConceptoNomina, 'id' | 'nombre'>[];
    novedad?: Novedad;
    limpiarAlGuardar?: boolean;
}>();

const versionFormulario = ref(0);

const cobertura = reactive({
    inicio: props.novedad?.fecha_inicio ?? '',
    fin: props.novedad?.fecha_fin ?? '',
    valor: Number(props.novedad?.valor_eventual ?? 0),
});

function diasCubiertos(): number {
    if (cobertura.inicio === '') {
        return 0;
    }

    if (cobertura.fin === '') {
        return 1;
    }

    const dias =
        (Date.parse(cobertura.fin) - Date.parse(cobertura.inicio)) / 86400000;

    return Number.isNaN(dias) ? 0 : Math.max(Math.round(dias) + 1, 0);
}

/**
 * Mantiene el resumen al día con las fechas y el valor que se escriben.
 */
function alEscribir(evento: Event): void {
    const formulario = evento.currentTarget as HTMLFormElement;

    // El valor en dinero vive en un campo oculto que se actualiza en el siguiente ciclo.
    nextTick(() => {
        const datos = new FormData(formulario);

        cobertura.inicio = String(datos.get('fecha_inicio') ?? '');
        cobertura.fin = String(datos.get('fecha_fin') ?? '');
        cobertura.valor = Number(datos.get('valor_eventual')) || 0;
    });
}

function alGuardar(): void {
    if (props.limpiarAlGuardar) {
        versionFormulario.value++;
        cobertura.inicio = '';
        cobertura.fin = '';
        cobertura.valor = 0;
    }
}
</script>

<template>
    <FormularioNomina
        :accion="accion"
        etiqueta-guardar="Guardar"
        :href-cancelar="nomina.novedades.index()"
        :reset-on-success="limpiarAlGuardar"
        @input="alEscribir"
        @change="alEscribir"
        @success="alGuardar"
    >
        <template #default="{ errors }">
            <CamposNovedad
                :key="versionFormulario"
                :errors="errors"
                :empleados="empleados"
                :tipos="tipos"
                :conceptos="conceptos"
                :novedad="novedad"
            />
        </template>
        <template #lateral>
            <aside
                class="rv flex min-w-0 flex-[1_1_280px] flex-col gap-4 rounded-[22px] border border-brand-gold/55 bg-cream p-7"
                style="--i: 3"
                aria-labelledby="resumen-novedad-title"
            >
                <h2
                    id="resumen-novedad-title"
                    class="font-heading text-lg leading-[normal] font-bold"
                >
                    Resumen
                </h2>
                <div
                    class="flex justify-between gap-3 text-[15px] leading-[normal]"
                >
                    <span class="text-muted-foreground">Días cubiertos</span>
                    <span class="font-heading font-bold">{{
                        diasCubiertos()
                    }}</span>
                </div>
                <div
                    class="flex justify-between gap-3 text-[15px] leading-[normal]"
                >
                    <span class="text-muted-foreground">Valor total</span>
                    <span class="font-heading font-bold">{{
                        formatearPesos(cobertura.valor)
                    }}</span>
                </div>
            </aside>
        </template>
    </FormularioNomina>
</template>
