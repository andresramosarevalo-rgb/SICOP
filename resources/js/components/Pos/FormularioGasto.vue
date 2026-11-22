<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import GastoCajaController from '@/actions/App/Http/Controllers/Pos/GastoCajaController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import InputDinero from '@/components/InputDinero.vue';
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
        class="grid grid-cols-1 gap-5 rounded-[22px] border bg-card px-7 py-6 sm:grid-cols-2"
        :reset-on-success="true"
        v-slot="{ errors, processing }"
    >
        <h3
            class="col-span-full font-heading text-lg leading-[normal] font-bold"
        >
            Registrar gasto
        </h3>
        <input type="hidden" name="fecha" :value="fecha" />
        <div class="col-span-full flex flex-col gap-2">
            <Label for="concepto" class="text-sm leading-[normal] font-semibold"
                >Concepto</Label
            >
            <Input id="concepto" name="concepto" class="campo-sicop" required />
            <InputError :message="errors.concepto" />
        </div>
        <div class="flex flex-col gap-2">
            <Label
                for="valor_gasto"
                class="text-sm leading-[normal] font-semibold"
                >Valor</Label
            >
            <InputDinero
                id="valor_gasto"
                name="valor_gasto"
                class="campo-sicop"
                placeholder="$ 0"
                required
            />
            <InputError :message="errors.valor_gasto" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="soporte" class="text-sm leading-[normal] font-semibold"
                >Soporte (JPG, PNG o PDF)</Label
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
        <div
            class="col-span-full flex justify-end border-t border-line-faint pt-3"
        >
            <Button
                :disabled="processing"
                class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >Agregar gasto</Button
            >
        </div>
    </Form>
</template>
