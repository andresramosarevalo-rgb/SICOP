<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import CierreCajaController from '@/actions/App/Http/Controllers/Pos/CierreCajaController';
import ConsignacionController from '@/actions/App/Http/Controllers/Pos/ConsignacionController';
import GastoCajaController from '@/actions/App/Http/Controllers/Pos/GastoCajaController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import FormularioConsignacion from '@/components/Pos/FormularioConsignacion.vue';
import FormularioArqueo from '@/components/Pos/FormularioArqueo.vue';
import FormularioGasto from '@/components/Pos/FormularioGasto.vue';
import ResultadoArqueo from '@/components/Pos/ResultadoArqueo.vue';
import ResumenCaja from '@/components/Pos/ResumenCaja.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearFecha, formatearPesos } from '@/lib/formato';
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
        <header
            class="rv flex flex-wrap items-end justify-between gap-5"
            style="--i: 1"
        >
            <div class="flex flex-col gap-2.5">
                <div class="flex flex-wrap items-center gap-3">
                    <h1
                        class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
                    >
                        Cierre de caja
                    </h1>
                    <span
                        class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                        :class="
                            estaCerrado
                                ? 'bg-ok-bg text-success'
                                : 'bg-cream text-warning'
                        "
                        >{{ estaCerrado ? 'Día cerrado' : 'Día abierto' }}</span
                    >
                </div>
                <p class="text-[17px] leading-[1.6] text-muted-foreground">
                    {{ sede.nombre }} · {{ sede.ciudad }}
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex min-w-[200px] flex-col gap-2">
                    <Label
                        for="fecha"
                        class="text-sm leading-[normal] font-semibold"
                        >Día</Label
                    >
                    <Input
                        id="fecha"
                        type="date"
                        class="campo-sicop"
                        :max="new Date().toLocaleDateString('en-CA')"
                        :model-value="fecha"
                        @update:model-value="cambiarDia"
                    />
                </div>
                <Form
                    v-if="
                        cierre &&
                        !consignacion &&
                        page.props.auth.permisos.gestionarPos
                    "
                    v-bind="CierreCajaController.reabrir.form(sede.id)"
                    v-slot="{ processing }"
                >
                    <input type="hidden" name="fecha" :value="fecha" />
                    <Button
                        variant="outline"
                        :disabled="processing"
                        title="Descarta el arqueo para corregir y volver a cerrar."
                        class="boton-plano h-[50px] rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                        >Reabrir el día</Button
                    >
                </Form>
            </div>
        </header>

        <ResumenCaja
            :resumen="resumen"
            :valor-gastos="valorGastos"
            :valor-efectivo-esperado="valorEfectivoEsperado"
        />

        <section class="rv flex flex-col gap-3.5" style="--i: 3">
            <h2 class="font-heading text-lg leading-[normal] font-bold">
                Gastos del día
            </h2>
            <PanelTabla titulo="Gastos del día" sin-animacion ocultar-titulo>
                <table class="min-w-0">
                    <thead>
                        <tr>
                            <th scope="col">Concepto</th>
                            <th scope="col" class="text-right">Valor</th>
                            <th scope="col">Soporte</th>
                            <th scope="col">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="gasto in gastos" :key="gasto.id">
                            <td>{{ gasto.concepto }}</td>
                            <td
                                class="text-right font-heading whitespace-nowrap"
                            >
                                {{ formatearPesos(gasto.valor_gasto) }}
                            </td>
                            <td>
                                <a
                                    :href="
                                        pos.cierre.gastos.soporte.url(gasto.id)
                                    "
                                    class="rounded-sm font-semibold text-success hover:underline"
                                    >{{ gasto.nombre_soporte }}</a
                                >
                            </td>
                            <td>
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
                                        variant="outline"
                                        :disabled="processing"
                                        class="boton-plano min-h-9 rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold text-danger shadow-none hover:bg-card hover:text-danger dark:bg-card dark:hover:bg-card"
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
            class="rv flex flex-col gap-[18px] rounded-[22px] border bg-card px-7 py-6"
            style="--i: 5"
        >
            <h2 class="font-heading text-lg leading-[normal] font-bold">
                Consignación
            </h2>

            <div v-if="consignacion" class="flex flex-col gap-2 text-sm">
                <p>
                    {{ formatearPesos(consignacion.valor_consignado) }}
                    consignados el
                    {{ formatearFecha(consignacion.fecha_consignacion) }} ·
                    referencia {{ consignacion.referencia }}
                </p>
                <p>
                    <a
                        :href="pos.consignaciones.soporte.url(consignacion.id)"
                        class="rounded-sm font-semibold text-success hover:underline"
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
                        variant="outline"
                        :disabled="processing"
                        class="boton-plano mt-1 min-h-9 rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold text-danger shadow-none hover:bg-card hover:text-danger dark:bg-card dark:hover:bg-card"
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
                        Aún no hay consignación registrada para este día.
                        Pendiente de consignar:
                        <span class="font-heading font-bold text-foreground">{{
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

        <section
            v-if="!cierre && !estaCerrado && sede.es_activa"
            class="rv flex flex-col gap-[18px] rounded-[22px] border bg-card px-7 py-6"
            style="--i: 5"
        >
            <h2 class="font-heading text-lg leading-[normal] font-bold">
                Cerrar el día
            </h2>
            <FormularioArqueo :sede-id="sede.id" :fecha="fecha" />
        </section>
    </PaginaPos>
</template>
