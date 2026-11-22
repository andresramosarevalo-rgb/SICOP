<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import { Button } from '@/components/ui/button';
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

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Sedes
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Sedes de COVIACOL con su ciudad y dirección.
                </p>
            </div>
            <Button
                as-child
                class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
            >
                <Link :href="pos.sedes.create()">Nueva sede</Link>
            </Button>
        </header>

        <PanelTabla titulo="Sedes registradas" relleno="16px">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Ciudad</th>
                        <th scope="col">Dirección</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Cajeros</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sede in sedes" :key="sede.id">
                        <td class="font-semibold">{{ sede.nombre }}</td>
                        <td>{{ sede.ciudad }}</td>
                        <td class="text-muted-foreground">
                            {{ sede.direccion }}
                        </td>
                        <td>
                            <span
                                class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                :class="
                                    sede.es_activa
                                        ? 'bg-ok-bg text-success'
                                        : 'bg-muted text-muted-foreground'
                                "
                                >{{
                                    sede.es_activa ? 'Activa' : 'Inactiva'
                                }}</span
                            >
                        </td>
                        <td>{{ sede.usuarios_count ?? 0 }}</td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="accion in [
                                        {
                                            texto: 'Cursos',
                                            href: pos.sedes.cursos.index(
                                                sede.id,
                                            ),
                                        },
                                        {
                                            texto: 'SOAT',
                                            href: pos.sedes.soats.index(
                                                sede.id,
                                            ),
                                        },
                                        {
                                            texto: 'Editar',
                                            href: pos.sedes.edit(sede.id),
                                        },
                                    ]"
                                    :key="accion.texto"
                                    variant="outline"
                                    as-child
                                    class="min-h-10 rounded-[10px] border-line-strong bg-transparent px-4 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                                >
                                    <Link :href="accion.href">{{
                                        accion.texto
                                    }}</Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="sedes.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Aún no hay sedes registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
