<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Area, Empleado, OpcionTipoDocumento } from '@/types';

const props = defineProps<{
    areas: Pick<Area, 'id' | 'nombre'>[];
    tiposDocumento: OpcionTipoDocumento[];
    errors: Record<string, string | undefined>;
    empleado?: Empleado;
}>();

// Los selectores conservan la elección al recibir errores del servidor.
const areaSeleccionada = ref(props.empleado?.area_id ?? '');
const tipoDocumentoSeleccionado = ref(
    props.empleado?.tipo_documento ?? props.tiposDocumento[0]?.valor ?? '',
);

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
        <div class="flex min-w-0 flex-col gap-2">
            <Label for="tipo_documento">Tipo de documento</Label>
            <select
                id="tipo_documento"
                name="tipo_documento"
                :aria-invalid="!!errors.tipo_documento"
                :aria-describedby="
                    errors.tipo_documento ? 'tipo_documento_error' : undefined
                "
                :class="claseSelect"
                v-model="tipoDocumentoSeleccionado"
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
            <InputError
                reservar-espacio
                id="tipo_documento_error"
                :message="errors.tipo_documento"
            />
        </div>

        <div
            v-for="campo in camposTexto"
            :key="campo.nombre"
            class="flex min-w-0 flex-col gap-2"
        >
            <Label :for="campo.nombre">{{ campo.etiqueta }}</Label>
            <Input
                :id="campo.nombre"
                :name="campo.nombre"
                :aria-invalid="!!errors[campo.nombre]"
                :aria-describedby="
                    errors[campo.nombre] ? `${campo.nombre}_error` : undefined
                "
                :type="'tipo' in campo ? campo.tipo : 'text'"
                :default-value="empleado?.[campo.nombre] ?? ''"
                :required="campo.requerido"
            />
            <InputError
                reservar-espacio
                :id="`${campo.nombre}_error`"
                :message="errors[campo.nombre]"
            />
        </div>

        <div class="flex min-w-0 flex-col gap-2">
            <Label for="area_id">Área</Label>
            <select
                id="area_id"
                name="area_id"
                :aria-invalid="!!errors.area_id"
                :aria-describedby="errors.area_id ? 'area_id_error' : undefined"
                :class="claseSelect"
                v-model="areaSeleccionada"
                required
            >
                <option value="" disabled>Seleccione un área</option>
                <option v-for="area in areas" :key="area.id" :value="area.id">
                    {{ area.nombre }}
                </option>
            </select>
            <InputError
                reservar-espacio
                id="area_id_error"
                :message="errors.area_id"
            />
        </div>
    </div>
</template>
