<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import CamposSede from '@/components/Pos/CamposSede.vue';
import { Button } from '@/components/ui/button';
import pos from '@/routes/pos';
import type { Sede } from '@/types';

const props = defineProps<{
    accion: { action: string; method: 'post' };
    sede?: Sede;
    etiquetaGuardar: string;
}>();

const vista = reactive({
    nombre: props.sede?.nombre ?? '',
    ciudad: props.sede?.ciudad ?? '',
    direccion: props.sede?.direccion ?? '',
    activa: props.sede?.es_activa ?? true,
});

/**
 * Mantiene la vista previa al día con lo que se escribe en el formulario.
 */
function alEscribir(evento: Event): void {
    const campo = evento.target as HTMLInputElement;

    if (campo.name === 'es_activa') {
        vista.activa = campo.checked;
    } else if (
        campo.name === 'nombre' ||
        campo.name === 'ciudad' ||
        campo.name === 'direccion'
    ) {
        vista[campo.name] = campo.value;
    }
}
</script>

<template>
    <div class="flex flex-wrap items-start gap-6">
        <Form
            v-bind="accion"
            class="rv flex min-w-0 flex-[2_1_520px] flex-col gap-[22px] rounded-[22px] border bg-card p-8"
            style="--i: 2"
            v-slot="{ errors, processing }"
            @input="alEscribir"
            @change="alEscribir"
        >
            <CamposSede :errors="errors" :sede="sede" />
            <div
                class="flex flex-wrap justify-end gap-3 border-t border-line-faint pt-3"
            >
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="pos.sedes.index()">Cancelar</Link>
                </Button>
                <Button
                    :disabled="processing"
                    class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >
                    {{ etiquetaGuardar }}
                </Button>
            </div>
        </Form>

        <aside
            class="rv flex min-w-0 flex-[1_1_300px] flex-col gap-4 rounded-[22px] border border-brand-gold/55 bg-cream p-7"
            style="--i: 3"
            aria-labelledby="vista-previa-title"
        >
            <h2
                id="vista-previa-title"
                class="font-heading text-lg leading-[normal] font-bold"
            >
                Vista previa
            </h2>
            <div
                class="flex flex-col gap-1.5 rounded-2xl border border-line-cream bg-card p-[18px]"
            >
                <span class="text-[13px] leading-[normal] text-ink-3"
                    >Así aparecerá en la lista</span
                >
                <span
                    class="font-heading text-[17px] leading-[normal] font-bold break-words"
                    >{{ vista.nombre || 'Nombre de la sede' }}</span
                >
                <span class="text-sm break-words text-muted-foreground"
                    >{{ vista.ciudad || 'Ciudad' }} ·
                    {{ vista.direccion || 'Dirección' }}</span
                >
                <span
                    class="mt-1.5 inline-flex self-start rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                    :class="
                        vista.activa
                            ? 'bg-ok-bg text-success'
                            : 'bg-muted text-muted-foreground'
                    "
                    >{{ vista.activa ? 'Activa' : 'Inactiva' }}</span
                >
            </div>
        </aside>
    </div>
</template>
