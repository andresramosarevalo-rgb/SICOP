<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShieldCheck, Store, Wallet } from '@lucide/vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="sicop-auth flex min-h-svh flex-wrap bg-background">
        <aside
            class="relative isolate flex flex-[0.48_1_440px] overflow-hidden bg-linear-to-br from-brand-navy to-brand-deep px-6 py-8 text-brand-cream sm:px-10 lg:items-center lg:px-16 lg:py-14"
        >
            <div
                class="deriva pointer-events-none absolute -top-24 -right-28 -z-10 size-[420px] rounded-full"
                style="
                    background: radial-gradient(
                        circle,
                        rgb(236 194 84 / 0.18) 0%,
                        rgb(236 194 84 / 0) 70%
                    );
                "
                aria-hidden="true"
            />
            <div
                class="deriva pointer-events-none absolute -bottom-20 -left-28 -z-10 size-[360px] rounded-full"
                style="
                    animation-delay: -6s;
                    background: radial-gradient(
                        circle,
                        rgb(156 173 196 / 0.2) 0%,
                        rgb(156 173 196 / 0) 70%
                    );
                "
                aria-hidden="true"
            />
            <svg
                class="pointer-events-none absolute right-[-60px] bottom-16 -z-10 hidden opacity-50 lg:block"
                width="260"
                height="260"
                viewBox="0 0 300 300"
                fill="none"
                aria-hidden="true"
            >
                <circle
                    cx="150"
                    cy="150"
                    r="128"
                    stroke="#9cadc4"
                    stroke-opacity=".3"
                />
                <circle
                    cx="150"
                    cy="150"
                    r="84"
                    stroke="#9cadc4"
                    stroke-opacity=".4"
                />
                <g fill="#ecc254">
                    <circle class="flota-acceso" cx="150" cy="22" r="9" />
                    <circle
                        class="flota-acceso"
                        style="animation-delay: -2s"
                        cx="234"
                        cy="150"
                        r="9"
                    />
                    <circle
                        class="flota-acceso"
                        style="animation-delay: -4s"
                        cx="66"
                        cy="150"
                        r="9"
                    />
                </g>
            </svg>

            <div class="relative z-10 mx-auto w-full max-w-[460px]">
                <Link
                    :href="home()"
                    aria-label="SICOP — Inicio"
                    class="rv-acceso inline-flex rounded-full focus-visible:outline-2 focus-visible:outline-offset-8 focus-visible:outline-brand-gold"
                    style="--i: 0"
                >
                    <svg
                        width="132"
                        height="132"
                        viewBox="0 0 100 100"
                        role="img"
                        aria-label="Logotipo de SICOP"
                    >
                        <circle cx="50" cy="50" r="44" fill="#F6EFDB" />
                        <g
                            stroke="#7C94AE"
                            stroke-width="5"
                            stroke-linecap="round"
                        >
                            <path d="M50 25V50" />
                            <path d="M25 50H50" />
                            <path d="M50 50H75" />
                            <path d="M50 50V75" />
                        </g>
                        <g fill="#E8B534">
                            <circle
                                class="flota-acceso"
                                cx="50"
                                cy="24"
                                r="9"
                            />
                            <circle
                                class="flota-acceso"
                                style="animation-delay: -1.5s"
                                cx="24"
                                cy="50"
                                r="9"
                            />
                            <circle
                                class="flota-acceso"
                                style="animation-delay: -3s"
                                cx="76"
                                cy="50"
                                r="9"
                            />
                            <circle
                                class="flota-acceso"
                                style="animation-delay: -4.5s"
                                cx="50"
                                cy="76"
                                r="9"
                            />
                        </g>
                        <circle cx="50" cy="50" r="11" fill="#173456" />
                    </svg>
                </Link>

                <div class="mt-6">
                    <p
                        class="rv-acceso font-heading text-3xl leading-[1.1] font-extrabold tracking-[0.08em] text-brand-cream lg:text-[38px]"
                        style="--i: 1"
                    >
                        SICOP
                    </p>
                    <p
                        class="rv-acceso mt-2.5 font-heading text-3xl leading-[1.15] font-bold text-brand-gold lg:text-4xl"
                        style="--i: 2"
                    >
                        Operaciones conectadas.
                    </p>
                    <p
                        class="rv-acceso mt-5 text-lg leading-[1.6] text-white/92"
                        style="--i: 3"
                    >
                        Sistema Integral COVIACOL de Operaciones
                    </p>
                    <p
                        class="rv-acceso mt-2.5 hidden text-base leading-[1.6] text-brand-cream/78 lg:block"
                        style="--i: 3"
                    >
                        Punto de venta, nómina y auditoría en una sola
                        plataforma.
                    </p>
                </div>

                <ul
                    aria-label="Módulos de SICOP"
                    class="rv-acceso mt-9 grid grid-cols-3 gap-3"
                    style="--i: 4"
                >
                    <li
                        v-for="modulo in [
                            { nombre: 'POS', icono: Store },
                            { nombre: 'Nómina', icono: Wallet },
                            { nombre: 'Auditoría', icono: ShieldCheck },
                        ]"
                        :key="modulo.nombre"
                        class="chip flex min-w-0 flex-col items-center justify-center gap-2.5 rounded-2xl border border-white/14 bg-white/7 px-2 py-[18px]"
                    >
                        <component
                            :is="modulo.icono"
                            aria-hidden="true"
                            class="size-6 shrink-0 text-brand-gold"
                            :stroke-width="1.8"
                        />
                        <span class="text-sm leading-[normal] font-semibold">
                            {{ modulo.nombre }}
                        </span>
                    </li>
                </ul>
            </div>
        </aside>

        <main
            class="flex min-w-0 flex-[0.52_1_440px] items-center justify-center px-4 py-8 sm:px-8 sm:py-12"
        >
            <section
                class="rv-acceso flex w-full max-w-[452px] flex-col gap-7 rounded-[28px] border border-border bg-card px-6 py-8 shadow-[0_30px_60px_-36px_var(--shadow-card)] sm:px-10 sm:py-11"
                style="--i: 2"
                aria-labelledby="auth-title"
            >
                <header class="space-y-2">
                    <h1
                        v-if="title"
                        id="auth-title"
                        class="font-heading text-2xl leading-[1.15] font-extrabold text-foreground sm:text-[30px]"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-base leading-normal text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </header>
                <slot />
            </section>
        </main>
    </div>
</template>
