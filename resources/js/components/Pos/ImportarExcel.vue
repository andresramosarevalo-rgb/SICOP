<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import ImportacionExcelController from '@/actions/App/Http/Controllers/Pos/ImportacionExcelController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    sedeId: number;
    tipo: 'cursos' | 'soats';
    etiqueta: string;
}>();

const abierto = ref(false);

const claseBotonSecundario =
    'boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card';
const parametros = { sede: props.sedeId, tipo: props.tipo };
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div class="flex flex-wrap gap-2.5">
            <Button variant="outline" as-child :class="claseBotonSecundario">
                <a :href="ImportacionExcelController.plantilla.url(parametros)"
                    >Descargar plantilla</a
                >
            </Button>
            <Button
                variant="outline"
                type="button"
                :class="claseBotonSecundario"
                :aria-expanded="abierto"
                @click="abierto = !abierto"
                >Importar Excel</Button
            >
        </div>

        <Form
            v-if="abierto"
            v-bind="ImportacionExcelController.store.form(parametros)"
            class="flex flex-col gap-[18px] rounded-[22px] border bg-card px-7 py-6"
            v-slot="{ errors, processing }"
            @success="abierto = false"
        >
            <p class="text-sm leading-[1.6] text-muted-foreground">
                Cada fila del Excel se guarda como un registro de
                {{ etiqueta }}. Si alguna fila tiene errores, no se guarda
                ninguna.
            </p>
            <div class="flex flex-col gap-2">
                <Label
                    for="archivo"
                    class="text-sm leading-[normal] font-semibold"
                    >Archivo de Excel (.xlsx)</Label
                >
                <Input
                    id="archivo"
                    name="archivo"
                    type="file"
                    accept=".xlsx"
                    class="campo-sicop pt-3"
                    required
                />
                <InputError :message="errors.archivo" />
            </div>
            <p
                v-if="errors.importacion"
                class="rounded-[14px] bg-danger-bg p-4 text-sm whitespace-pre-line text-danger"
            >
                {{ errors.importacion }}
            </p>
            <div class="flex justify-end border-t border-line-faint pt-3">
                <Button
                    :disabled="processing"
                    class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                    >Importar</Button
                >
            </div>
        </Form>
    </div>
</template>
