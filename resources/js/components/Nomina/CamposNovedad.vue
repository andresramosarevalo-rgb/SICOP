<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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

const tipo = ref(props.novedad?.tipo ?? props.tipos[0]?.valor);
const tipoSeleccionado = computed(() =>
    props.tipos.find((opcion) => opcion.valor === tipo.value),
);

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="empleado_id">Empleado</Label>
            <select
                id="empleado_id"
                name="empleado_id"
                :class="claseSelect"
                :value="novedad?.empleado_id ?? ''"
                required
            >
                <option value="" disabled>Seleccione un empleado</option>
                <option
                    v-for="empleado in empleados"
                    :key="empleado.id"
                    :value="empleado.id"
                >
                    {{ empleado.apellidos }}, {{ empleado.nombres }}
                </option>
            </select>
            <InputError :message="errors.empleado_id" />
        </div>
        <div class="grid gap-2">
            <Label for="tipo">Tipo de novedad</Label>
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
        <div class="grid gap-2">
            <Label for="fecha_inicio">{{
                tipoSeleccionado?.es_por_dias ? 'Desde' : 'Fecha'
            }}</Label>
            <Input
                id="fecha_inicio"
                name="fecha_inicio"
                type="date"
                :default-value="novedad?.fecha_inicio"
                required
            />
            <InputError :message="errors.fecha_inicio" />
        </div>
        <div v-if="tipoSeleccionado?.es_por_dias" class="grid gap-2">
            <Label for="fecha_fin">Hasta</Label>
            <Input
                id="fecha_fin"
                name="fecha_fin"
                type="date"
                :default-value="novedad?.fecha_fin ?? ''"
            />
            <InputError :message="errors.fecha_fin" />
        </div>
        <div v-if="tipoSeleccionado?.unidad" class="grid gap-2">
            <Label for="cantidad"
                >Cantidad de {{ tipoSeleccionado.unidad }}</Label
            >
            <Input
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
            <div class="grid gap-2">
                <Label for="concepto_nomina_id">Concepto</Label>
                <select
                    id="concepto_nomina_id"
                    name="concepto_nomina_id"
                    :class="claseSelect"
                    :value="novedad?.concepto_nomina_id ?? ''"
                >
                    <option value="" disabled>Seleccione un concepto</option>
                    <option
                        v-for="concepto in conceptos"
                        :key="concepto.id"
                        :value="concepto.id"
                    >
                        {{ concepto.nombre }}
                    </option>
                </select>
                <InputError :message="errors.concepto_nomina_id" />
            </div>
            <div class="grid gap-2">
                <Label for="valor_eventual">Valor</Label>
                <Input
                    id="valor_eventual"
                    name="valor_eventual"
                    type="number"
                    min="0"
                    step="0.01"
                    :default-value="novedad?.valor_eventual ?? ''"
                />
                <InputError :message="errors.valor_eventual" />
            </div>
        </template>
        <div class="grid gap-2 sm:col-span-2">
            <Label for="observacion">Observación (opcional)</Label>
            <Input
                id="observacion"
                name="observacion"
                :default-value="novedad?.observacion ?? ''"
            />
            <InputError :message="errors.observacion" />
        </div>
    </div>
</template>
