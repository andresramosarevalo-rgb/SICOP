<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import CierreCajaController from '@/actions/App/Http/Controllers/Pos/CierreCajaController';
import ConsignacionController from '@/actions/App/Http/Controllers/Pos/ConsignacionController';
import GastoCajaController from '@/actions/App/Http/Controllers/Pos/GastoCajaController';
import Heading from '@/components/Heading.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import FormularioConsignacion from '@/components/Pos/FormularioConsignacion.vue';
import FormularioArqueo from '@/components/Pos/FormularioArqueo.vue';
import FormularioGasto from '@/components/Pos/FormularioGasto.vue';
import ResultadoArqueo from '@/components/Pos/ResultadoArqueo.vue';
import ResumenCaja from '@/components/Pos/ResumenCaja.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type {
    CierreCaja,
    Consignacion,
    GastoCaja,
    ResumenCaja as ResumenCajaDelDia,
    Sede,
} from '@/types';

const page = usePage();

const props = defineProps<{
    sede: Sede;
    fecha: string;
    resumen: ResumenCajaDelDia;
    gastos: GastoCaja[];
    valorGastos: number;
    valorEfectivoEsperado: number;
    estaCerrado: boolean;
    cierre: CierreCaja | null;
    consignacion: Consignacion | null;
    cierreId: number | null;
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

    <PaginaPos>
        <BotonAtras
            :respaldo="
                page.props.auth.permisos.gestionarPos
                    ? pos.cierres.index({ query: { fecha } })
                    : pos.sedes.cursos.index(sede.id)
            "
        />

        <div
            class="flex flex-wrap items-end justify-between gap-4 rounded-xl border border-brand-gold/30 bg-accent/50 p-5"
        >
            <Heading
                title="Cierre de caja"
                :description="`${sede.nombre} · ${sede.ciudad}`"
            />
            <div class="flex flex-wrap items-end gap-3">
                <Badge
                    v-if="estaCerrado"
                    variant="outline"
                    class="border-success/20 bg-success/10 text-success"
                    >Día cerrado</Badge
                >
                <Badge
                    v-else
                    variant="outline"
                    class="border-warning/20 bg-warning/10 text-warning"
                    >Día abierto</Badge
                >
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
            <PanelTabla titulo="Gastos del día">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Concepto</th>
                            <th class="px-4 py-2 text-right font-medium">
                                Valor
                            </th>
                            <th class="px-4 py-2 font-medium">Soporte</th>
                            <th class="px-4 py-2">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="gasto in gastos" :key="gasto.id">
                            <td class="px-4 py-2">{{ gasto.concepto }}</td>
                            <td
                                class="px-4 py-2 text-right whitespace-nowrap tabular-nums"
                            >
                                {{ formatearPesos(gasto.valor_gasto) }}
                            </td>
                            <td class="px-4 py-2">
                                <a
                                    :href="
                                        pos.cierre.gastos.soporte.url(gasto.id)
                                    "
                                    class="rounded-sm font-medium text-primary underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                                    >{{ gasto.nombre_soporte }}</a
                                >
                            </td>
                            <td class="px-4 py-2">
                                <Form
                                    v-if="!estaCerrado"
                                    v-bind="
                                        GastoCajaController.destroy.form(
                                            gasto.id,
                                        )
                                    "
                                    class="flex justify-end"
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        size="sm"
                                        :disabled="processing"
                                        >Eliminar</Button
                                    >
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="gastos.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                No hay gastos registrados este día.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </PanelTabla>

            <FormularioGasto
                v-if="!estaCerrado && sede.es_activa"
                :sede-id="sede.id"
                :fecha="fecha"
            />
        </section>

        <ResultadoArqueo v-if="cierre" :cierre="cierre" />

        <section
            v-if="cierre"
            class="flex flex-col gap-5 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
        >
            <h2 class="text-base font-semibold">Consignación</h2>

            <div v-if="consignacion" class="flex flex-col gap-2 text-sm">
                <p>
                    {{ formatearPesos(consignacion.valor_consignado) }}
                    consignados el {{ consignacion.fecha_consignacion }} ·
                    referencia {{ consignacion.referencia }}
                </p>
                <p>
                    <a
                        :href="pos.consignaciones.soporte.url(consignacion.id)"
                        class="rounded-sm font-medium text-primary underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                        >{{ consignacion.nombre_soporte }}</a
                    >
                </p>
                <p
                    v-if="
                        Number(cierre.valor_a_consignar) >
                        Number(consignacion.valor_consignado)
                    "
                    class="text-muted-foreground"
                >
                    Quedan por consignar
                    {{
                        formatearPesos(
                            Number(cierre.valor_a_consignar) -
                                Number(consignacion.valor_consignado),
                        )
                    }}.
                </p>
                <Form
                    v-if="page.props.auth.permisos.gestionarPos"
                    v-bind="
                        ConsignacionController.destroy.form(consignacion.id)
                    "
                    v-slot="{ processing }"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        class="self-start text-destructive hover:text-destructive"
                        :disabled="processing"
                        >Eliminar consignación</Button
                    >
                </Form>
            </div>

            <template v-else>
                <p
                    v-if="Number(cierre.valor_a_consignar) <= 0"
                    class="text-sm text-muted-foreground"
                >
                    Este día no hay efectivo por consignar.
                </p>
                <template v-else>
                    <p class="text-sm text-muted-foreground">
                        Pendiente de consignar:
                        <span class="font-semibold text-foreground">{{
                            formatearPesos(cierre.valor_a_consignar)
                        }}</span>
                    </p>
                    <FormularioConsignacion
                        v-if="page.props.auth.permisos.gestionarPos && cierreId"
                        :cierre-id="cierreId"
                        :valor-a-consignar="cierre.valor_a_consignar"
                    />
                </template>
            </template>
        </section>

        <Form
            v-if="
                cierre && !consignacion && page.props.auth.permisos.gestionarPos
            "
            v-bind="CierreCajaController.reabrir.form(sede.id)"
            class="flex flex-wrap items-center gap-3 rounded-xl border bg-card p-5"
            v-slot="{ processing }"
        >
            <input type="hidden" name="fecha" :value="fecha" />
            <Button variant="outline" :disabled="processing"
                >Reabrir el día</Button
            >
            <p class="text-sm text-muted-foreground">
                Descarta el arqueo para corregir y volver a cerrar.
            </p>
        </Form>

        <section
            v-else-if="!estaCerrado && sede.es_activa"
            class="flex flex-col gap-5 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
        >
            <h2 class="text-base font-semibold">Cerrar el día</h2>
            <FormularioArqueo
                :sede-id="sede.id"
                :fecha="fecha"
                :resumen="resumen"
                :valor-efectivo-esperado="valorEfectivoEsperado"
            />
        </section>
    </PaginaPos>
</template>
