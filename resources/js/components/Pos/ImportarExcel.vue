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
const parametros = { sede: props.sedeId, tipo: props.tipo };
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap gap-2">
            <Button variant="outline" as-child>
                <a :href="ImportacionExcelController.plantilla.url(parametros)"
                    >Descargar plantilla</a
                >
            </Button>
            <Button variant="outline" type="button" @click="abierto = !abierto"
                >Importar Excel</Button
            >
        </div>

        <Form
            v-if="abierto"
            v-bind="ImportacionExcelController.store.form(parametros)"
            class="flex flex-col gap-3 rounded-xl border bg-card p-4"
            v-slot="{ errors, processing }"
            @success="abierto = false"
        >
            <p class="text-sm text-muted-foreground">
                Cada fila del Excel se guarda como un registro de
                {{ etiqueta }}. Si alguna fila tiene errores, no se guarda
                ninguna.
            </p>
            <div class="grid gap-2">
                <Label for="archivo">Archivo de Excel (.xlsx)</Label>
                <Input
                    id="archivo"
                    name="archivo"
                    type="file"
                    accept=".xlsx"
                    required
                />
                <InputError :message="errors.archivo" />
            </div>
            <p
                v-if="errors.importacion"
                class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm whitespace-pre-line text-destructive"
            >
                {{ errors.importacion }}
            </p>
            <div>
                <Button :disabled="processing">Importar</Button>
            </div>
        </Form>
    </div>
</template>
