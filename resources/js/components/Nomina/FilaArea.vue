<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import AreaController from '@/actions/App/Http/Controllers/Nomina/AreaController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Area } from '@/types';

defineProps<{
    area: Area;
}>();

const esEdicion = ref(false);
</script>

<template>
    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
        <Form
            v-if="esEdicion"
            v-bind="AreaController.update.form(area.id)"
            class="flex flex-1 flex-wrap items-start gap-2"
            v-slot="{ errors, processing }"
            @success="esEdicion = false"
        >
            <input
                type="hidden"
                name="es_activa"
                :value="area.es_activa ? 1 : 0"
            />
            <div class="grid flex-1 gap-1">
                <Input
                    name="nombre"
                    :default-value="area.nombre"
                    required
                    aria-label="Nombre"
                />
                <InputError :message="errors.nombre" />
            </div>
            <Button :disabled="processing">Guardar</Button>
            <Button type="button" variant="ghost" @click="esEdicion = false"
                >Cancelar</Button
            >
        </Form>

        <template v-else>
            <div class="flex items-center gap-2">
                <span class="font-medium">{{ area.nombre }}</span>
                <Badge :variant="area.es_activa ? 'secondary' : 'outline'">
                    {{ area.es_activa ? 'Activa' : 'Inactiva' }}
                </Badge>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="esEdicion = true"
                    >Editar</Button
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
                    <Button variant="ghost" size="sm" :disabled="processing">
                        {{ area.es_activa ? 'Desactivar' : 'Activar' }}
                    </Button>
                </Form>
            </div>
        </template>
    </li>
</template>
