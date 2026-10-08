<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Area, Empleado, OpcionTipoDocumento } from '@/types';

defineProps<{
    areas: Pick<Area, 'id' | 'nombre'>[];
    tiposDocumento: OpcionTipoDocumento[];
    errors: Record<string, string | undefined>;
    empleado?: Empleado;
}>();

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';

const camposTexto = [
    {
        nombre: 'numero_documento',
        etiqueta: 'Número de documento',
        requerido: true,
    },
    { nombre: 'nombres', etiqueta: 'Nombres', requerido: true },
    { nombre: 'apellidos', etiqueta: 'Apellidos', requerido: true },
    {
        nombre: 'email',
        etiqueta: 'Correo electrónico',
        requerido: true,
        tipo: 'email',
    },
    { nombre: 'telefono', etiqueta: 'Teléfono', requerido: false, tipo: 'tel' },
    { nombre: 'direccion', etiqueta: 'Dirección', requerido: false },
    {
        nombre: 'fecha_nacimiento',
        etiqueta: 'Fecha de nacimiento',
        requerido: false,
        tipo: 'date',
    },
    { nombre: 'cargo', etiqueta: 'Cargo', requerido: true },
] as const;
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="tipo_documento">Tipo de documento</Label>
            <select
                id="tipo_documento"
                name="tipo_documento"
                :class="claseSelect"
                :value="empleado?.tipo_documento ?? tiposDocumento[0]?.valor"
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

        <div
            v-for="campo in camposTexto"
            :key="campo.nombre"
            class="grid gap-2"
        >
            <Label :for="campo.nombre">{{ campo.etiqueta }}</Label>
            <Input
                :id="campo.nombre"
                :name="campo.nombre"
                :type="'tipo' in campo ? campo.tipo : 'text'"
                :default-value="empleado?.[campo.nombre] ?? ''"
                :required="campo.requerido"
            />
            <InputError :message="errors[campo.nombre]" />
        </div>

        <div class="grid gap-2">
            <Label for="area_id">Área</Label>
            <select
                id="area_id"
                name="area_id"
                :class="claseSelect"
                :value="empleado?.area_id"
                required
            >
                <option value="" disabled>Seleccione un área</option>
                <option v-for="area in areas" :key="area.id" :value="area.id">
                    {{ area.nombre }}
                </option>
            </select>
            <InputError :message="errors.area_id" />
        </div>
    </div>
</template>
