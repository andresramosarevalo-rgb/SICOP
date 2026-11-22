<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import admin from '@/routes/admin';
import type { UsuarioAdministrativo } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: admin.usuarios.index() }],
    },
});

defineProps<{
    usuarios: UsuarioAdministrativo[];
}>();

const etiquetasRol: Record<UsuarioAdministrativo['rol'], string> = {
    super_admin: 'Super administrador',
    administrador: 'Administrador',
    auxiliar_nomina: 'Auxiliar de nómina',
    contador: 'Contador',
    cajero: 'Cajero',
};

const coloresAvatar = [
    'bg-primary text-cta-mark',
    'bg-brand-blue text-white',
    'bg-brand-gold text-brand-navy',
    'bg-brand-slate text-brand-deep',
];
</script>

<template>
    <Head title="Usuarios" />

    <div
        class="mx-auto flex w-full max-w-[1280px] flex-1 flex-col gap-7 px-4 pt-7 pb-12 md:px-10"
    >
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Usuarios
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Administre los accesos del personal de COVIACOL.
                </p>
            </div>

            <Button
                as-child
                class="boton-usuarios h-12 gap-2.5 rounded-full px-6 text-[15px] font-bold"
            >
                <Link :href="admin.usuarios.create()">
                    <span class="go inline-flex text-cta-mark"
                        ><Plus
                            class="size-[18px]"
                            :stroke-width="2.4"
                            aria-hidden="true"
                    /></span>
                    Registrar usuario
                </Link>
            </Button>
        </header>

        <section
            class="rv overflow-hidden rounded-[22px] border bg-card shadow-[0_1px_2px_rgba(21,51,89,0.04)]"
            style="--i: 2"
            aria-labelledby="lista-usuarios-title"
        >
            <div
                class="flex min-h-[66px] flex-wrap items-center gap-3 border-b border-line-faint px-6 py-5"
            >
                <h2
                    id="lista-usuarios-title"
                    class="font-heading text-lg leading-[normal] font-bold"
                >
                    Personal con acceso
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead
                        class="bg-background text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-6 py-3.5 font-bold">
                                Nombre
                            </th>
                            <th scope="col" class="px-4 py-3.5 font-bold">
                                Correo
                            </th>
                            <th scope="col" class="px-4 py-3.5 font-bold">
                                Rol
                            </th>
                            <th scope="col" class="px-4 py-3.5 font-bold">
                                Sede
                            </th>
                            <th scope="col" class="px-4 py-3.5 font-bold">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3.5 font-bold">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(usuario, indice) in usuarios"
                            :key="usuario.id"
                            class="fila-usuario border-t border-line-faint"
                            :style="{ '--i': indice }"
                        >
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex size-[38px] flex-none items-center justify-center rounded-full font-heading text-[13px] font-bold"
                                        :class="
                                            coloresAvatar[
                                                indice % coloresAvatar.length
                                            ]
                                        "
                                        aria-hidden="true"
                                    >
                                        {{ getInitials(usuario.name) }}
                                    </span>
                                    <span class="font-semibold">{{
                                        usuario.name
                                    }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-muted-foreground">
                                {{ usuario.email }}
                            </td>
                            <td class="px-4 py-3.5">
                                <Badge
                                    variant="outline"
                                    class="border-line-strong bg-secondary px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                >
                                    {{ etiquetasRol[usuario.rol] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3.5">
                                {{ usuario.sede?.nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <Badge
                                    variant="outline"
                                    class="gap-2 border-0 px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                    :class="
                                        usuario.es_activo
                                            ? 'bg-ok-bg text-success'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    <span
                                        class="size-2 rounded-full"
                                        :class="
                                            usuario.es_activo
                                                ? 'punto-activo bg-[#176749]'
                                                : 'bg-[#8798ad]'
                                        "
                                        aria-hidden="true"
                                    />
                                    {{
                                        usuario.es_activo
                                            ? 'Activo'
                                            : 'Deshabilitado'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-6 py-3.5">
                                <div
                                    v-if="usuario.puede_gestionar"
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <Button
                                        variant="outline"
                                        as-child
                                        class="fantasma min-h-10 rounded-[10px] border-line-strong bg-card px-4 text-[13px] font-semibold shadow-none dark:bg-card"
                                    >
                                        <Link
                                            :href="
                                                admin.usuarios.edit(usuario.id)
                                            "
                                            >Editar</Link
                                        >
                                    </Button>
                                    <Form
                                        v-bind="
                                            UsuarioController.cambiarEstado.form(
                                                usuario.id,
                                            )
                                        "
                                        :options="{ preserveScroll: true }"
                                        v-slot="{ processing }"
                                    >
                                        <input
                                            type="hidden"
                                            name="es_activo"
                                            :value="usuario.es_activo ? 0 : 1"
                                        />
                                        <Button
                                            variant="outline"
                                            class="fantasma min-h-10 rounded-[10px] border-line-strong bg-card px-4 text-[13px] font-semibold shadow-none dark:bg-card"
                                            :disabled="processing"
                                        >
                                            {{
                                                usuario.es_activo
                                                    ? 'Deshabilitar'
                                                    : 'Habilitar'
                                            }}
                                        </Button>
                                    </Form>
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                        </tr>

                        <tr v-if="usuarios.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                Todavía no hay usuarios registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
