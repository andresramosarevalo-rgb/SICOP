<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import CambiarEstadoEmpleado from '@/components/Nomina/CambiarEstadoEmpleado.vue';
import ConceptosRecurrentes from '@/components/Nomina/ConceptosRecurrentes.vue';
import SaldoInicialVacaciones from '@/components/Nomina/SaldoInicialVacaciones.vue';
import TablaContratos from '@/components/Nomina/TablaContratos.vue';
import { Button } from '@/components/ui/button';
import { formatearFecha } from '@/lib/formato';
import nomina from '@/routes/nomina';
import type {
    AsignacionConcepto,
    ConceptoNomina,
    Contrato,
    Empleado,
    SaldoVacaciones,
} from '@/types';

const props = defineProps<{
    empleado: Empleado;
    tipoDocumento: string;
    tipoCuentaBancaria: string | null;
    contratos: Contrato[];
    contratoVigente: Contrato | null;
    saldoVacaciones: SaldoVacaciones;
    asignaciones: AsignacionConcepto[];
    conceptosDisponibles: ConceptoNomina[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Empleados', href: nomina.empleados.index() },
            { title: 'Expediente', href: '' },
        ],
    },
});

const datos = computed(() => [
    { etiqueta: 'Sede', valor: props.empleado.sede?.nombre },
    {
        etiqueta: 'Documento',
        valor: `${props.tipoDocumento} ${props.empleado.numero_documento}`,
    },
    { etiqueta: 'Correo electrónico', valor: props.empleado.email },
    { etiqueta: 'Teléfono', valor: props.empleado.telefono },
    { etiqueta: 'Dirección', valor: props.empleado.direccion },
    {
        etiqueta: 'Fecha de nacimiento',
        valor: props.empleado.fecha_nacimiento
            ? formatearFecha(props.empleado.fecha_nacimiento)
            : '',
    },
    { etiqueta: 'Área', valor: props.empleado.area?.nombre },
    { etiqueta: 'Cargo', valor: props.empleado.cargo },
    { etiqueta: 'Banco', valor: props.empleado.banco },
    {
        etiqueta: 'Número de cuenta bancaria',
        valor: props.empleado.numero_cuenta_bancaria,
    },
    { etiqueta: 'Tipo de cuenta bancaria', valor: props.tipoCuentaBancaria },
]);

const formatoDias = new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 4,
});

const diasVacaciones = computed(() => [
    { etiqueta: 'Causados', valor: props.saldoVacaciones.diasCausados },
    { etiqueta: 'Utilizados', valor: props.saldoVacaciones.diasUtilizados },
    { etiqueta: 'Disponibles', valor: props.saldoVacaciones.diasDisponibles },
]);
</script>

<template>
    <Head :title="`${empleado.nombres} ${empleado.apellidos}`" />

    <PaginaPos>
        <div
            class="rv flex flex-wrap items-start justify-between gap-5 rounded-[22px] border border-brand-gold/55 bg-cream px-7 py-6"
            style="--i: 1"
        >
            <div class="flex min-w-0 flex-col gap-2.5">
                <div class="flex flex-wrap items-center gap-3">
                    <h1
                        class="font-heading text-3xl leading-[1.15] font-extrabold wrap-anywhere md:text-[34px]"
                    >
                        {{ empleado.nombres }} {{ empleado.apellidos }}
                    </h1>
                    <span
                        class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                        :class="
                            empleado.es_activo
                                ? 'bg-ok-bg text-success'
                                : 'bg-muted text-muted-foreground'
                        "
                        >{{ empleado.es_activo ? 'Activo' : 'Inactivo' }}</span
                    >
                </div>
                <p class="text-base leading-[1.6] text-muted-foreground">
                    Expediente del empleado · {{ tipoDocumento }}
                    {{ empleado.numero_documento }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <CambiarEstadoEmpleado :empleado="empleado" />
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="nomina.empleados.edit(empleado.id)"
                        >Editar</Link
                    >
                </Button>
                <Button
                    as-child
                    class="boton-plano h-12 rounded-full px-6 text-[15px] font-bold hover:bg-primary"
                >
                    <Link :href="nomina.empleados.contratos.create(empleado.id)"
                        >Registrar contrato</Link
                    >
                </Button>
            </div>
        </div>

        <div
            v-if="!contratoVigente"
            class="rv flex flex-wrap items-center justify-between gap-3 rounded-[18px] border border-danger/40 bg-danger-bg px-5 py-4 text-sm"
            style="--i: 2"
        >
            <p>
                <strong>Este empleado no tiene contrato vigente</strong> y no se
                incluirá en la liquidación de la nómina.
            </p>
        </div>

        <section
            class="rv rounded-[22px] border bg-card px-7 py-6"
            style="--i: 2"
        >
            <dl
                class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-5"
            >
                <div v-for="dato in datos" :key="dato.etiqueta">
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        {{ dato.etiqueta }}
                    </dt>
                    <dd
                        class="mt-1 text-base leading-[normal] font-semibold wrap-anywhere"
                    >
                        {{ dato.valor || '—' }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rv flex flex-col gap-3.5" style="--i: 3">
            <div class="flex flex-wrap items-baseline gap-x-3">
                <h2 class="font-heading text-lg leading-[normal] font-bold">
                    Contratos
                </h2>
                <span class="text-sm text-muted-foreground"
                    >El más reciente primero</span
                >
            </div>
            <PanelTabla
                v-if="contratos.length"
                titulo="Contratos"
                sin-animacion
                ocultar-titulo
            >
                <TablaContratos :contratos="contratos" />
            </PanelTabla>
            <p v-else class="text-sm text-muted-foreground">
                El empleado no tiene contratos registrados.
            </p>
        </section>

        <section class="rv flex flex-col gap-3.5" style="--i: 4">
            <div class="flex flex-col gap-1">
                <h2 class="font-heading text-lg leading-[normal] font-bold">
                    Vacaciones
                </h2>
                <p class="text-sm text-muted-foreground">
                    Cálculo orientativo según los contratos, las novedades y la
                    referencia histórica registrada. No certifica el saldo
                    histórico.
                </p>
            </div>

            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <p>
                    Fecha de cálculo del saldo:
                    <time :datetime="saldoVacaciones.fechaCorte">
                        {{ formatearFecha(saldoVacaciones.fechaCorte) }}
                    </time>
                </p>
                <p v-if="saldoVacaciones.fechaIngreso">
                    Primer ingreso registrado:
                    <time :datetime="saldoVacaciones.fechaIngreso">
                        {{ formatearFecha(saldoVacaciones.fechaIngreso) }}
                    </time>
                </p>
            </div>

            <dl
                class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-4"
            >
                <div
                    v-for="saldo in diasVacaciones"
                    :key="saldo.etiqueta"
                    class="lift rounded-[18px] border bg-card p-5"
                >
                    <dt class="text-sm leading-[normal] text-muted-foreground">
                        {{ saldo.etiqueta }}
                    </dt>
                    <dd
                        class="mt-1.5 font-heading text-[28px] leading-[normal] font-bold wrap-anywhere"
                    >
                        {{ formatoDias.format(Number(saldo.valor)) }}
                        <span
                            class="font-sans text-sm font-normal text-muted-foreground"
                            >días</span
                        >
                    </dd>
                </div>
            </dl>

            <p
                v-if="!saldoVacaciones.fechaIngreso"
                class="text-sm text-muted-foreground"
            >
                No hay contratos con fechas válidas de servicio hasta la fecha
                de cálculo. No se puede establecer la antigüedad del empleado.
            </p>

            <p class="text-sm text-muted-foreground">
                {{ saldoVacaciones.criterioCalendario }}
            </p>

            <SaldoInicialVacaciones
                :empleado="empleado"
                :fecha-maxima="saldoVacaciones.fechaCorte"
            />

            <ul
                v-if="saldoVacaciones.advertencias.length"
                class="list-disc space-y-2 rounded-[18px] border bg-card py-4 pr-5 pl-9 text-sm"
                aria-label="Limitaciones del cálculo de vacaciones"
            >
                <li
                    v-for="advertencia in saldoVacaciones.advertencias"
                    :key="advertencia"
                >
                    {{ advertencia }}
                </li>
            </ul>
        </section>

        <ConceptosRecurrentes
            :empleado-id="empleado.id"
            :asignaciones="asignaciones"
            :conceptos-disponibles="conceptosDisponibles"
        />
    </PaginaPos>
</template>
