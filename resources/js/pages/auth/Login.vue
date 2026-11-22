<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, Lock, Mail } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Iniciar sesión',
        description: 'Ingresa con tu correo y contraseña',
    },
});

defineProps<{
    status?: string;
}>();

const mostrarContrasena = ref(false);

const contenedorCampo =
    'fld flex h-[52px] items-center gap-3 rounded-[14px] border border-[#8798ad] bg-card px-4';
const campoTransparente =
    'h-full min-w-0 flex-1 rounded-none border-0 bg-transparent px-0 text-base shadow-none focus-visible:ring-0 dark:bg-transparent';
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        v-if="status"
        role="status"
        class="-mt-2 text-center text-sm font-medium text-success"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-[22px]"
    >
        <div class="flex flex-col gap-2">
            <Label for="email" class="text-sm leading-[normal] font-semibold"
                >Correo electrónico</Label
            >
            <div :class="contenedorCampo">
                <Mail
                    class="size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                    :class="campoTransparente"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <div class="flex flex-col gap-2">
            <Label for="password" class="text-sm leading-[normal] font-semibold"
                >Contraseña</Label
            >
            <div :class="`${contenedorCampo} pr-2`">
                <Lock
                    class="size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="password"
                    :type="mostrarContrasena ? 'text' : 'password'"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Contraseña"
                    :class="campoTransparente"
                />
                <button
                    type="button"
                    class="ojo flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-[10px] text-muted-foreground"
                    :aria-label="
                        mostrarContrasena
                            ? 'Ocultar contraseña'
                            : 'Mostrar contraseña'
                    "
                    :tabindex="-1"
                    @click="mostrarContrasena = !mostrarContrasena"
                >
                    <EyeOff
                        v-if="mostrarContrasena"
                        class="size-5"
                        :stroke-width="1.8"
                        aria-hidden="true"
                    />
                    <Eye
                        v-else
                        class="size-5"
                        :stroke-width="1.8"
                        aria-hidden="true"
                    />
                </button>
            </div>
            <InputError :message="errors.password" />
        </div>

        <Label
            for="remember"
            class="flex min-h-11 cursor-pointer items-center gap-3 text-[15px] font-normal"
        >
            <Checkbox
                id="remember"
                name="remember"
                :tabindex="3"
                class="size-5"
            />
            <span>Recordar mi sesión</span>
        </Label>

        <Button
            type="submit"
            class="boton-acceso h-[54px] w-full gap-2.5 rounded-[14px] text-base font-bold"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            Iniciar sesión
            <span v-if="!processing" class="go inline-flex text-cta-mark"
                ><ArrowRight
                    class="size-[18px]"
                    :stroke-width="2.2"
                    aria-hidden="true"
            /></span>
        </Button>
    </Form>
</template>
