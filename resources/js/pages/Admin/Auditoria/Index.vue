<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CambiosAuditoria from '@/components/Admin/CambiosAuditoria.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Auditoria, PaginaSimple } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Auditoría', href: '' }],
    },
});

defineProps<{
    auditorias: PaginaSimple<Auditoria>;
}>();

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
