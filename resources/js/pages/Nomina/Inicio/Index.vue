<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import nomina from '@/routes/nomina';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Nómina',
                href: nomina.inicio.index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Nómina" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <Heading
            title="Nómina"
            description="Expedientes de trabajadores, novedades y liquidación de la nómina."
        />
    </div>
</template>
