<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import ResumenCaja from '@/components/Pos/ResumenCaja.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import pos from '@/routes/pos';
import type { ResumenCaja as ResumenCajaDelDia, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    fecha: string;
    resumen: ResumenCajaDelDia;
    valorEfectivoEsperado: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cierre de caja', href: '' },
        ],
    },
});

function cambiarDia(fecha: string | number): void {
    if (fecha) {
        router.get(
            pos.sedes.cierre.show(props.sede.id),
            { fecha },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="`Cierre de caja · ${sede.nombre}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Cierre de caja"
                :description="`${sede.nombre} · ${sede.ciudad}`"
            />
            <div class="flex items-end gap-3">
                <div class="grid gap-2">
                    <Label for="fecha">Día</Label>
                    <Input
                        id="fecha"
                        type="date"
                        :max="new Date().toLocaleDateString('en-CA')"
                        :model-value="fecha"
                        @update:model-value="cambiarDia"
                    />
                </div>
            </div>
        </div>

        <ResumenCaja
            :resumen="resumen"
            :valor-efectivo-esperado="valorEfectivoEsperado"
        />
    </div>
</template>
