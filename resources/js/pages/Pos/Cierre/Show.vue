<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import FormularioGasto from '@/components/Pos/FormularioGasto.vue';
import ResumenCaja from '@/components/Pos/ResumenCaja.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type {
    GastoCaja,
    ResumenCaja as ResumenCajaDelDia,
    Sede,
} from '@/types';

const props = defineProps<{
    sede: Sede;
    fecha: string;
    resumen: ResumenCajaDelDia;
    gastos: GastoCaja[];
    valorGastos: number;
    valorEfectivoEsperado: number;
    estaCerrado: boolean;
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
                <Badge v-if="estaCerrado" variant="outline">Día cerrado</Badge>
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
            :valor-gastos="valorGastos"
            :valor-efectivo-esperado="valorEfectivoEsperado"
        />

        <section class="flex flex-col gap-4">
            <h2 class="font-medium">Gastos del día</h2>

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Concepto</th>
                            <th class="px-4 py-2 font-medium">Valor</th>
                            <th class="px-4 py-2 font-medium">Soporte</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="gasto in gastos" :key="gasto.id">
                            <td class="px-4 py-2">{{ gasto.concepto }}</td>
                            <td class="px-4 py-2">
                                {{ formatearPesos(gasto.valor_gasto) }}
                            </td>
                            <td class="px-4 py-2">
                                {{ gasto.nombre_soporte }}
                            </td>
                        </tr>
                        <tr v-if="gastos.length === 0">
                            <td
                                colspan="3"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                No hay gastos registrados este día.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <FormularioGasto
                v-if="!estaCerrado && sede.es_activa"
                :sede-id="sede.id"
                :fecha="fecha"
            />
        </section>
    </div>
</template>
