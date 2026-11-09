<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import GastoCajaController from '@/actions/App/Http/Controllers/Pos/GastoCajaController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    sedeId: number;
    fecha: string;
}>();
</script>

<template>
    <Form
        v-bind="GastoCajaController.store.form(sedeId)"
        class="grid gap-5 rounded-xl border bg-card p-5 shadow-sm sm:grid-cols-2 sm:p-6"
        :reset-on-success="true"
        v-slot="{ errors, processing }"
    >
        <h3 class="text-base font-semibold sm:col-span-2">Registrar gasto</h3>
        <input type="hidden" name="fecha" :value="fecha" />
        <div class="grid gap-2 sm:col-span-2">
            <Label for="concepto">Concepto</Label>
            <Input id="concepto" name="concepto" required />
            <InputError :message="errors.concepto" />
        </div>
        <div class="grid gap-2">
            <Label for="valor_gasto">Valor</Label>
            <Input
                id="valor_gasto"
                name="valor_gasto"
                type="number"
                min="0"
                step="0.01"
                required
            />
            <InputError :message="errors.valor_gasto" />
        </div>
        <div class="grid gap-2">
            <Label for="soporte">Soporte (JPG, PNG o PDF)</Label>
            <Input
                id="soporte"
                name="soporte"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                required
            />
            <InputError :message="errors.soporte" />
        </div>
        <div class="flex justify-end border-t pt-4 sm:col-span-2">
            <Button :disabled="processing">Agregar gasto</Button>
        </div>
    </Form>
</template>
