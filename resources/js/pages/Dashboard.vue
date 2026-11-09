<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ChartColumn, Compass } from '@lucide/vue';
import { computed } from 'vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const page = usePage();
const nombre = computed(() => page.props.auth.user.name);
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

        <div
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]"
        >
            <section
                class="overflow-hidden rounded-xl border bg-card shadow-sm"
                aria-labelledby="resumen-title"
            >
                <header class="border-b px-6 py-5">
                    <h2 id="resumen-title" class="text-lg font-semibold">
                        Resumen de operaciones
                    </h2>
                </header>
                <div
                    class="flex min-h-64 flex-col items-center justify-center px-6 py-10 text-center"
                >
                    <span
                        class="mb-5 flex size-14 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground"
                    >
                        <ChartColumn class="size-6" aria-hidden="true" />
                    </span>
                    <h3 class="text-base font-semibold">
                        Sin indicadores disponibles
                    </h3>
                    <p
                        class="mt-2 max-w-md text-sm leading-relaxed text-muted-foreground"
                    >
                        Los indicadores generales aún no están disponibles en
                        este inicio. Puedes consultar los registros en los
                        módulos a los que tienes acceso.
                    </p>
                </div>
            </section>

            <aside
                class="rounded-xl border border-brand-gold/40 bg-accent/50 p-6"
                aria-labelledby="espacio-title"
            >
                <Compass class="mb-4 size-6 text-primary" aria-hidden="true" />
                <h2 id="espacio-title" class="text-lg font-semibold">
                    Tu espacio de trabajo
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                    El menú de navegación reúne los módulos disponibles para tu
                    cuenta. Selecciona uno para continuar con tus operaciones.
                </p>
            </aside>
        </div>
    </div>
</template>
