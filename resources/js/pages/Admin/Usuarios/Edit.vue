<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
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

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';

const etiquetasRol: Record<UsuarioAdministrativo['rol'], string> = {
    super_admin: 'Super administrador',
    administrador: 'Administrador',
    auxiliar_nomina: 'Auxiliar de nómina',
    cajero: 'Cajero',
};
</script>

<template>
    <Head title="Editar usuario" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Editar usuario
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Cambie el nombre o el correo y, si hace falta, restablezca la
                contraseña. Para impedir que entre al sistema, deshabilítelo
                desde el listado.
            </p>
        </header>

        <FormularioNomina
            :accion="UsuarioController.update.form(props.usuario.id)"
            :reset-on-success="['password', 'password_confirmation']"
            etiqueta-guardar="Guardar cambios"
            :href-cancelar="admin.usuarios.index()"
        >
            <template #default="{ errors }">
                <dl
                    class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-5 border-b border-line-faint pb-5"
                >
                    <div>
                        <dt
                            class="text-sm leading-[normal] text-muted-foreground"
                        >
                            Rol
                        </dt>
                        <dd class="mt-1 font-semibold">
                            {{ etiquetasRol[props.usuario.rol] }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-sm leading-[normal] text-muted-foreground"
                        >
                            Sede
                        </dt>
                        <dd class="mt-1 font-semibold">
                            {{ props.usuario.sede?.nombre ?? '—' }}
                        </dd>
                    </div>
                </dl>

                <div
                    class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
                >
                    <div class="flex flex-col gap-2">
                        <Label for="name" :class="claseEtiqueta"
                            >Nombre completo</Label
                        >
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            class="campo-sicop"
                            required
                            autocomplete="name"
                            :default-value="props.usuario.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="email" :class="claseEtiqueta"
                            >Correo electrónico</Label
                        >
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            class="campo-sicop"
                            required
                            autocomplete="off"
                            :default-value="props.usuario.email"
                        />
                        <p class="text-sm text-muted-foreground">
                            Si lo cambia, el usuario recibirá un correo para
                            verificarlo de nuevo.
                        </p>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="password" :class="claseEtiqueta"
                            >Nueva contraseña</Label
                        >
                        <PasswordInput
                            id="password"
                            name="password"
                            class="campo-sicop [--relleno-campo-der:44px]"
                            autocomplete="new-password"
                            placeholder="Déjela vacía para conservar la actual"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label
                            for="password_confirmation"
                            :class="claseEtiqueta"
                            >Confirmar nueva contraseña</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="campo-sicop [--relleno-campo-der:44px]"
                            autocomplete="new-password"
                            placeholder="Repita la nueva contraseña"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
