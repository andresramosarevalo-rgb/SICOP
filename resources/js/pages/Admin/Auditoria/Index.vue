<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuditoriaController from '@/actions/App/Http/Controllers/Admin/AuditoriaController';
import CambiosAuditoria from '@/components/Admin/CambiosAuditoria.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import InputError from '@/components/InputError.vue';
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

const claseSelect = 'campo-sicop [--alto-campo:46px] [--relleno-campo:14px]';

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';

const nombresEvento: Record<string, string> = {
    creado: 'Creó',
    modificado: 'Modificó',
    eliminado: 'Eliminó',
};

function formatearFecha(fecha: string): string {
    const momento = new Date(fecha);
    const dos = (numero: number): string => String(numero).padStart(2, '0');

    return `${dos(momento.getDate())}/${dos(momento.getMonth() + 1)}/${momento.getFullYear()} ${dos(momento.getHours())}:${dos(momento.getMinutes())}`;
}
</script>

<template>
    <Head title="Auditoría" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Auditoría
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Historial de quién creó, modificó o eliminó registros del punto
                de venta y de la nómina.
            </p>
        </header>

        <Form
            v-bind="AuditoriaController.form()"
            class="rv flex flex-col gap-5 rounded-[22px] border bg-card px-7 py-6"
            style="--i: 2"
            v-slot="{ errors }"
        >
            <div
                class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-4"
            >
                <div class="flex flex-col gap-2">
                    <Label for="modulo" :class="claseEtiqueta">Módulo</Label>
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
                <div class="flex flex-col gap-2">
                    <Label for="sede_id" :class="claseEtiqueta">Sede</Label>
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
                <div class="flex flex-col gap-2">
                    <Label for="user_id" :class="claseEtiqueta">Usuario</Label>
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
                <div class="flex flex-col gap-2">
                    <Label for="etiqueta" :class="claseEtiqueta"
                        >Tipo de registro</Label
                    >
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
                <div class="flex flex-col gap-2">
                    <Label for="evento" :class="claseEtiqueta">Acción</Label>
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
                <div class="flex flex-col gap-2">
                    <Label for="desde" :class="claseEtiqueta">Desde</Label>
                    <Input
                        id="desde"
                        name="desde"
                        type="date"
                        :class="claseSelect"
                        :default-value="filtros.desde"
                    />
                    <InputError :message="errors.desde" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="hasta" :class="claseEtiqueta">Hasta</Label>
                    <Input
                        id="hasta"
                        name="hasta"
                        type="date"
                        :class="claseSelect"
                        :default-value="filtros.hasta"
                    />
                    <InputError :message="errors.hasta" />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <Button
                    class="boton-plano h-[46px] rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                    >Filtrar</Button
                >
                <Button
                    variant="ghost"
                    as-child
                    class="boton-plano h-[46px] rounded-full px-5 text-[15px] font-semibold text-muted-foreground hover:bg-transparent hover:text-muted-foreground"
                >
                    <Link :href="admin.auditoria.index()">Limpiar filtros</Link>
                </Button>
            </div>
        </Form>

        <PanelTabla titulo="Cambios registrados" :indice="3">
            <table class="min-w-[1000px]">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Sede</th>
                        <th scope="col">Acción</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="auditoria in auditorias.data"
                        :key="auditoria.id"
                        class="align-top"
                    >
                        <td class="whitespace-nowrap">
                            {{ formatearFecha(auditoria.created_at) }}
                        </td>
                        <td class="font-semibold">
                            {{ auditoria.usuario?.name ?? 'Sistema' }}
                        </td>
                        <td>{{ auditoria.sede?.nombre ?? '—' }}</td>
                        <td>
                            <span
                                class="inline-flex rounded-full px-3 py-1 text-[13px] leading-[normal] font-semibold"
                                :class="
                                    auditoria.evento === 'eliminado'
                                        ? 'bg-danger-bg text-danger'
                                        : 'border border-line-strong'
                                "
                                >{{ nombresEvento[auditoria.evento] }}</span
                            >
                        </td>
                        <td>
                            <p class="font-semibold">
                                {{ auditoria.etiqueta }}
                            </p>
                            <p class="mt-0.5 text-muted-foreground">
                                {{ auditoria.descripcion }}
                            </p>
                        </td>
                        <td>
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
        </PanelTabla>

        <div class="rv flex justify-between gap-3" style="--i: 4">
            <Button
                v-if="auditorias.prev_page_url"
                variant="outline"
                as-child
                class="boton-plano h-11 rounded-full border-line-strong bg-card px-5 text-sm font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
            >
                <Link :href="auditorias.prev_page_url">Anterior</Link>
            </Button>
            <span
                v-else
                class="inline-flex h-11 items-center rounded-full border px-5 text-sm font-semibold text-ink-3"
                >Anterior</span
            >
            <Button
                v-if="auditorias.next_page_url"
                variant="outline"
                as-child
                class="boton-plano h-11 rounded-full border-line-strong bg-card px-5 text-sm font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
            >
                <Link :href="auditorias.next_page_url">Siguiente</Link>
            </Button>
        </div>
    </PaginaPos>
</template>
