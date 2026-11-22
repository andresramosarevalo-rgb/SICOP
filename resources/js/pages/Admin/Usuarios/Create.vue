<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
    puedeElegirSede: boolean;
}>();

const rolSeleccionado = ref('');

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';
</script>

<template>
    <Head title="Registrar usuario" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Registrar usuario
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Registre un usuario y asigne el rol correspondiente.
            </p>
        </header>

        <FormularioNomina
            :accion="UsuarioController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            etiqueta-guardar="Registrar usuario"
            :href-cancelar="admin.usuarios.index()"
        >
            <template #default="{ errors }">
                <div
                    class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
                >
                    <div class="col-span-full flex flex-col gap-2">
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
                            placeholder="Nombre completo del usuario"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="col-span-full flex flex-col gap-2">
                        <Label for="email" :class="claseEtiqueta"
                            >Correo electrónico</Label
                        >
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            class="campo-sicop"
                            required
                            autocomplete="email"
                            placeholder="correo@ejemplo.com"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="rol" :class="claseEtiqueta"
                            >Rol del usuario</Label
                        >
                        <select
                            id="rol"
                            name="rol"
                            required
                            v-model="rolSeleccionado"
                            class="campo-sicop"
                        >
                            <option value="" disabled selected>
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

                    <div
                        v-if="
                            puedeElegirSede && rolSeleccionado !== 'super_admin'
                        "
                        class="flex flex-col gap-2"
                    >
                        <Label for="sede_id" :class="claseEtiqueta"
                            >Sede del usuario</Label
                        >
                        <select
                            id="sede_id"
                            name="sede_id"
                            required
                            class="campo-sicop"
                        >
                            <option value="" disabled selected>
                                Seleccione una sede
                            </option>
                            <option
                                v-for="sede in sedes"
                                :key="sede.id"
                                :value="sede.id"
                            >
                                {{ sede.nombre }}
                            </option>
                        </select>
                        <InputError :message="errors.sede_id" />
                    </div>

                    <p
                        v-else-if="!puedeElegirSede && sedes.length > 0"
                        class="col-span-full text-sm text-muted-foreground"
                    >
                        El usuario se registra en su sede:
                        <span class="font-semibold text-foreground">{{
                            sedes[0].nombre
                        }}</span>
                    </p>

                    <div class="flex flex-col gap-2">
                        <Label for="password" :class="claseEtiqueta"
                            >Contraseña</Label
                        >
                        <PasswordInput
                            id="password"
                            name="password"
                            class="campo-sicop [--relleno-campo-der:44px]"
                            required
                            autocomplete="new-password"
                            placeholder="Ingrese una contraseña"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label
                            for="password_confirmation"
                            :class="claseEtiqueta"
                            >Repetir contraseña</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="campo-sicop [--relleno-campo-der:44px]"
                            required
                            autocomplete="new-password"
                            placeholder="Repita la contraseña"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>
            </template>
            <template #lateral>
                <aside
                    class="rv flex min-w-0 flex-[1_1_280px] flex-col gap-3.5 rounded-[22px] border border-brand-gold/55 bg-cream p-7"
                    style="--i: 3"
                    aria-labelledby="sobre-la-sede-title"
                >
                    <h2
                        id="sobre-la-sede-title"
                        class="font-heading text-lg leading-[normal] font-bold"
                    >
                        Sobre la sede
                    </h2>
                    <p class="text-sm leading-[1.6] text-muted-foreground">
                        El selector de sede aparece solo al elegir un rol que
                        pertenece a una sede, y solo si quien registra puede
                        elegirla; en otro caso el usuario queda en la sede de
                        quien lo registra.
                    </p>
                </aside>
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
