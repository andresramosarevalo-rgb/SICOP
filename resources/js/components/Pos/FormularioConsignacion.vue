<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ConsignacionController from '@/actions/App/Http/Controllers/Pos/ConsignacionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
        class="grid gap-4 sm:grid-cols-4"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="fecha_consignacion">Fecha de la consignación</Label>
            <Input
                id="fecha_consignacion"
                name="fecha_consignacion"
                type="date"
                :max="hoy"
                :default-value="hoy"
                required
            />
            <InputError :message="errors.fecha_consignacion" />
        </div>
        <div class="grid gap-2">
            <Label for="valor_consignado">Valor consignado</Label>
            <Input
                id="valor_consignado"
                name="valor_consignado"
                type="number"
                min="0"
                step="0.01"
                :default-value="Number(valorAConsignar)"
                required
            />
            <InputError :message="errors.valor_consignado" />
        </div>
        <div class="grid gap-2">
            <Label for="referencia">Referencia del banco</Label>
            <Input id="referencia" name="referencia" required />
            <InputError :message="errors.referencia" />
        </div>
        <div class="grid gap-2">
            <Label for="soporte">Comprobante (JPG, PNG o PDF)</Label>
            <Input
                id="soporte"
                name="soporte"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                required
            />
            <InputError :message="errors.soporte" />
        </div>
        <div class="sm:col-span-4">
            <Button :disabled="processing">Registrar consignación</Button>
        </div>
    </Form>
</template>
