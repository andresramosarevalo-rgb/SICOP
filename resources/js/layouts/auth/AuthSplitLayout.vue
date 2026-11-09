<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="sicop-auth grid min-h-svh bg-background lg:grid-cols-[0.48fr_0.52fr]"
    >
        <aside
            class="relative isolate flex overflow-hidden bg-brand-navy px-6 py-8 text-white sm:px-10 lg:items-start lg:px-16 lg:pt-[16vh] lg:pb-20"
        >
            <div class="relative z-10 mx-auto w-full max-w-sm">
                <Link
                    :href="home()"
                    aria-label="SICOP — Inicio"
                    class="inline-flex rounded-full focus-visible:outline-2 focus-visible:outline-offset-8 focus-visible:outline-brand-gold"
                >
                    <AppLogoIcon class="sm:w-32 lg:w-36" />
                </Link>

                <div class="mt-5 space-y-3 lg:mt-8 lg:space-y-5">
                    <p
                        class="font-heading text-sm font-semibold tracking-wide text-brand-cream"
                    >
                        SICOP
                    </p>
                    <p
                        class="font-heading text-2xl leading-tight font-semibold text-brand-gold sm:text-3xl lg:text-4xl"
                    >
                        Operaciones conectadas.
                    </p>
                    <p
                        class="max-w-xs text-sm leading-relaxed text-white/85 lg:text-base"
                    >
                        Sistema Integral COVIACOL de Operaciones
                    </p>
                    <p
                        class="hidden max-w-xs text-sm leading-relaxed text-white/75 lg:block"
                    >
                        Punto de venta, nómina y auditoría en una sola
                        plataforma.
                    </p>
                </div>
            </div>
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -right-36 -bottom-40 -z-10 hidden size-96 rounded-full border border-brand-gold/30 lg:block"
            >
                <div
                    class="absolute inset-7 rounded-full border-[18px] border-brand-cream/10"
                />
                <div
                    class="absolute inset-20 rounded-full border border-brand-gold/20"
                />
            </div>
        </aside>

        <main
            class="flex min-w-0 items-center justify-center px-4 py-8 sm:px-8 sm:py-12 lg:px-12"
        >
            <section
                class="w-full max-w-[420px] rounded-2xl border border-border bg-card px-6 py-8 shadow-[0_12px_40px_-16px_rgba(21,51,89,0.2)] sm:px-9 sm:py-10"
                aria-labelledby="auth-title"
            >
                <header class="mb-8 space-y-3">
                    <h1
                        v-if="title"
                        id="auth-title"
                        class="font-heading text-2xl leading-tight font-semibold text-brand-navy sm:text-3xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </header>
                <slot />
            </section>
        </main>
    </div>
</template>
