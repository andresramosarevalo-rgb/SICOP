<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
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
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="dashboard()" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Usuarios"
                description="Administre los accesos del personal de COVIACOL."
            />

            <Button as-child>
                <Link :href="admin.usuarios.create()"> Registrar usuario </Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Correo</th>
                        <th class="px-4 py-3 font-medium">Rol</th>
                        <th class="px-4 py-3 font-medium">Sede</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <tr v-for="usuario in usuarios" :key="usuario.id">
                        <td class="px-4 py-3">
                            {{ usuario.name }}
                        </td>
                        <td class="px-4 py-3">
                            {{ usuario.email }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">
                                {{ etiquetasRol[usuario.rol] }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            {{ usuario.sede?.nombre ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    usuario.es_activo ? 'secondary' : 'outline'
                                "
                            >
                                {{
                                    usuario.es_activo
                                        ? 'Activo'
                                        : 'Deshabilitado'
                                }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="usuario.puede_gestionar"
                                class="flex flex-wrap items-center gap-2"
                            >
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="admin.usuarios.edit(usuario.id)"
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
                                        size="sm"
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
                            <span v-else class="text-muted-foreground">—</span>
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
    </div>
</template>
