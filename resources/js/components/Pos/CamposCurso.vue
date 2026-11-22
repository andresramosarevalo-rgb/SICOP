<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CamposPago from '@/components/Pos/CamposPago.vue';
import type { Curso, Opcion, OpcionTipoDocumento } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    tiposDocumento: OpcionTipoDocumento[];
    codigosInfraccion: Opcion[];
    formasPago: Opcion[];
    referidos: Opcion[];
    porcentajesDescuento: number[];
    curso?: Curso;
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
                        (curso?.tipo_documento ?? tiposDocumento[0]?.valor)
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
                :default-value="curso?.numero_documento"
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
                :default-value="curso?.nombre_cliente"
                required
            />
            <InputError :message="errors.nombre_cliente" />
        </div>
        <h2
            class="col-span-full mt-2 border-b border-line-faint pb-3 font-heading text-base leading-[normal] font-bold"
        >
            Curso y datos de contacto
        </h2>
        <div class="flex flex-col gap-2">
            <Label for="fecha" :class="claseEtiqueta">Fecha</Label>
            <Input
                class="campo-sicop"
                id="fecha"
                name="fecha"
                type="date"
                :max="hoy"
                :default-value="curso?.fecha ?? hoy"
                required
            />
            <InputError :message="errors.fecha" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="codigo_infraccion" :class="claseEtiqueta"
                >Tipo de comparendo</Label
            >
            <select
                id="codigo_infraccion"
                name="codigo_infraccion"
                :class="claseSelect"
                required
            >
                <option value="" disabled :selected="!curso?.codigo_infraccion">
                    Seleccione el tipo
                </option>
                <option
                    v-for="codigo in codigosInfraccion"
                    :key="codigo.valor"
                    :value="codigo.valor"
                    :selected="codigo.valor === curso?.codigo_infraccion"
                >
                    {{ codigo.etiqueta }}
                </option>
            </select>
            <InputError :message="errors.codigo_infraccion" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="valor_curso" :class="claseEtiqueta"
                >Valor del curso</Label
            >
            <InputDinero
                class="campo-sicop"
                id="valor_curso"
                name="valor_curso"
                :default-value="curso?.valor_curso"
                required
            />
            <InputError :message="errors.valor_curso" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="porcentaje_descuento" :class="claseEtiqueta"
                >Descuento obtenido</Label
            >
            <select
                id="porcentaje_descuento"
                name="porcentaje_descuento"
                :class="claseSelect"
                required
            >
                <option
                    v-for="porcentaje in porcentajesDescuento"
                    :key="porcentaje"
                    :value="porcentaje"
                    :selected="
                        porcentaje ===
                        (curso
                            ? Number(curso.porcentaje_descuento)
                            : porcentajesDescuento[0])
                    "
                >
                    {{ porcentaje }} %
                </option>
            </select>
            <InputError :message="errors.porcentaje_descuento" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="celular" :class="claseEtiqueta">Celular</Label>
            <Input
                class="campo-sicop"
                id="celular"
                name="celular"
                type="tel"
                :default-value="curso?.celular"
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
                :default-value="curso?.correo"
                required
            />
            <InputError :message="errors.correo" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="numero_certificado" :class="claseEtiqueta"
                >Número de certificado</Label
            >
            <Input
                class="campo-sicop"
                id="numero_certificado"
                name="numero_certificado"
                :default-value="curso?.numero_certificado"
                required
            />
            <InputError :message="errors.numero_certificado" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="numero_comparendo" :class="claseEtiqueta"
                >Número de comparendo</Label
            >
            <Input
                class="campo-sicop"
                id="numero_comparendo"
                name="numero_comparendo"
                :default-value="curso?.numero_comparendo"
                required
            />
            <InputError :message="errors.numero_comparendo" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="secretaria_transito" :class="claseEtiqueta"
                >Secretaría de tránsito</Label
            >
            <Input
                class="campo-sicop"
                id="secretaria_transito"
                name="secretaria_transito"
                :default-value="curso?.secretaria_transito"
                required
            />
            <InputError :message="errors.secretaria_transito" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="hora_inicio" :class="claseEtiqueta"
                >Hora de inicio</Label
            >
            <Input
                class="campo-sicop"
                id="hora_inicio"
                name="hora_inicio"
                type="time"
                :default-value="curso?.hora_inicio?.slice(0, 5)"
                required
            />
            <InputError :message="errors.hora_inicio" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="hora_fin" :class="claseEtiqueta">Hora de fin</Label>
            <Input
                class="campo-sicop"
                id="hora_fin"
                name="hora_fin"
                type="time"
                :default-value="curso?.hora_fin?.slice(0, 5)"
                required
            />
            <InputError :message="errors.hora_fin" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="referido" :class="claseEtiqueta">Referido</Label>
            <select id="referido" name="referido" :class="claseSelect">
                <option value="" :selected="!curso?.referido">
                    Sin referido
                </option>
                <option
                    v-for="referido in referidos"
                    :key="referido.valor"
                    :value="referido.valor"
                    :selected="referido.valor === curso?.referido"
                >
                    {{ referido.etiqueta }}
                </option>
            </select>
            <InputError :message="errors.referido" />
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
                    :forma-pago="curso?.forma_pago"
                    :valor-efectivo="curso?.valor_efectivo"
                    :valor-datafono="curso?.valor_datafono"
                    :valor-qr="curso?.valor_qr"
                />
            </div>
        </fieldset>
    </div>
</template>
