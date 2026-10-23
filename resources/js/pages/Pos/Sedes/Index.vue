<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Badge } from '@/components/ui/badge';
import pos from '@/routes/pos';
import type { Sede } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
        ],
    },
});

defineProps<{
    sedes: Sede[];
}>();
</script>

<template>
    <Head title="Sedes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Sedes"
                description="Cada sede registra sus cursos y SOAT vendidos."
            />
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Nombre</th>
                        <th class="px-4 py-2 font-medium">Ciudad</th>
                        <th class="px-4 py-2 font-medium">Dirección</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="sede in sedes" :key="sede.id">
                        <td class="px-4 py-2">{{ sede.nombre }}</td>
                        <td class="px-4 py-2">{{ sede.ciudad }}</td>
                        <td class="px-4 py-2">{{ sede.direccion }}</td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="
                                    sede.es_activa ? 'default' : 'outline'
                                "
                                >{{
                                    sede.es_activa ? 'Activa' : 'Inactiva'
                                }}</Badge
                            >
                        </td>
                    </tr>
                    <tr v-if="sedes.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Aún no hay sedes registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
