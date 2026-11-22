<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import AreaController from '@/actions/App/Http/Controllers/Nomina/AreaController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Area } from '@/types';

defineProps<{
    area: Area;
}>();
</script>

<template>
    <tr>
        <td class="!py-3.5">
            <Form
                :id="`area-${area.id}`"
                v-bind="AreaController.update.form(area.id)"
                class="flex flex-col gap-1"
                v-slot="{ errors }"
            >
                <input
                    type="hidden"
                    name="es_activa"
                    :value="area.es_activa ? 1 : 0"
                />
                <Input
                    name="nombre"
                    class="campo-sicop max-w-[320px] [--alto-campo:42px] [--ancho-campo:auto]"
                    :default-value="area.nombre"
                    required
                    aria-label="Nombre del área"
                />
                <InputError :message="errors.nombre" />
            </Form>
        </td>
        <td>
            <span
                class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                :class="
                    area.es_activa
                        ? 'bg-ok-bg text-success'
                        : 'bg-muted text-muted-foreground'
                "
                >{{ area.es_activa ? 'Activa' : 'Inactiva' }}</span
            >
        </td>
        <td class="!py-3.5 whitespace-nowrap">
            <div class="flex items-start gap-2">
                <Button
                    type="submit"
                    :form="`area-${area.id}`"
                    class="boton-plano min-h-[38px] rounded-[10px] px-4 text-[13px] font-bold hover:bg-primary"
                    >Guardar</Button
                >
                <Form
                    v-bind="AreaController.update.form(area.id)"
                    v-slot="{ processing }"
                >
                    <input type="hidden" name="nombre" :value="area.nombre" />
                    <input
                        type="hidden"
                        name="es_activa"
                        :value="area.es_activa ? 0 : 1"
                    />
                    <Button
                        variant="outline"
                        :disabled="processing"
                        class="boton-plano min-h-[38px] rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                    >
                        {{ area.es_activa ? 'Desactivar' : 'Activar' }}
                    </Button>
                </Form>
            </div>
        </td>
    </tr>
</template>
