<script setup lang="ts">
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Banknote,
    CarFront,
    ClipboardList,
    GraduationCap,
    HandCoins,
    ShieldCheck,
    Store,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import ContadorAnimado from '@/components/ContadorAnimado.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { formatearPesos } from '@/lib/formato';
import { dashboard } from '@/routes';
import rutasAdmin from '@/routes/admin';
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
        ...meses.flatMap((mes) => [Number(mes.cursos), Number(mes.soats)]),
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

const tarjetasPos = computed(() =>
    props.pos
        ? [
              {
                  titulo: 'Cursos del mes',
                  icono: GraduationCap,
                  href: rutasPos.cursos.index(),
                  valor: props.pos.cursos.cantidad,
                  detalleValor: props.pos.cursos.valor,
              },
              {
                  titulo: 'SOAT del mes',
                  icono: CarFront,
                  href: rutasPos.soats.index(),
                  valor: props.pos.soats.cantidad,
                  detalleValor: props.pos.soats.valor,
              },
              {
                  titulo: 'Cierres de caja del mes',
                  icono: Wallet,
                  href: rutasPos.cierres.index(),
                  valor: props.pos.cierres_cerrados_mes,
                  prefijo: 'Ventas: ',
                  detalleValor: totalVentasMes.value,
              },
              {
                  titulo: 'Saldo por consignar',
                  icono: HandCoins,
                  href: rutasPos.consignaciones.index(),
                  valor: props.pos.saldo_por_consignar,
                  moneda: true,
                  detalle: `${props.pos.dias_sin_consignar} ${
                      props.pos.dias_sin_consignar === 1
                          ? 'día sin consignar'
                          : 'días sin consignar'
                  }`,
              },
          ]
        : [],
);

const tarjetasNomina = computed(() =>
    props.nomina
        ? [
              {
                  titulo: 'Empleados activos',
                  icono: Users,
                  href: rutasNomina.empleados.index(),
                  valor: props.nomina.empleados_activos,
              },
              {
                  titulo: 'Periodos sin cerrar',
                  icono: Banknote,
                  href: rutasNomina.periodos.index(),
                  valor: props.nomina.periodos_abiertos,
              },
              {
                  titulo: 'Novedades sin liquidar',
                  icono: ClipboardList,
                  href: rutasNomina.novedades.index(),
                  valor: props.nomina.novedades_sin_liquidar,
              },
          ]
        : [],
);

const permisos = computed(() => page.props.auth.permisos);

const accesoPrincipal = computed(() => {
    if (props.pos) {
        return { texto: 'Ir al punto de venta', href: rutasPos.inicio.index() };
    }

    return props.nomina
        ? { texto: 'Ir a nómina', href: rutasNomina.inicio.index() }
        : null;
});

const modulos = computed(() => [
    ...(permisos.value.usarPos
        ? [
              {
                  titulo: 'Punto de venta',
                  icono: Store,
                  href: rutasPos.inicio.index(),
              },
          ]
        : []),
    ...(permisos.value.gestionarNomina
        ? [
              {
                  titulo: 'Nómina',
                  icono: Wallet,
                  href: rutasNomina.inicio.index(),
              },
          ]
        : []),
    ...(permisos.value.verAuditoria
        ? [
              {
                  titulo: 'Auditoría',
                  icono: ShieldCheck,
                  href: rutasAdmin.auditoria.index(),
              },
          ]
        : []),
    ...(permisos.value.gestionarUsuarios
        ? [
              {
                  titulo: 'Usuarios',
                  icono: Users,
                  href: rutasAdmin.usuarios.index(),
              },
          ]
        : []),
]);
</script>

<template>
    <Head title="Inicio" />

    <div
        class="mx-auto flex w-full max-w-[1280px] flex-1 flex-col gap-7 px-4 pt-7 pb-12 md:px-10"
    >
        <section
            class="rv relative flex min-h-60 items-center overflow-hidden rounded-3xl bg-linear-to-br from-brand-navy to-[#1f4575] p-8 text-brand-cream md:p-11"
            style="--i: 1"
        >
            <div class="relative z-10 flex max-w-xl flex-col gap-3">
                <p
                    class="text-[13px] font-bold tracking-[0.16em] text-brand-gold uppercase"
                >
                    SICOP · Inicio
                </p>
                <h1
                    class="font-heading text-3xl leading-[1.12] font-extrabold break-words md:text-[40px]"
                >
                    Hola, {{ nombre }}
                </h1>
                <p class="text-[17px] leading-[1.6] text-brand-cream/[.86]">
                    Bienvenido al Sistema Integral COVIACOL de Operaciones.
                </p>
                <Button
                    v-if="accesoPrincipal"
                    as-child
                    size="lg"
                    class="boton-inicio mt-2.5 h-12 gap-2.5 self-start rounded-full bg-brand-gold px-6 text-[15px] font-bold text-brand-navy hover:bg-brand-gold"
                >
                    <Link :href="accesoPrincipal.href">
                        {{ accesoPrincipal.texto }}
                        <span class="go inline-flex"
                            ><ArrowRight
                                class="size-[18px]"
                                :stroke-width="2.2"
                                aria-hidden="true"
                        /></span>
                    </Link>
                </Button>
            </div>
            <svg
                class="absolute top-1/2 right-12 hidden -translate-y-1/2 opacity-95 min-[861px]:block"
                width="320"
                height="320"
                viewBox="0 0 300 300"
                fill="none"
                aria-hidden="true"
            >
                <circle
                    cx="150"
                    cy="150"
                    r="128"
                    stroke="#9cadc4"
                    stroke-opacity=".22"
                />
                <circle
                    cx="150"
                    cy="150"
                    r="84"
                    stroke="#9cadc4"
                    stroke-opacity=".32"
                />
                <g
                    stroke="#9cadc4"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-opacity=".7"
                >
                    <path d="M150 66V150" />
                    <path d="M66 150H150" />
                    <path d="M150 150H234" />
                    <path d="M150 150V234" />
                </g>
                <g fill="#ecc254">
                    <circle class="flota" cx="150" cy="62" r="15" />
                    <circle
                        class="flota"
                        style="animation-delay: -1.5s"
                        cx="62"
                        cy="150"
                        r="15"
                    />
                    <circle
                        class="flota"
                        style="animation-delay: -3s"
                        cx="238"
                        cy="150"
                        r="15"
                    />
                    <circle
                        class="flota"
                        style="animation-delay: -4.5s"
                        cx="150"
                        cy="238"
                        r="15"
                    />
                </g>
                <circle
                    cx="150"
                    cy="150"
                    r="22"
                    fill="#0d2038"
                    stroke="#f3eedd"
                    stroke-width="3"
                />
            </svg>
        </section>

        <p
            v-if="sinModulos"
            class="rv rounded-2xl border bg-card p-6 text-sm leading-relaxed text-muted-foreground"
            style="--i: 2"
        >
            Tu cuenta no tiene indicadores en este inicio. Usa el menú de
            navegación para consultar los módulos a los que tienes acceso.
        </p>

        <section
            v-if="pos"
            class="flex flex-col gap-4"
            aria-labelledby="indicadores-pos-title"
        >
            <div
                class="rv flex flex-wrap items-baseline gap-x-3"
                style="--i: 2"
            >
                <h2
                    id="indicadores-pos-title"
                    class="font-heading text-xl font-bold"
                >
                    Resumen de operaciones
                </h2>
                <p class="text-sm text-muted-foreground first-letter:uppercase">
                    {{ mesActual }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="(tarjeta, indice) in tarjetasPos"
                    :key="tarjeta.titulo"
                    :href="tarjeta.href"
                    class="lift rv flex flex-col gap-4 rounded-[18px] border bg-card p-[22px]"
                    :style="{ '--i': indice + 3 }"
                >
                    <span
                        class="bub flex size-11 items-center justify-center rounded-xl bg-secondary text-foreground"
                    >
                        <component
                            :is="tarjeta.icono"
                            class="size-[22px]"
                            aria-hidden="true"
                        />
                    </span>
                    <div>
                        <p class="text-sm text-muted-foreground">
                            {{ tarjeta.titulo }}
                        </p>
                        <p
                            class="mt-1 font-heading text-[34px] leading-[normal] font-bold"
                        >
                            <ContadorAnimado
                                :valor="tarjeta.valor"
                                :moneda="tarjeta.moneda"
                            />
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ tarjeta.prefijo }}
                            <ContadorAnimado
                                v-if="tarjeta.detalleValor !== undefined"
                                :valor="tarjeta.detalleValor"
                                moneda
                            />
                            <template v-else>{{ tarjeta.detalle }}</template>
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <div class="flex flex-wrap items-stretch gap-6">
            <Card
                v-if="pos"
                class="rv min-w-0 flex-[2_1_520px] gap-6 rounded-[20px] p-7"
                style="--i: 7"
                aria-labelledby="grafica-ventas-title"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2
                        id="grafica-ventas-title"
                        class="font-heading text-lg font-bold"
                    >
                        Ventas de los últimos 6 meses
                    </h2>
                    <ul
                        class="flex gap-[18px] text-[13px] text-muted-foreground"
                        aria-hidden="true"
                    >
                        <li class="inline-flex items-center gap-2">
                            <span class="size-3 rounded bg-bar-a" />
                            Cursos
                        </li>
                        <li class="inline-flex items-center gap-2">
                            <span class="size-3 rounded bg-bar-b" />
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
                        class="relative flex h-60 items-end justify-around gap-3 border-b px-2"
                        style="
                            background: repeating-linear-gradient(
                                to top,
                                transparent 0,
                                transparent 59px,
                                var(--line-faint) 59px,
                                var(--line-faint) 60px
                            );
                        "
                        aria-hidden="true"
                    >
                        <div
                            v-for="(mes, indice) in tendencia"
                            :key="mes.etiqueta"
                            class="flex h-full items-end gap-1.5"
                            :title="`${mes.etiqueta}: ${formatearPesos(mes.total)}`"
                        >
                            <div
                                class="bar-grow w-4 rounded-t-lg bg-bar-a sm:w-6"
                                :style="{
                                    '--i': indice * 2,
                                    height: `${mes.alturaCursos}%`,
                                }"
                            />
                            <div
                                class="bar-grow w-4 rounded-t-lg bg-bar-b sm:w-6"
                                :style="{
                                    '--i': indice * 2 + 1,
                                    height: `${mes.alturaSoats}%`,
                                }"
                            />
                        </div>
                    </div>
                    <div
                        class="-mt-3 flex justify-around gap-3 text-[13px] text-muted-foreground capitalize"
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
            </Card>

            <aside
                v-if="modulos.length > 0"
                class="rv flex min-w-0 flex-[1_1_320px] flex-col gap-[18px] rounded-[20px] border border-brand-gold/55 bg-cream p-7"
                style="--i: 8"
                aria-labelledby="espacio-title"
            >
                <div>
                    <h2
                        id="espacio-title"
                        class="font-heading text-lg font-bold"
                    >
                        Tu espacio de trabajo
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        El menú reúne los módulos disponibles para tu cuenta.
                        Selecciona uno para continuar.
                    </p>
                </div>
                <div class="flex flex-col gap-2.5">
                    <Link
                        v-for="modulo in modulos"
                        :key="modulo.titulo"
                        :href="modulo.href"
                        class="lift flex items-center gap-3.5 rounded-[14px] border border-line-cream bg-card px-4 py-3.5"
                    >
                        <span
                            class="bub flex size-10 items-center justify-center rounded-[10px] bg-secondary text-foreground"
                        >
                            <component
                                :is="modulo.icono"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </span>
                        <span class="flex-1 text-[15px] font-semibold">
                            {{ modulo.titulo }}
                        </span>
                        <ArrowUpRight
                            class="arr size-[18px] text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </aside>
        </div>

        <section
            v-if="nomina"
            class="flex flex-col gap-4"
            aria-labelledby="indicadores-nomina-title"
        >
            <h2
                id="indicadores-nomina-title"
                class="rv font-heading text-xl font-bold"
                style="--i: 9"
            >
                Nómina
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <Link
                    v-for="(tarjeta, indice) in tarjetasNomina"
                    :key="tarjeta.titulo"
                    :href="tarjeta.href"
                    class="lift rv flex flex-col gap-4 rounded-[18px] border bg-card p-[22px]"
                    :style="{ '--i': indice + 10 }"
                >
                    <span
                        class="bub flex size-11 items-center justify-center rounded-xl bg-secondary text-foreground"
                    >
                        <component
                            :is="tarjeta.icono"
                            class="size-[22px]"
                            aria-hidden="true"
                        />
                    </span>
                    <div>
                        <p class="text-sm text-muted-foreground">
                            {{ tarjeta.titulo }}
                        </p>
                        <p
                            class="mt-1 font-heading text-[34px] leading-[normal] font-bold"
                        >
                            <ContadorAnimado :valor="tarjeta.valor" />
                        </p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
