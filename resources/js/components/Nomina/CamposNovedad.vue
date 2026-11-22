<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ResumenVacacionesNovedad from '@/components/Nomina/ResumenVacacionesNovedad.vue';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    ConceptoNomina,
    Empleado,
    Novedad,
    OpcionTipoNovedad,
} from '@/types';

const props = defineProps<{
    errors: Record<string, string | undefined>;
    empleados: Pick<Empleado, 'id' | 'nombres' | 'apellidos'>[];
    tipos: OpcionTipoNovedad[];
    conceptos: Pick<ConceptoNomina, 'id' | 'nombre'>[];
    novedad?: Novedad;
}>();

const empleadoId = ref<string | number>(props.novedad?.empleado_id ?? '');
const fechaInicio = ref(props.novedad?.fecha_inicio ?? '');
const fechaFin = ref(props.novedad?.fecha_fin ?? '');
const tipo = ref(props.novedad?.tipo ?? props.tipos[0]?.valor);
const tipoSeleccionado = computed(() =>
    props.tipos.find((opcion) => opcion.valor === tipo.value),
);

const claseSelect = 'campo-sicop';

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <div
        class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
    >
        <div class="col-span-full flex flex-col gap-2">
            <Label for="empleado_id" :class="claseEtiqueta">Empleado</Label>
            <select
                id="empleado_id"
                v-model="empleadoId"
                name="empleado_id"
                :class="claseSelect"
                required
            >
                <option value="" disabled>Seleccione un empleado</option>
                <option
                    v-for="empleado in empleados"
                    :key="empleado.id"
                    :value="empleado.id"
                >
                    {{ empleado.nombres }} {{ empleado.apellidos }}
                </option>
            </select>
            <InputError :message="errors.empleado_id" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="tipo" :class="claseEtiqueta">Tipo de novedad</Label>
            <select id="tipo" v-model="tipo" name="tipo" :class="claseSelect">
                <option
                    v-for="opcion in tipos"
                    :key="opcion.valor"
                    :value="opcion.valor"
                >
                    {{ opcion.etiqueta }}
                </option>
            </select>
            <InputError :message="errors.tipo" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="fecha_inicio" :class="claseEtiqueta">{{
                tipoSeleccionado?.es_por_dias ? 'Desde' : 'Fecha'
            }}</Label>
            <Input
                class="campo-sicop"
                id="fecha_inicio"
                name="fecha_inicio"
                type="date"
                v-model="fechaInicio"
                required
            />
            <InputError :message="errors.fecha_inicio" />
        </div>
        <div v-if="tipoSeleccionado?.es_por_dias" class="flex flex-col gap-2">
            <Label for="fecha_fin" :class="claseEtiqueta">Hasta</Label>
            <Input
                class="campo-sicop"
                id="fecha_fin"
                name="fecha_fin"
                type="date"
                v-model="fechaFin"
            />
            <InputError :message="errors.fecha_fin" />
        </div>
        <ResumenVacacionesNovedad
            v-if="tipo === 'vacaciones'"
            :empleado-id="empleadoId"
            :fecha-inicio="fechaInicio"
            :fecha-fin="fechaFin"
            :novedad-id="novedad?.id"
        />
        <div v-if="tipoSeleccionado?.unidad" class="flex flex-col gap-2">
            <Label for="cantidad"
                >Cantidad de {{ tipoSeleccionado.unidad }}</Label
            >
            <Input
                class="campo-sicop"
                id="cantidad"
                name="cantidad"
                type="number"
                min="1"
                step="1"
                :default-value="novedad?.cantidad ?? ''"
            />
            <InputError :message="errors.cantidad" />
        </div>
        <template v-if="tipo === 'concepto_eventual'">
            <div class="flex flex-col gap-2">
                <Label for="concepto_nomina_id" :class="claseEtiqueta"
                    >Concepto</Label
                >
                <select
                    id="concepto_nomina_id"
                    name="concepto_nomina_id"
                    :class="claseSelect"
                >
                    <option
                        value=""
                        disabled
                        :selected="!novedad?.concepto_nomina_id"
                    >
                        Seleccione un concepto
                    </option>
                    <option
                        v-for="concepto in conceptos"
                        :key="concepto.id"
                        :value="concepto.id"
                        :selected="concepto.id === novedad?.concepto_nomina_id"
                    >
                        {{ concepto.nombre }}
                    </option>
                </select>
                <InputError :message="errors.concepto_nomina_id" />
            </div>
            <div class="flex flex-col gap-2">
                <Label for="valor_eventual" :class="claseEtiqueta">Valor</Label>
                <InputDinero
                    class="campo-sicop"
                    id="valor_eventual"
                    name="valor_eventual"
                    :default-value="novedad?.valor_eventual ?? ''"
                />
                <InputError :message="errors.valor_eventual" />
            </div>
        </template>
        <div class="col-span-full grid gap-2">
            <Label for="observacion" :class="claseEtiqueta"
                >Observación (opcional)</Label
            >
            <Input
                class="campo-sicop"
                id="observacion"
                name="observacion"
                :default-value="novedad?.observacion ?? ''"
            />
            <InputError :message="errors.observacion" />
        </div>
    </div>
</template>
