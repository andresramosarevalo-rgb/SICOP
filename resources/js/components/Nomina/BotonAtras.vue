<script setup lang="ts">
import { Link, type InertiaLinkProps } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    /** Página superior del módulo; independiente del historial de interacciones. */
    respaldo: NonNullable<InertiaLinkProps['href']>;
}>();
</script>

<template>
    <div class="print:hidden">
        <Button variant="ghost" size="sm" class="-ml-2" as-child>
            <Link :href="respaldo">
                <ArrowLeft aria-hidden="true" />
                Atrás
            </Link>
        </Button>
    </div>
</template>
