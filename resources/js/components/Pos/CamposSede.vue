<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Sede } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    sede?: Sede;
}>();
</script>

<template>
    <div
        class="grid grid-cols-[repeat(auto-fit,minmax(min(260px,100%),1fr))] gap-5"
    >
        <div class="flex flex-col gap-2">
            <Label for="nombre" class="text-sm leading-[normal] font-semibold"
                >Nombre de la sede</Label
            >
            <Input
                id="nombre"
                name="nombre"
                class="campo-sicop"
                :default-value="sede?.nombre"
                placeholder="Ej.: Sede Centro"
                required
            />
            <InputError :message="errors.nombre" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="ciudad" class="text-sm leading-[normal] font-semibold"
                >Ciudad</Label
            >
            <Input
                id="ciudad"
                name="ciudad"
                class="campo-sicop"
                :default-value="sede?.ciudad"
                placeholder="Ej.: Bucaramanga"
                required
            />
            <InputError :message="errors.ciudad" />
        </div>
        <div class="col-span-full flex flex-col gap-2">
            <Label
                for="direccion"
                class="text-sm leading-[normal] font-semibold"
                >Dirección</Label
            >
            <Input
                id="direccion"
                name="direccion"
                class="campo-sicop"
                :default-value="sede?.direccion"
                placeholder="Dirección completa"
                required
            />
            <InputError :message="errors.direccion" />
        </div>
        <div class="flex flex-col gap-2">
            <Label
                for="valor_base_caja"
                class="text-sm leading-[normal] font-semibold"
                >Base de caja</Label
            >
            <InputDinero
                id="valor_base_caja"
                name="valor_base_caja"
                class="campo-sicop"
                :default-value="sede?.valor_base_caja ?? 0"
                required
            />
            <InputError :message="errors.valor_base_caja" />
        </div>
        <label
            for="es_activa"
            class="col-span-full flex min-h-11 cursor-pointer items-center gap-3 text-[15px] font-medium"
        >
            <input type="hidden" name="es_activa" value="0" />
            <input
                id="es_activa"
                type="checkbox"
                name="es_activa"
                value="1"
                class="m-0 size-5 accent-primary"
                :checked="sede?.es_activa ?? true"
            />
            Sede activa (puede registrar cursos y SOAT)
        </label>
    </div>
</template>
