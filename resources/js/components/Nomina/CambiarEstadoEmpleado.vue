<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Empleado } from '@/types';

defineProps<{
    empleado: Pick<Empleado, 'id' | 'es_activo'>;
}>();
</script>

<template>
    <Form
        v-bind="EmpleadoController.cambiarEstado.form(empleado.id)"
        :options="{ preserveScroll: true }"
        class="flex items-center gap-3"
        v-slot="{ processing }"
    >
        <Badge :variant="empleado.es_activo ? 'secondary' : 'outline'">
            {{ empleado.es_activo ? 'Activo' : 'Inactivo' }}
        </Badge>
        <input
            type="hidden"
            name="es_activo"
            :value="empleado.es_activo ? 0 : 1"
        />
        <Button variant="outline" :disabled="processing">
            {{ empleado.es_activo ? 'Desactivar' : 'Activar' }}
        </Button>
    </Form>
</template>
