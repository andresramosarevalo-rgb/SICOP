<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { UsuarioAdministrativo } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Usuarios', href: admin.usuarios.index() },
            { title: 'Editar usuario', href: '' },
        ],
    },
});

const props = defineProps<{
    usuario: UsuarioAdministrativo;
}>();

const etiquetasRol: Record<UsuarioAdministrativo['rol'], string> = {
    super_admin: 'Super administrador',
    administrador: 'Administrador',
    auxiliar_nomina: 'Auxiliar de nómina',
    contador: 'Contador',
    cajero: 'Cajero',
};
</script>

<template>
    <Head title="Editar usuario" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="admin.usuarios.index()" />

        <Heading
            title="Editar usuario"
            description="Cambie el nombre o el correo y, si hace falta, restablezca la contraseña. Para impedir que entre al sistema, deshabilítelo desde el listado."
        />

        <dl class="grid gap-4 rounded-xl border p-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-muted-foreground">Rol</dt>
                <dd class="font-medium">
                    {{ etiquetasRol[props.usuario.rol] }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Sede</dt>
                <dd class="font-medium">
                    {{ props.usuario.sede?.nombre ?? '—' }}
                </dd>
            </div>
        </dl>

        <Form
            v-bind="UsuarioController.update.form(props.usuario.id)"
            :reset-on-success="['password', 'password_confirmation']"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nombre completo</Label>
                <Input
                    id="name"
                    name="name"
                    type="text"
                    required
                    autocomplete="name"
                    :default-value="props.usuario.name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="off"
                    :default-value="props.usuario.email"
                />
                <p class="text-sm text-muted-foreground">
                    Si lo cambia, el usuario recibirá un correo para verificarlo
                    de nuevo.
                </p>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Nueva contraseña</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="Déjela vacía para conservar la actual"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">
                    Confirmar nueva contraseña
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Repita la nueva contraseña"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Button type="submit" :disabled="processing">
                    {{ processing ? 'Guardando...' : 'Guardar cambios' }}
                </Button>
                <Button type="button" variant="outline" as-child>
                    <Link :href="admin.usuarios.index()"> Cancelar </Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
