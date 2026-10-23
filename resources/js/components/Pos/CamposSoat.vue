<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CamposPago from '@/components/Pos/CamposPago.vue';
import type { Opcion, OpcionTipoDocumento, Soat } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    tiposDocumento: OpcionTipoDocumento[];
    formasPago: Opcion[];
    soat?: Soat;
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
                :value="soat?.tipo_documento ?? tiposDocumento[0]?.valor"
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
                :default-value="soat?.numero_documento"
                required
            />
            <InputError :message="errors.numero_documento" />
        </div>
        <div class="grid gap-2 sm:col-span-2">
            <Label for="nombre_cliente">Nombre del cliente</Label>
            <Input
                id="nombre_cliente"
                name="nombre_cliente"
                :default-value="soat?.nombre_cliente"
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
                :default-value="soat?.fecha ?? hoy"
                required
            />
            <InputError :message="errors.fecha" />
        </div>
        <div class="grid gap-2">
            <Label for="valor_soat">Valor del SOAT</Label>
            <Input
                id="valor_soat"
                name="valor_soat"
                type="number"
                min="0"
                step="0.01"
                :default-value="soat?.valor_soat"
                required
            />
            <InputError :message="errors.valor_soat" />
        </div>
        <div class="grid gap-2">
            <Label for="celular">Celular</Label>
            <Input
                id="celular"
                name="celular"
                type="tel"
                :default-value="soat?.celular"
                required
            />
            <InputError :message="errors.celular" />
        </div>
        <div class="grid gap-2">
            <Label for="correo">Correo electrónico</Label>
            <Input
                id="correo"
                name="correo"
                type="email"
                :default-value="soat?.correo"
                required
            />
            <InputError :message="errors.correo" />
        </div>
        <div class="grid gap-2">
            <Label for="placa">Placa del vehículo</Label>
            <Input
                id="placa"
                name="placa"
                class="uppercase"
                maxlength="10"
                :default-value="soat?.placa"
                required
            />
            <InputError :message="errors.placa" />
        </div>
        <CamposPago
            :errors="errors"
            :formas-pago="formasPago"
            :forma-pago="soat?.forma_pago"
            :valor-efectivo="soat?.valor_efectivo"
            :valor-qr="soat?.valor_qr"
        />
    </div>
</template>
