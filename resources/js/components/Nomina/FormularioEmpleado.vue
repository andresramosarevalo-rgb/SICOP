<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import EmpleadoController from '@/actions/App/Http/Controllers/Nomina/EmpleadoController';
import CamposContrato from '@/components/Nomina/CamposContrato.vue';
import CamposEmpleado from '@/components/Nomina/CamposEmpleado.vue';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { Area, Opcion, OpcionTipoDocumento } from '@/types';

defineProps<{
    areas: Pick<Area, 'id' | 'nombre'>[];
    tiposDocumento: OpcionTipoDocumento[];
    tiposCuentaBancaria: Opcion[];
    sedes: { id: number; nombre: string }[];
    puedeElegirSede: boolean;
    tiposContrato: Opcion[];
    periodicidades: Opcion[];
}>();

const vista = reactive({
    nombres: '',
    apellidos: '',
    cargo: '',
    area: '',
    sede: '',
    periodicidad: '',
    tipoContrato: '',
});

function textoElegido(campo: HTMLSelectElement): string {
    return campo.value === '' ? '' : (campo.selectedOptions[0]?.text ?? '');
}

/**
 * Mantiene la vista previa al día con lo que se escribe en el formulario.
 */
function alEscribir(evento: Event): void {
    const campo = evento.target as HTMLInputElement | HTMLSelectElement;

    switch (campo.name) {
        case 'nombres':
        case 'apellidos':
        case 'cargo':
            vista[campo.name] = campo.value;
            break;
        case 'area_id':
            vista.area = textoElegido(campo as HTMLSelectElement);
            break;
        case 'sede_id':
            vista.sede = textoElegido(campo as HTMLSelectElement);
            break;
        case 'contrato[periodicidad_pago]':
            vista.periodicidad = textoElegido(campo as HTMLSelectElement);
            break;
        case 'contrato[tipo_contrato]':
            vista.tipoContrato = textoElegido(campo as HTMLSelectElement);
            break;
    }
}

function ubicacion(): string {
    return (
        [vista.cargo, vista.area, vista.sede].filter(Boolean).join(' · ') ||
        'Cargo · Área · Sede'
    );
}

function condiciones(): string {
    return [vista.periodicidad, vista.tipoContrato].filter(Boolean).join(' · ');
}
</script>

<template>
    <div class="flex flex-wrap items-start gap-6">
        <Form
            v-bind="EmpleadoController.store.form()"
            :options="{ preserveScroll: 'errors' }"
            class="rv flex min-w-0 flex-[2_1_560px] flex-col gap-6"
            style="--i: 2"
            v-slot="{ errors, processing }"
            @input="alEscribir"
            @change="alEscribir"
        >
            <section
                class="flex flex-col gap-5 rounded-[22px] border bg-card p-7"
            >
                <h2 class="font-heading text-lg leading-[normal] font-bold">
                    1. Datos del empleado
                </h2>
                <CamposEmpleado
                    :areas="areas"
                    :tipos-documento="tiposDocumento"
                    :tipos-cuenta-bancaria="tiposCuentaBancaria"
                    :sedes="sedes"
                    :puede-elegir-sede="puedeElegirSede"
                    :errors="errors"
                />
            </section>
            <section
                class="flex flex-col gap-5 rounded-[22px] border bg-card p-7"
            >
                <div class="flex flex-col gap-1">
                    <h2 class="font-heading text-lg leading-[normal] font-bold">
                        2. Contrato
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Define el salario y cada cuánto se le paga (semanal,
                        quincenal o mensual).
                    </p>
                </div>
                <CamposContrato
                    prefijo="contrato"
                    :errors="errors"
                    :tipos-contrato="tiposContrato"
                    :periodicidades="periodicidades"
                />
            </section>
            <div class="flex flex-wrap justify-end gap-3">
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="nomina.empleados.index()">Cancelar</Link>
                </Button>
                <Button
                    :disabled="processing"
                    class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                    >Registrar empleado y contrato</Button
                >
            </div>
        </Form>

        <aside
            class="rv flex min-w-0 flex-[1_1_280px] flex-col gap-4 rounded-[22px] border border-brand-gold/55 bg-cream p-7"
            style="--i: 3"
            aria-labelledby="vista-previa-empleado-title"
        >
            <h2
                id="vista-previa-empleado-title"
                class="font-heading text-lg leading-[normal] font-bold"
            >
                Vista previa
            </h2>
            <div
                class="flex flex-col gap-1.5 rounded-2xl border border-line-cream bg-card p-[18px]"
            >
                <span
                    class="font-heading text-[17px] leading-[normal] font-bold break-words"
                    >{{
                        `${vista.nombres} ${vista.apellidos}`.trim() ||
                        'Nombres y apellidos'
                    }}</span
                >
                <span
                    class="text-sm leading-[normal] break-words text-muted-foreground"
                    >{{ ubicacion() }}</span
                >
                <span
                    v-if="condiciones()"
                    class="mt-1.5 inline-flex self-start rounded-full bg-ok-bg px-3 py-[5px] text-[13px] leading-[normal] font-semibold text-success"
                    >{{ condiciones() }}</span
                >
            </div>
        </aside>
    </div>
</template>
