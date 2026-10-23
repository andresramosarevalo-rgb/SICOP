<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Sede } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    sede?: Sede;
}>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="nombre">Nombre</Label>
            <Input
                id="nombre"
                name="nombre"
                :default-value="sede?.nombre"
                placeholder="Sede Villavicencio"
                required
            />
            <InputError :message="errors.nombre" />
        </div>
        <div class="grid gap-2">
            <Label for="ciudad">Ciudad</Label>
            <Input
                id="ciudad"
                name="ciudad"
                :default-value="sede?.ciudad"
                required
            />
            <InputError :message="errors.ciudad" />
        </div>
        <div class="grid gap-2 sm:col-span-2">
            <Label for="direccion">Dirección</Label>
            <Input
                id="direccion"
                name="direccion"
                :default-value="sede?.direccion"
                required
            />
            <InputError :message="errors.direccion" />
        </div>
        <label v-if="sede" class="flex items-center gap-2 text-sm">
            <input type="hidden" name="es_activa" value="0" />
            <input
                type="checkbox"
                name="es_activa"
                value="1"
                :checked="sede.es_activa"
            />
            Sede activa
        </label>
    </div>
</template>
