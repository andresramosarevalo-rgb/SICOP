<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { OpcionRol } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usuarios',
                href: admin.usuarios.index(),
            },
            {
                title: 'Registrar usuario',
                href: admin.usuarios.create(),
            },
        ],
    },
});

defineProps<{
    roles: OpcionRol[];
    sedes: { id: number; nombre: string }[];
}>();

const rolSeleccionado = ref('');
</script>

<template>
    <Head title="Registrar usuario" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="admin.usuarios.index()" />

        <Heading
            title="Registrar usuario"
            description="Registre un usuario y asigne el rol correspondiente."
        />

        <Form
            v-bind="UsuarioController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <!-- Nombre completo -->
            <div class="grid gap-2">
                <Label for="name">Nombre completo</Label>

                <Input
                    id="name"
                    name="name"
                    type="text"
                    required
                    autocomplete="name"
                    placeholder="Nombre completo del usuario"
                />

                <InputError :message="errors.name" />
            </div>

            <!-- Correo electrónico -->
            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>

                <Input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                />

                <InputError :message="errors.email" />
            </div>

            <!-- Rol -->
            <div class="grid gap-2">
                <Label for="rol">Rol del usuario</Label>

                <select
                    id="rol"
                    name="rol"
                    required
                    v-model="rolSeleccionado"
                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <option value="" disabled>
                        Seleccione un rol
                    </option>

                    <option
                        v-for="rol in roles"
                        :key="rol.valor"
                        :value="rol.valor"
                    >
                        {{ rol.etiqueta }}
                    </option>
                </select>

                <InputError :message="errors.rol" />
            </div>

            <div v-if="rolSeleccionado === 'cajero'" class="grid gap-2">
                <Label for="sede_id">Sede del cajero</Label>
                <select
                    id="sede_id"
                    name="sede_id"
                    required
                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <option value="" disabled selected>Seleccione una sede</option>
                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                        {{ sede.nombre }}
                    </option>
                </select>
                <InputError :message="errors.sede_id" />
            </div>

            <!-- Contraseña -->
            <div class="grid gap-2">
                <Label for="password">Contraseña</Label>

                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Ingrese una contraseña"
                />

                <InputError :message="errors.password" />
            </div>

            <!-- Confirmación de contraseña -->
            <div class="grid gap-2">
                <Label for="password_confirmation">
                    Confirmar contraseña
                </Label>

                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repita la contraseña"
                />

                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Acciones del formulario -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <Button
                    type="submit"
                    variant="default"
                    :disabled="processing"
                    data-test="registrar-usuario-button"
                >
                    {{ processing ? 'Registrando...' : 'Registrar usuario' }}
                </Button>

                <Button type="button" variant="outline" as-child>
                    <Link :href="admin.usuarios.index()"> Cancelar </Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
