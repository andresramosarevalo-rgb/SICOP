<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Curso, OpcionTipoDocumento } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    tiposDocumento: OpcionTipoDocumento[];
    porcentajesDescuento: number[];
    curso?: Curso;
}>();

const hoy = new Date().toLocaleDateString('en-CA');

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="tipo_documento">Tipo de documento</Label>
            <select
                id="tipo_documento"
                name="tipo_documento"
                :class="claseSelect"
                :value="curso?.tipo_documento ?? tiposDocumento[0]?.valor"
                required
            >
                <option
                    v-for="tipo in tiposDocumento"
                    :key="tipo.valor"
                    :value="tipo.valor"
                >
                    {{ tipo.etiqueta }}
                </option>
            </select>
            <InputError :message="errors.tipo_documento" />
        </div>
        <div class="grid gap-2">
            <Label for="numero_documento">Número de documento</Label>
            <Input
                id="numero_documento"
                name="numero_documento"
                :default-value="curso?.numero_documento"
                required
            />
            <InputError :message="errors.numero_documento" />
        </div>
        <div class="grid gap-2 sm:col-span-2">
            <Label for="nombre_cliente">Nombre del cliente</Label>
            <Input
                id="nombre_cliente"
                name="nombre_cliente"
                :default-value="curso?.nombre_cliente"
                required
            />
            <InputError :message="errors.nombre_cliente" />
        </div>
        <div class="grid gap-2">
            <Label for="fecha">Fecha</Label>
            <Input
                id="fecha"
                name="fecha"
                type="date"
                :max="hoy"
                :default-value="curso?.fecha ?? hoy"
                required
            />
            <InputError :message="errors.fecha" />
        </div>
        <div class="grid gap-2">
            <Label for="tipo_comparendo">Tipo de comparendo</Label>
            <Input
                id="tipo_comparendo"
                name="tipo_comparendo"
                :default-value="curso?.tipo_comparendo"
                required
            />
            <InputError :message="errors.tipo_comparendo" />
        </div>
        <div class="grid gap-2">
            <Label for="valor_curso">Valor del curso</Label>
            <Input
                id="valor_curso"
                name="valor_curso"
                type="number"
                min="0"
                step="0.01"
                :default-value="curso?.valor_curso"
                required
            />
            <InputError :message="errors.valor_curso" />
        </div>
        <div class="grid gap-2">
            <Label for="porcentaje_descuento">Descuento obtenido</Label>
            <select
                id="porcentaje_descuento"
                name="porcentaje_descuento"
                :class="claseSelect"
                :value="
                    curso
                        ? Number(curso.porcentaje_descuento)
                        : porcentajesDescuento[0]
                "
                required
            >
                <option
                    v-for="porcentaje in porcentajesDescuento"
                    :key="porcentaje"
                    :value="porcentaje"
                >
                    {{ porcentaje }} %
                </option>
            </select>
            <InputError :message="errors.porcentaje_descuento" />
        </div>
    </div>
</template>
