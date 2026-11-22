<script setup lang="ts">
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import {
    Banknote,
    CarFront,
    ClipboardList,
    GraduationCap,
    HandCoins,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import ContadorAnimado from '@/components/ContadorAnimado.vue';
import { formatearPesos } from '@/lib/formato';
import { dashboard } from '@/routes';
import rutasNomina from '@/routes/nomina';
import rutasPos from '@/routes/pos';

type Venta = { cantidad: number; valor: string };
type MesTendencia = { mes: string; cursos: string; soats: string };

type IndicadoresPos = {
    mes: string;
    cursos: Venta;
    soats: Venta;
    cierres_cerrados_mes: number;
    saldo_por_consignar: string;
    dias_sin_consignar: number;
    tendencia: MesTendencia[];
};

type IndicadoresNomina = {
    empleados_activos: number;
    periodos_abiertos: number;
    novedades_sin_liquidar: number;
};

const props = defineProps<{
    pos: IndicadoresPos | null;
    nomina: IndicadoresNomina | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const page = usePage();

/**
 * Los indicadores se piden al servidor cada pocos segundos mientras la pestaña está visible,
 * así los registros nuevos aparecen sin recargar la página.
 */
usePoll(5000, { only: ['pos', 'nomina'] });

const barrasVisibles = ref(false);
onMounted(() => requestAnimationFrame(() => (barrasVisibles.value = true)));
const nombre = computed(() => page.props.auth.user.name);

const formatoMes = new Intl.DateTimeFormat('es-CO', {
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
});
const formatoMesCorto = new Intl.DateTimeFormat('es-CO', {
    month: 'short',
    timeZone: 'UTC',
});

const mesActual = computed(() =>
    props.pos ? formatoMes.format(new Date(props.pos.mes)) : '',
);

const totalVentasMes = computed(() =>
    props.pos
        ? Number(props.pos.cursos.valor) + Number(props.pos.soats.valor)
        : 0,
);

const tendencia = computed(() => {
    const meses = props.pos?.tendencia ?? [];
    const maximo = Math.max(
        1,
        ...meses.map((mes) => Number(mes.cursos) + Number(mes.soats)),
    );

    return meses.map((mes) => ({
        etiqueta: formatoMesCorto.format(new Date(mes.mes)),
        cursos: Number(mes.cursos),
        soats: Number(mes.soats),
        total: Number(mes.cursos) + Number(mes.soats),
        alturaCursos: (Number(mes.cursos) / maximo) * 100,
        alturaSoats: (Number(mes.soats) / maximo) * 100,
    }));
});

const hayVentasEnLaTendencia = computed(() =>
    tendencia.value.some((mes) => mes.total > 0),
);

const sinModulos = computed(() => props.pos === null && props.nomina === null);
</script>

<template>
    <Head title="Inicio" />

    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <header class="space-y-2 border-b border-border pb-6">
            <p class="text-sm font-medium text-muted-foreground">
                SICOP · Inicio
            </p>
            <h1
                class="text-2xl leading-tight font-semibold break-words sm:text-3xl"
            >
                Hola, {{ nombre }}
            </h1>
            <p class="text-sm leading-relaxed text-muted-foreground">
                Bienvenido al Sistema Integral COVIACOL de Operaciones.
            </p>
        </header>

        <p
            v-if="sinModulos"
            class="rounded-xl border bg-card p-6 text-sm leading-relaxed text-muted-foreground"
        >
            Tu cuenta no tiene indicadores en este inicio. Usa el menú de
            navegación para consultar los módulos a los que tienes acceso.
        </p>

        <section
            v-if="pos"
            class="space-y-4"
            aria-labelledby="indicadores-pos-title"
        >
            <div>
                <h2 id="indicadores-pos-title" class="text-lg font-semibold">
                    Punto de venta
                </h2>
                <p class="text-sm text-muted-foreground first-letter:uppercase">
                    {{ mesActual }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Link
                    :href="rutasPos.cursos.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <GraduationCap
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">Cursos del mes</p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado :valor="pos.cursos.cantidad" />
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <ContadorAnimado :valor="pos.cursos.valor" moneda />
                    </p>
                </Link>

                <Link
                    :href="rutasPos.soats.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <CarFront
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">SOAT del mes</p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado :valor="pos.soats.cantidad" />
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <ContadorAnimado :valor="pos.soats.valor" moneda />
                    </p>
                </Link>

                <Link
                    :href="rutasPos.cierres.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <Wallet
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">
                        Cierres de caja del mes
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado :valor="pos.cierres_cerrados_mes" />
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Ventas:
                        <ContadorAnimado :valor="totalVentasMes" moneda />
                    </p>
                </Link>

                <Link
                    :href="rutasPos.consignaciones.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <HandCoins
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">
                        Saldo por consignar
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado
                            :valor="pos.saldo_por_consignar"
                            moneda
                        />
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <ContadorAnimado :valor="pos.dias_sin_consignar" />
                        {{
                            pos.dias_sin_consignar === 1
                                ? 'día sin consignar'
                                : 'días sin consignar'
                        }}
                    </p>
                </Link>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-base font-semibold">
                        Ventas de los últimos 6 meses
                    </h3>
                    <ul
                        class="flex gap-4 text-xs text-muted-foreground"
                        aria-hidden="true"
                    >
                        <li class="flex items-center gap-1.5">
                            <span
                                class="size-2.5 rounded-sm bg-primary dark:bg-chart-3"
                            />
                            Cursos
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-sm bg-brand-gold" />
                            SOAT
                        </li>
                    </ul>
                </div>

                <p
                    v-if="!hayVentasEnLaTendencia"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    Aún no hay ventas registradas en estos meses.
                </p>

                <template v-else>
                    <div
                        class="mt-6 grid h-48 grid-cols-6 items-end gap-3 sm:gap-6"
                        aria-hidden="true"
                    >
                        <div
                            v-for="mes in tendencia"
                            :key="mes.etiqueta"
                            class="flex h-full flex-col justify-end"
                            :title="`${mes.etiqueta}: ${formatearPesos(mes.total)}`"
                        >
                            <div
                                class="w-full bg-brand-gold transition-[height] duration-1000 ease-out motion-reduce:transition-none"
                                :style="{
                                    height: `${barrasVisibles ? mes.alturaSoats : 0}%`,
                                }"
                            />
                            <div
                                class="w-full rounded-t-sm bg-primary transition-[height] duration-1000 ease-out motion-reduce:transition-none dark:bg-chart-3"
                                :style="{
                                    height: `${barrasVisibles ? mes.alturaCursos : 0}%`,
                                }"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-2 grid grid-cols-6 gap-3 text-center text-xs text-muted-foreground capitalize sm:gap-6"
                        aria-hidden="true"
                    >
                        <span v-for="mes in tendencia" :key="mes.etiqueta">
                            {{ mes.etiqueta }}
                        </span>
                    </div>

                    <table class="sr-only">
                        <caption>
                            Ventas de cursos y SOAT por mes
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Mes</th>
                                <th scope="col">Cursos</th>
                                <th scope="col">SOAT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="mes in tendencia" :key="mes.etiqueta">
                                <th scope="row">{{ mes.etiqueta }}</th>
                                <td>{{ formatearPesos(mes.cursos) }}</td>
                                <td>{{ formatearPesos(mes.soats) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </div>
        </section>

        <section
            v-if="nomina"
            class="space-y-4"
            aria-labelledby="indicadores-nomina-title"
        >
            <h2 id="indicadores-nomina-title" class="text-lg font-semibold">
                Nómina
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <Link
                    :href="rutasNomina.empleados.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <Users
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">
                        Empleados activos
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado :valor="nomina.empleados_activos" />
                    </p>
                </Link>

                <Link
                    :href="rutasNomina.periodos.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <Banknote
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">
                        Periodos sin cerrar
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado :valor="nomina.periodos_abiertos" />
                    </p>
                </Link>

                <Link
                    :href="rutasNomina.novedades.index()"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/50"
                >
                    <ClipboardList
                        class="mb-3 size-5 text-primary"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-muted-foreground">
                        Novedades sin liquidar
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        <ContadorAnimado
                            :valor="nomina.novedades_sin_liquidar"
                        />
                    </p>
                </Link>
            </div>
        </section>
    </div>
</template>
