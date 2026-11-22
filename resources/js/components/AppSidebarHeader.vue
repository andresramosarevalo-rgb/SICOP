<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="mx-auto flex w-full max-w-[1280px] items-center gap-3 px-4 pt-7 md:px-10"
    >
        <SidebarTrigger class="-ml-1 md:hidden" />
        <nav
            aria-label="Ruta"
            class="rv flex min-w-0 items-center gap-2 text-sm leading-[normal] text-muted-foreground"
            style="--i: 0"
        >
            <span>SICOP</span>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <ChevronRight class="size-3.5 shrink-0" aria-hidden="true" />
                <span
                    v-if="index === breadcrumbs.length - 1"
                    aria-current="page"
                    class="truncate font-semibold text-foreground"
                    >{{ item.title }}</span
                >
                <Link v-else :href="item.href" class="hover:text-foreground">{{
                    item.title
                }}</Link>
            </template>
        </nav>
    </header>
</template>
