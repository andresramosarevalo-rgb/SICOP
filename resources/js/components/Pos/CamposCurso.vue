<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CamposPago from '@/components/Pos/CamposPago.vue';
import type { Curso, Opcion, OpcionTipoDocumento } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    tiposDocumento: OpcionTipoDocumento[];
    formasPago: Opcion[];
    referidos: Opcion[];
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
        <div class="grid gap-2">
            <Label for="celular">Celular</Label>
            <Input
                id="celular"
                name="celular"
                type="tel"
                :default-value="curso?.celular"
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
                :default-value="curso?.correo"
                required
            />
            <InputError :message="errors.correo" />
        </div>
        <div class="grid gap-2">
            <Label for="numero_certificado">Número de certificado</Label>
            <Input
                id="numero_certificado"
                name="numero_certificado"
                :default-value="curso?.numero_certificado"
                required
            />
            <InputError :message="errors.numero_certificado" />
        </div>
        <div class="grid gap-2">
            <Label for="numero_comparendo">Número de comparendo</Label>
            <Input
                id="numero_comparendo"
                name="numero_comparendo"
                :default-value="curso?.numero_comparendo"
                required
            />
            <InputError :message="errors.numero_comparendo" />
        </div>
        <div class="grid gap-2">
            <Label for="secretaria_transito">Secretaría de tránsito</Label>
            <Input
                id="secretaria_transito"
                name="secretaria_transito"
                :default-value="curso?.secretaria_transito"
                required
            />
            <InputError :message="errors.secretaria_transito" />
        </div>
        <div class="grid gap-2">
            <Label for="hora_inicio">Hora de inicio</Label>
            <Input
                id="hora_inicio"
                name="hora_inicio"
                type="time"
                :default-value="curso?.hora_inicio?.slice(0, 5)"
                required
            />
            <InputError :message="errors.hora_inicio" />
        </div>
        <div class="grid gap-2">
            <Label for="hora_fin">Hora de fin</Label>
            <Input
                id="hora_fin"
                name="hora_fin"
                type="time"
                :default-value="curso?.hora_fin?.slice(0, 5)"
                required
            />
            <InputError :message="errors.hora_fin" />
        </div>
        <div class="grid gap-2">
            <Label for="referido">Referido</Label>
            <select
                id="referido"
                name="referido"
                :class="claseSelect"
                :value="curso?.referido ?? ''"
            >
                <option value="">Sin referido</option>
                <option
                    v-for="referido in referidos"
                    :key="referido.valor"
                    :value="referido.valor"
                >
                    {{ referido.etiqueta }}
                </option>
            </select>
            <InputError :message="errors.referido" />
        </div>
        <CamposPago
            :errors="errors"
            :formas-pago="formasPago"
            :forma-pago="curso?.forma_pago"
            :valor-efectivo="curso?.valor_efectivo"
            :valor-datafono="curso?.valor_datafono"
            :valor-qr="curso?.valor_qr"
        />
    </div>
</template>
