<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    /** Página a la que se va si no hay historial, por ejemplo al abrir un enlace directo. */
    respaldo: NonNullable<InertiaLinkProps['href']>;
}>();

function volver(): void {
    if (window.history.length > 1) {
        window.history.back();

        return;
    }

    router.visit(props.respaldo);
}
</script>

<template>
    <div class="print:hidden">
        <Button variant="ghost" size="sm" class="-ml-2" @click="volver">
            <ArrowLeft />
            Atrás
        </Button>
    </div>
</template>
