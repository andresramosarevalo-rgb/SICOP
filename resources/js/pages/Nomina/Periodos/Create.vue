<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PeriodoNominaController from '@/actions/App/Http/Controllers/Nomina/PeriodoNominaController';
import FormularioNomina from '@/components/Nomina/FormularioNomina.vue';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import nomina from '@/routes/nomina';
import type { Opcion } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Nómina', href: nomina.inicio.index() },
            { title: 'Periodos', href: nomina.periodos.index() },
            { title: 'Nuevo periodo', href: nomina.periodos.create() },
        ],
    },
});

const claseEtiqueta = 'text-sm leading-[normal] font-semibold';

defineProps<{
    periodicidades: Opcion[];
    sedes: { id: number; nombre: string }[];
    puedeElegirSede: boolean;
}>();
</script>

<template>
    <Head title="Nuevo periodo" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Nuevo periodo de nómina
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                Elija la sede, la periodicidad y el rango de fechas que se va a
                pagar.
            </p>
        </header>

        <FormularioNomina
            :accion="PeriodoNominaController.store.form()"
            etiqueta-guardar="Crear periodo"
            :href-cancelar="nomina.periodos.index()"
        >
            <template #default="{ errors }">
                <div
                    class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-5"
                >
                    <div v-if="puedeElegirSede" class="flex flex-col gap-2">
                        <Label for="sede_id" :class="claseEtiqueta">Sede</Label>
                        <select id="sede_id" name="sede_id" class="campo-sicop">
                            <option value="">Todas las sedes</option>
                            <option
                                v-for="sede in sedes"
                                :key="sede.id"
                                :value="sede.id"
                            >
                                {{ sede.nombre }}
                            </option>
                        </select>
                        <InputError :message="errors.sede_id" />
                    </div>
                    <p
                        v-else-if="sedes.length > 0"
                        class="col-span-full text-sm text-muted-foreground"
                    >
                        Periodo de la sede:
                        <span class="font-semibold text-foreground">{{
                            sedes[0].nombre
                        }}</span>
                    </p>
                    <div class="flex flex-col gap-2">
                        <Label for="periodicidad_pago" :class="claseEtiqueta"
                            >Periodicidad</Label
                        >
                        <select
                            id="periodicidad_pago"
                            name="periodicidad_pago"
                            class="campo-sicop"
                        >
                            <option
                                v-for="periodicidad in periodicidades"
                                :key="periodicidad.valor"
                                :value="periodicidad.valor"
                            >
                                {{ periodicidad.etiqueta }}
                            </option>
                        </select>
                        <InputError :message="errors.periodicidad_pago" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label for="fecha_inicio" :class="claseEtiqueta"
                            >Desde</Label
                        >
                        <Input
                            id="fecha_inicio"
                            name="fecha_inicio"
                            type="date"
                            class="campo-sicop"
                            required
                        />
                        <InputError :message="errors.fecha_inicio" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label for="fecha_fin" :class="claseEtiqueta"
                            >Hasta</Label
                        >
                        <Input
                            id="fecha_fin"
                            name="fecha_fin"
                            type="date"
                            class="campo-sicop"
                            required
                        />
                        <InputError :message="errors.fecha_fin" />
                    </div>
                    <p class="col-span-full text-sm text-muted-foreground">
                        Se liquidarán los empleados con contrato vigente de esta
                        periodicidad.
                    </p>
                </div>
            </template>
        </FormularioNomina>
    </PaginaPos>
</template>
