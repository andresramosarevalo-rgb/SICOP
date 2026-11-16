<script setup lang="ts">
import { ref, watch } from 'vue';
import VacacionesNovedadController from '@/actions/App/Http/Controllers/Nomina/VacacionesNovedadController';
import type { ConsultaVacacionesNovedad } from '@/types';

const props = defineProps<{
    empleadoId: string | number;
    fechaInicio: string;
    fechaFin: string;
    novedadId?: number;
}>();

const consulta = ref<ConsultaVacacionesNovedad | null>(null);
const esCargando = ref(false);
const errorConsulta = ref('');
const formatoDias = new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 4,
});
const formatearDias = (dias: string | number) =>
    formatoDias.format(Number(dias));

watch(
    () => [
        props.empleadoId,
        props.fechaInicio,
        props.fechaFin,
        props.novedadId,
    ],
    (_valores, _anteriores, limpiar) => {
        consulta.value = null;
        errorConsulta.value = '';
        esCargando.value = false;

        if (!props.empleadoId) return;

        const controlador = new AbortController();
        esCargando.value = true;

        const temporizador = setTimeout(async () => {
            const query: Record<string, string | number> = {};
            if (props.novedadId !== undefined) {
                query.novedad_id = props.novedadId;
            }
            if (
                props.fechaInicio &&
                props.fechaFin &&
                props.fechaFin >= props.fechaInicio
            ) {
                query.fecha_inicio = props.fechaInicio;
                query.fecha_fin = props.fechaFin;
            }

            try {
                const respuesta = await fetch(
                    VacacionesNovedadController.url(Number(props.empleadoId), {
                        query,
                    }),
                    {
                        headers: { Accept: 'application/json' },
                        signal: controlador.signal,
                    },
                );
                if (!respuesta.ok) throw new Error('Consulta no disponible');
                const datos =
                    (await respuesta.json()) as ConsultaVacacionesNovedad;
                if (!controlador.signal.aborted) consulta.value = datos;
            } catch {
                if (!controlador.signal.aborted) {
                    errorConsulta.value =
                        'No se pudo consultar el saldo. Vuelva a seleccionar el empleado para reintentar. El saldo se validará al guardar.';
                }
            } finally {
                if (!controlador.signal.aborted) esCargando.value = false;
            }
        }, 200);

        limpiar(() => {
            clearTimeout(temporizador);
            controlador.abort();
        });
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="grid gap-3 rounded-xl border bg-muted/30 p-4 text-sm sm:col-span-2"
        role="status"
        aria-live="polite"
        :aria-busy="esCargando"
    >
        <p class="font-medium">Saldo de vacaciones</p>
        <p v-if="!empleadoId" class="text-muted-foreground">
            Seleccione un empleado para consultar su saldo.
        </p>
        <p v-else-if="esCargando" class="text-muted-foreground">
            Consultando saldo y días hábiles…
        </p>
        <p v-else-if="errorConsulta" class="text-destructive">
            {{ errorConsulta }}
        </p>
        <template v-else-if="consulta">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">
                        Disponible al {{ consulta.saldoVacaciones.fechaCorte }}
                    </dt>
                    <dd class="font-medium">
                        {{
                            formatearDias(
                                consulta.saldoVacaciones.diasDisponibles,
                            )
                        }}
                        días hábiles
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">
                        Vacaciones futuras programadas
                    </dt>
                    <dd>{{ consulta.diasProgramados }} días hábiles</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">
                        Disponible para programar
                    </dt>
                    <dd class="font-medium">
                        {{
                            formatearDias(consulta.diasDisponiblesParaProgramar)
                        }}
                        días hábiles
                    </dd>
                </div>
                <template v-if="consulta.diasSolicitados !== null">
                    <div>
                        <dt class="text-muted-foreground">
                            Días hábiles del período
                        </dt>
                        <dd>{{ consulta.diasSolicitados }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">
                            Días hábiles a descontar
                        </dt>
                        <dd>{{ consulta.diasADescontar }}</dd>
                    </div>
                    <div v-if="consulta.diasRestantes !== null">
                        <dt class="text-muted-foreground">
                            Disponible después de guardar
                        </dt>
                        <dd>
                            {{ formatearDias(consulta.diasRestantes) }} días
                            hábiles
                        </dd>
                    </div>
                </template>
            </dl>
            <p
                v-if="consulta.diasSolicitados === null"
                class="text-muted-foreground"
            >
                Indique un período válido para consultar los días a descontar.
            </p>
            <p
                v-if="consulta.tieneSaldoSuficiente === false"
                class="text-destructive"
            >
                Saldo de vacaciones insuficiente para este período.
            </p>
            <p v-if="novedadId" class="text-muted-foreground">
                El saldo mostrado excluye esta novedad para evaluar su
                reemplazo.
            </p>
            <p class="text-muted-foreground">
                Se considera lo causado hasta hoy y se reservan las vacaciones
                futuras. Solo se descuentan días dentro del servicio registrado
                y que no estén incluidos en otras vacaciones. La liquidación
                conserva sus días calendario.
            </p>
            <p class="text-muted-foreground">
                {{ consulta.saldoVacaciones.criterioCalendario }}
            </p>
            <p
                v-for="advertencia in consulta.saldoVacaciones.advertencias"
                :key="advertencia"
                class="text-muted-foreground"
            >
                {{ advertencia }}
            </p>
        </template>
    </div>
</template>
