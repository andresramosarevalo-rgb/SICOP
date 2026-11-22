<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    claseDiferencia,
    describirDiferencia,
    formatearPesos,
} from '@/lib/formato';
import pos from '@/routes/pos';
import type { FilaCierreCaja } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cierres de caja', href: '' },
        ],
    },
});

defineProps<{
    fecha: string;
    filas: FilaCierreCaja[];
    valorTotalAConsignar: number;
}>();

function filtrarPorDia(fecha: string | number): void {
    if (fecha) {
        router.get(pos.cierres.index(), { fecha }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Cierres de caja" />

    <PaginaPos>
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2">
                <h1
                    class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                >
                    Cierres de caja
                </h1>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    Estado del cierre y arqueo de cada sede en el día
                    seleccionado.
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="pos.consignaciones.index()"
                        >Consignaciones</Link
                    >
                </Button>
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <a
                        :href="pos.cierres.exportar.url({ query: { fecha } })"
                        title="Descargar CSV"
                        >Exportar</a
                    >
                </Button>
            </div>
        </header>

        <div
            class="rv flex flex-wrap items-end justify-between gap-4"
            style="--i: 2"
        >
            <div class="flex min-w-[220px] flex-col gap-2">
                <Label
                    for="fecha"
                    class="text-sm leading-[normal] font-semibold"
                    >Día</Label
                >
                <Input
                    id="fecha"
                    type="date"
                    class="campo-sicop"
                    :model-value="fecha"
                    @update:model-value="filtrarPorDia"
                />
            </div>
            <div
                class="flex items-center gap-3.5 rounded-[18px] border border-brand-gold/55 bg-cream px-[22px] py-4"
            >
                <span class="text-sm text-muted-foreground"
                    >Total a consignar de las sedes cerradas:</span
                >
                <span
                    class="font-heading text-2xl leading-[normal] font-extrabold"
                    >{{ formatearPesos(valorTotalAConsignar) }}</span
                >
            </div>
        </div>

        <PanelTabla
            titulo="Arqueos por sede"
            :indice="3"
            relleno="16px"
            ocultar-titulo
        >
            <table class="min-w-[1000px]">
                <thead>
                    <tr>
                        <th scope="col">Sede</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-right">Efectivo contado</th>
                        <th scope="col">Arqueo efectivo</th>
                        <th scope="col">Arqueo datáfono</th>
                        <th scope="col">Arqueo QR</th>
                        <th scope="col" class="text-right">A consignar</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="fila in filas" :key="fila.sede.id">
                        <td class="font-semibold">{{ fila.sede.nombre }}</td>
                        <td>
                            <span
                                class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                                :class="
                                    fila.cierre
                                        ? 'bg-ok-bg text-success'
                                        : 'bg-cream text-warning'
                                "
                                >{{
                                    fila.cierre ? 'Cerrado' : 'Sin cerrar'
                                }}</span
                            >
                        </td>
                        <template v-if="fila.cierre">
                            <td
                                class="text-right font-heading whitespace-nowrap"
                            >
                                {{
                                    formatearPesos(
                                        fila.cierre.valor_efectivo_contado,
                                    )
                                }}
                            </td>
                            <td
                                class="font-semibold"
                                :class="
                                    claseDiferencia(
                                        fila.cierre.valor_diferencia_efectivo,
                                    )
                                "
                            >
                                {{
                                    describirDiferencia(
                                        fila.cierre.valor_diferencia_efectivo,
                                    )
                                }}
                            </td>
                            <td
                                class="font-semibold"
                                :class="
                                    claseDiferencia(
                                        fila.cierre.valor_diferencia_datafono,
                                    )
                                "
                            >
                                {{
                                    describirDiferencia(
                                        fila.cierre.valor_diferencia_datafono,
                                    )
                                }}
                            </td>
                            <td
                                class="font-semibold"
                                :class="
                                    claseDiferencia(
                                        fila.cierre.valor_diferencia_qr,
                                    )
                                "
                            >
                                {{
                                    describirDiferencia(
                                        fila.cierre.valor_diferencia_qr,
                                    )
                                }}
                            </td>
                            <td
                                class="text-right font-heading whitespace-nowrap"
                            >
                                {{
                                    formatearPesos(
                                        fila.cierre.valor_a_consignar,
                                    )
                                }}
                            </td>
                        </template>
                        <td v-else colspan="5" class="text-muted-foreground">
                            —
                        </td>
                        <td>
                            <Button
                                variant="outline"
                                as-child
                                class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                            >
                                <Link
                                    :href="
                                        pos.sedes.cierre.show(fila.sede.id, {
                                            query: { fecha },
                                        })
                                    "
                                    >Ver</Link
                                >
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="filas.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            No hay sedes registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
