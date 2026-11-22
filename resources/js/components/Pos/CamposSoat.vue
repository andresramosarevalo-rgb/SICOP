<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import InputDinero from '@/components/InputDinero.vue';
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

const claseSelect = 'campo-sicop';

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <div
        class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
    >
        <h2
            class="col-span-full border-b border-line-faint pb-3 font-heading text-base leading-[normal] font-bold"
        >
            Identificación del cliente
        </h2>
        <div class="flex flex-col gap-2">
            <Label for="tipo_documento" :class="claseEtiqueta"
                >Tipo de documento</Label
            >
            <select
                id="tipo_documento"
                name="tipo_documento"
                :class="claseSelect"
                required
            >
                <option
                    v-for="tipo in tiposDocumento"
                    :key="tipo.valor"
                    :value="tipo.valor"
                    :selected="
                        tipo.valor ===
                        (soat?.tipo_documento ?? tiposDocumento[0]?.valor)
                    "
                >
                    {{ tipo.etiqueta }}
                </option>
            </select>
            <InputError :message="errors.tipo_documento" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="numero_documento" :class="claseEtiqueta"
                >Número de documento</Label
            >
            <Input
                class="campo-sicop"
                id="numero_documento"
                name="numero_documento"
                :default-value="soat?.numero_documento"
                required
            />
            <InputError :message="errors.numero_documento" />
        </div>
        <div class="col-span-full flex flex-col gap-2">
            <Label for="nombre_cliente" :class="claseEtiqueta"
                >Nombre del cliente</Label
            >
            <Input
                class="campo-sicop"
                id="nombre_cliente"
                name="nombre_cliente"
                :default-value="soat?.nombre_cliente"
                required
            />
            <InputError :message="errors.nombre_cliente" />
        </div>
        <h2
            class="col-span-full mt-2 border-b border-line-faint pb-3 font-heading text-base leading-[normal] font-bold"
        >
            SOAT y datos de contacto
        </h2>
        <div class="flex flex-col gap-2">
            <Label for="fecha" :class="claseEtiqueta">Fecha</Label>
            <Input
                class="campo-sicop"
                id="fecha"
                name="fecha"
                type="date"
                :max="hoy"
                :default-value="soat?.fecha ?? hoy"
                required
            />
            <InputError :message="errors.fecha" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="valor_soat" :class="claseEtiqueta"
                >Valor del SOAT</Label
            >
            <InputDinero
                class="campo-sicop"
                id="valor_soat"
                name="valor_soat"
                :default-value="soat?.valor_soat"
                required
            />
            <InputError :message="errors.valor_soat" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="celular" :class="claseEtiqueta">Celular</Label>
            <Input
                class="campo-sicop"
                id="celular"
                name="celular"
                type="tel"
                :default-value="soat?.celular"
                required
            />
            <InputError :message="errors.celular" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="correo" :class="claseEtiqueta"
                >Correo electrónico</Label
            >
            <Input
                class="campo-sicop"
                id="correo"
                name="correo"
                type="email"
                :default-value="soat?.correo"
                required
            />
            <InputError :message="errors.correo" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="placa" :class="claseEtiqueta">Placa del vehículo</Label>
            <Input
                id="placa"
                name="placa"
                class="campo-sicop uppercase"
                maxlength="10"
                :default-value="soat?.placa"
                required
            />
            <InputError :message="errors.placa" />
        </div>
        <fieldset
            class="col-span-full mt-2 min-w-0 rounded-2xl border border-line-cream bg-cream/40 p-5"
        >
            <legend class="px-2 font-heading text-sm font-bold">
                Información de pago
            </legend>
            <div
                class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
            >
                <CamposPago
                    :errors="errors"
                    :formas-pago="formasPago"
                    :forma-pago="soat?.forma_pago"
                    :valor-efectivo="soat?.valor_efectivo"
                    :valor-qr="soat?.valor_qr"
                />
            </div>
        </fieldset>
    </div>
</template>
