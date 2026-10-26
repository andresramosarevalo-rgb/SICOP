<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Sede } from '@/types';

defineProps<{
    errors: Record<string, string | undefined>;
    sede: Sede;
}>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2 sm:col-span-2">
            <Label for="email">Correo de la sede</Label>
            <Input
                id="email"
                name="email"
                type="email"
                autocomplete="off"
                :default-value="sede.usuario?.email"
                required
            />
            <InputError :message="errors.email" />
        </div>
        <div class="grid gap-2">
            <Label for="password">{{
                sede.usuario ? 'Nueva contraseña' : 'Contraseña'
            }}</Label>
            <Input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                :required="!sede.usuario"
            />
            <InputError :message="errors.password" />
        </div>
        <div class="grid gap-2">
            <Label for="password_confirmation">Confirmar contraseña</Label>
            <Input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                :required="!sede.usuario"
            />
        </div>
    </div>
</template>
