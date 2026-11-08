<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
    administrador: 'Administrador',
    auxiliar_nomina: 'Auxiliar de nómina',
    contador: 'Contador',
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
                    </tr>

                    <tr v-if="usuarios.length === 0">
                        <td
                            colspan="3"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Todavía no hay usuarios administrativos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
