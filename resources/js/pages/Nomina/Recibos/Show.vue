<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import TablaDetallesRecibo from '@/components/Nomina/TablaDetallesRecibo.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type {
    DetalleReciboNomina,
    Empleado,
    PeriodoNomina,
    ReciboNomina,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Periodos', href: nomina.periodos.index() },
            { title: 'Comprobante de pago', href: '' },
        ],
    },
});

defineProps<{
    recibo: ReciboNomina;
    periodo: Pick<
        PeriodoNomina,
        'id' | 'periodicidad_pago' | 'fecha_inicio' | 'fecha_fin' | 'estado'
    >;
    empleado: Pick<
        Empleado,
        | 'nombres'
        | 'apellidos'
        | 'tipo_documento'
        | 'numero_documento'
        | 'cargo'
        | 'email'
    > & {
        area: string;
    };
    devengos: DetalleReciboNomina[];
    deducciones: DetalleReciboNomina[];
}>();

function imprimir(): void {
    window.print();
}
</script>

<template>
    <Head
        :title="`Comprobante de pago - ${empleado.nombres} ${empleado.apellidos}`"
    />

    <div class="flex h-full max-w-4xl flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="nomina.periodos.show(periodo.id)" />

        <div class="flex justify-end print:hidden">
            <Button variant="outline" @click="imprimir">Imprimir</Button>
        </div>

        <article
            class="flex flex-col gap-6 rounded-xl border p-6 print:border-0 print:p-0"
        >
            <header
                class="flex flex-wrap items-start justify-between gap-4 border-b pb-4"
            >
                <div>
                    <p class="text-lg font-semibold">COVIACOL</p>
                    <p class="text-sm text-muted-foreground">
                        Comprobante de pago de nómina
                    </p>
                </div>
                <div class="text-right text-sm">
                    <p>
                        Periodo {{ periodo.fecha_inicio }} —
                        {{ periodo.fecha_fin }}
                    </p>
                    <p class="text-muted-foreground capitalize">
                        Nómina {{ periodo.periodicidad_pago }}
                    </p>
                </div>
            </header>

            <dl class="grid gap-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-muted-foreground">Empleado</dt>
                    <dd class="font-medium">
                        {{ empleado.nombres }} {{ empleado.apellidos }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Documento</dt>
                    <dd class="font-medium">
                        {{ empleado.tipo_documento }}
                        {{ empleado.numero_documento }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Cargo y área</dt>
                    <dd class="font-medium">
                        {{ empleado.cargo }} · {{ empleado.area }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Salario base mensual</dt>
                    <dd class="font-medium">
                        {{ formatearPesos(recibo.valor_salario_base) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Días de salario</dt>
                    <dd class="font-medium">{{ recibo.dias_liquidados }}</dd>
                </div>
            </dl>

            <div class="grid gap-6 md:grid-cols-2">
                <TablaDetallesRecibo
                    titulo="Devengos"
                    :detalles="devengos"
                    :total="recibo.valor_total_devengado"
                />
                <TablaDetallesRecibo
                    titulo="Deducciones"
                    :detalles="deducciones"
                    :total="recibo.valor_total_deducciones"
                />
            </div>

            <footer
                class="flex items-center justify-between rounded-lg bg-muted/50 p-4 print:bg-transparent"
            >
                <span class="font-medium">Neto a pagar</span>
                <span class="text-xl font-semibold">{{
                    formatearPesos(recibo.valor_neto)
                }}</span>
            </footer>
        </article>
    </div>
</template>
