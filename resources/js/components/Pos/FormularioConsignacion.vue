<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ConsignacionController from '@/actions/App/Http/Controllers/Pos/ConsignacionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    cierreId: number;
    valorAConsignar: string;
}>();

const hoy = new Date().toLocaleDateString('en-CA');
</script>

<template>
    <Form
        v-bind="ConsignacionController.store.form(cierreId)"
        class="flex flex-col gap-[18px]"
        v-slot="{ errors, processing }"
    >
        <div
            class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-5"
        >
            <div class="flex flex-col gap-2">
                <Label
                    for="fecha_consignacion"
                    class="text-sm leading-[normal] font-semibold"
                    >Fecha de la consignación</Label
                >
                <Input
                    id="fecha_consignacion"
                    name="fecha_consignacion"
                    type="date"
                    class="campo-sicop"
                    :max="hoy"
                    :default-value="hoy"
                    required
                />
                <InputError :message="errors.fecha_consignacion" />
            </div>
            <div class="flex flex-col gap-2">
                <Label
                    for="valor_consignado"
                    class="text-sm leading-[normal] font-semibold"
                    >Valor consignado</Label
                >
                <Input
                    id="valor_consignado"
                    name="valor_consignado"
                    type="number"
                    min="0"
                    step="0.01"
                    class="campo-sicop"
                    :default-value="Number(valorAConsignar)"
                    required
                />
                <InputError :message="errors.valor_consignado" />
            </div>
            <div class="flex flex-col gap-2">
                <Label
                    for="referencia"
                    class="text-sm leading-[normal] font-semibold"
                    >Referencia del banco</Label
                >
                <Input
                    id="referencia"
                    name="referencia"
                    class="campo-sicop"
                    placeholder="Número de referencia"
                    required
                />
                <InputError :message="errors.referencia" />
            </div>
            <div class="flex flex-col gap-2">
                <Label
                    for="soporte"
                    class="text-sm leading-[normal] font-semibold"
                    >Comprobante (JPG, PNG o PDF)</Label
                >
                <Input
                    id="soporte"
                    name="soporte"
                    type="file"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="campo-sicop pt-3"
                    required
                />
                <InputError :message="errors.soporte" />
            </div>
        </div>
        <div class="flex justify-end">
            <Button
                :disabled="processing"
                class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >Guardar consignación</Button
            >
        </div>
    </Form>
</template>
