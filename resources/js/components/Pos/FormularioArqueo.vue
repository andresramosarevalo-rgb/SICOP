<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import CierreCajaController from '@/actions/App/Http/Controllers/Pos/CierreCajaController';
import { Button } from '@/components/ui/button';

defineProps<{
    sedeId: number;
    fecha: string;
}>();
</script>

<template>
    <Form
        v-bind="CierreCajaController.store.form(sedeId)"
        class="flex flex-wrap items-center gap-4"
        v-slot="{ processing }"
    >
        <input type="hidden" name="fecha" :value="fecha" />
        <Button :disabled="processing">Cerrar día</Button>
        <p class="text-sm text-muted-foreground">
            Congela las cifras del día para consignar el efectivo.
        </p>
    </Form>
</template>
