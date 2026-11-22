<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AreaController from '@/actions/App/Http/Controllers/Nomina/AreaController';
import InputError from '@/components/InputError.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import FilaArea from '@/components/Nomina/FilaArea.vue';
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

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Áreas
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Departamentos a los que pertenecen los empleados. Un área
                inactiva no se ofrece al registrar empleados.
            </p>
        </header>

        <Form
            v-bind="AreaController.store.form()"
            class="rv flex flex-wrap items-end gap-4 rounded-[20px] border bg-card px-6 py-5"
            style="--i: 2"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-2">
                <Label
                    for="nombre"
                    class="text-sm leading-[normal] font-semibold"
                    >Nueva área</Label
                >
                <Input
                    id="nombre"
                    name="nombre"
                    class="campo-sicop"
                    required
                    placeholder="Ej. Atención al cliente"
                />
                <InputError :message="errors.nombre" />
            </div>
            <Button
                :disabled="processing"
                class="boton-plano h-[50px] rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >Agregar</Button
            >
        </Form>

        <PanelTabla titulo="Áreas" :indice="3" ocultar-titulo>
            <table class="min-w-[640px]">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Estado</th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <FilaArea
                        v-for="area in areas"
                        :key="area.id"
                        :area="area"
                    />
                    <tr v-if="areas.length === 0">
                        <td
                            colspan="3"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Todavía no hay áreas registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
