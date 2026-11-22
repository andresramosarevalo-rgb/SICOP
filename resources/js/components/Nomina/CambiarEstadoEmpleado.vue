<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
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
        class="flex flex-wrap items-center gap-3"
        v-slot="{ processing }"
    >
        <span
            class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
            :class="
                empleado.es_activo
                    ? 'bg-ok-bg text-success'
                    : 'bg-muted text-muted-foreground'
            "
        >
            {{ empleado.es_activo ? 'Activo' : 'Inactivo' }}
        </span>
        <input
            type="hidden"
            name="es_activo"
            :value="empleado.es_activo ? 0 : 1"
        />
        <Button
            variant="outline"
            :disabled="processing"
            class="boton-plano min-h-9 rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
        >
            {{ empleado.es_activo ? 'Desactivar' : 'Activar' }}
        </Button>
    </Form>
</template>
