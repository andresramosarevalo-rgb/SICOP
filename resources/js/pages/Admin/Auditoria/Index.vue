<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuditoriaController from '@/actions/App/Http/Controllers/Admin/AuditoriaController';
import CambiosAuditoria from '@/components/Admin/CambiosAuditoria.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { Auditoria, FiltrosAuditoria, PaginaSimple, Sede } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Auditoría', href: '' }],
    },
});

defineProps<{
    auditorias: PaginaSimple<Auditoria>;
    filtros: FiltrosAuditoria;
    sedes: Pick<Sede, 'id' | 'nombre'>[];
    usuarios: { id: number; name: string }[];
    etiquetas: string[];
}>();

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';

const nombresEvento: Record<string, string> = {
    creado: 'Creó',
    modificado: 'Modificó',
    eliminado: 'Eliminó',
};

function formatearFecha(fecha: string): string {
    return new Date(fecha).toLocaleString('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Auditoría" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Auditoría"
            description="Historial de quién creó, modificó o eliminó registros del punto de venta y de la nómina."
        />

        <Form
            v-bind="AuditoriaController.form()"
            class="grid gap-4 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-4"
            v-slot="{ errors }"
        >
            <div class="grid gap-2">
                <Label for="modulo">Módulo</Label>
                <select
                    id="modulo"
                    name="modulo"
                    :class="claseSelect"
                    :value="filtros.modulo ?? ''"
                >
                    <option value="">Todos</option>
                    <option value="pos">Punto de venta</option>
                    <option value="nomina">Nómina</option>
                    <option value="admin">Usuarios</option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="sede_id">Sede</Label>
                <select
                    id="sede_id"
                    name="sede_id"
                    :class="claseSelect"
                    :value="filtros.sede_id ?? ''"
                >
                    <option value="">Todas</option>
                    <option
                        v-for="sede in sedes"
                        :key="sede.id"
                        :value="sede.id"
                    >
                        {{ sede.nombre }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="user_id">Usuario</Label>
                <select
                    id="user_id"
                    name="user_id"
                    :class="claseSelect"
                    :value="filtros.user_id ?? ''"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="usuario in usuarios"
                        :key="usuario.id"
                        :value="usuario.id"
                    >
                        {{ usuario.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="etiqueta">Tipo de registro</Label>
                <select
                    id="etiqueta"
                    name="etiqueta"
                    :class="claseSelect"
                    :value="filtros.etiqueta ?? ''"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="etiqueta in etiquetas"
                        :key="etiqueta"
                        :value="etiqueta"
                    >
                        {{ etiqueta }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="evento">Acción</Label>
                <select
                    id="evento"
                    name="evento"
                    :class="claseSelect"
                    :value="filtros.evento ?? ''"
                >
                    <option value="">Todas</option>
                    <option value="creado">Creó</option>
                    <option value="modificado">Modificó</option>
                    <option value="eliminado">Eliminó</option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="desde">Desde</Label>
                <Input
                    id="desde"
                    name="desde"
                    type="date"
                    :default-value="filtros.desde"
                />
                <InputError :message="errors.desde" />
            </div>
            <div class="grid gap-2">
                <Label for="hasta">Hasta</Label>
                <Input
                    id="hasta"
                    name="hasta"
                    type="date"
                    :default-value="filtros.hasta"
                />
                <InputError :message="errors.hasta" />
            </div>
            <div class="flex items-end gap-2">
                <Button>Filtrar</Button>
                <Button variant="ghost" as-child>
                    <Link :href="admin.auditoria.index()">Limpiar</Link>
                </Button>
            </div>
        </Form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Usuario</th>
                        <th class="px-4 py-2 font-medium">Sede</th>
                        <th class="px-4 py-2 font-medium">Acción</th>
                        <th class="px-4 py-2 font-medium">Registro</th>
                        <th class="px-4 py-2 font-medium">Cambios</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="auditoria in auditorias.data"
                        :key="auditoria.id"
                        class="align-top"
                    >
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ formatearFecha(auditoria.created_at) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ auditoria.usuario?.name ?? 'Sistema' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ auditoria.sede?.nombre ?? '—' }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="
                                    auditoria.evento === 'eliminado'
                                        ? 'destructive'
                                        : 'outline'
                                "
                                >{{ nombresEvento[auditoria.evento] }}</Badge
                            >
                        </td>
                        <td class="px-4 py-2">
                            <p class="font-medium">{{ auditoria.etiqueta }}</p>
                            <p class="text-muted-foreground">
                                {{ auditoria.descripcion }}
                            </p>
                        </td>
                        <td class="px-4 py-2">
                            <CambiosAuditoria :auditoria="auditoria" />
                        </td>
                    </tr>
                    <tr v-if="auditorias.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            No hay cambios registrados con esos filtros.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between">
            <Button v-if="auditorias.prev_page_url" variant="outline" as-child>
                <Link :href="auditorias.prev_page_url">Anterior</Link>
            </Button>
            <span v-else />
            <Button v-if="auditorias.next_page_url" variant="outline" as-child>
                <Link :href="auditorias.next_page_url">Siguiente</Link>
            </Button>
        </div>
    </div>
</template>
