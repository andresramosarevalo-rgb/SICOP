<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AreaController from '@/actions/App/Http/Controllers/Nomina/AreaController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import nomina from '@/routes/nomina';
import type { Area } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Áreas', href: nomina.areas.index() },
        ],
    },
});

defineProps<{
    areas: Area[];
}>();
</script>

<template>
    <Head title="Áreas" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Áreas"
            description="Departamentos a los que pertenecen los empleados. Un área inactiva no se ofrece al registrar empleados."
        />

        <Form
            v-bind="AreaController.store.form()"
            class="flex flex-wrap items-end gap-2"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="grid flex-1 gap-2">
                <Label for="nombre">Nueva área</Label>
                <Input
                    id="nombre"
                    name="nombre"
                    required
                    placeholder="Ej. Atención al cliente"
                />
                <InputError :message="errors.nombre" />
            </div>
            <Button :disabled="processing">Agregar</Button>
        </Form>

        <ul v-if="areas.length" class="divide-y rounded-xl border px-4">
            <li
                v-for="area in areas"
                :key="area.id"
                class="flex items-center justify-between gap-3 py-3"
            >
                <span class="font-medium">{{ area.nombre }}</span>
                <Badge :variant="area.es_activa ? 'secondary' : 'outline'">
                    {{ area.es_activa ? 'Activa' : 'Inactiva' }}
                </Badge>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">
            Todavía no hay áreas registradas.
        </p>
    </div>
</template>
