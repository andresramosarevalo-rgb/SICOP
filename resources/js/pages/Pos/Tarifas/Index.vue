<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import TarifaGestorController from '@/actions/App/Http/Controllers/Pos/TarifaGestorController';
import PaginaPos from '@/components/Pos/PaginaPos.vue';
import PanelTabla from '@/components/Pos/PanelTabla.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import InputDinero from '@/components/InputDinero.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { FilaTarifaGestor, Sede } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Tarifas del gestor', href: '' },
        ],
    },
});

const props = defineProps<{
    sedes: Pick<Sede, 'id' | 'nombre'>[];
    sedeId: number | null;
    filas: FilaTarifaGestor[];
    puedeEditarBase: boolean;
}>();

const tituloTabla = computed(() => {
    const sede = props.sedes.find((opcion) => opcion.id === props.sedeId);

    return sede
        ? `Tarifas de ${sede.nombre}`
        : 'Tarifa base de todas las sedes';
});

function elegirSede(evento: Event): void {
    const sedeId = (evento.target as HTMLSelectElement).value;

    router.get(pos.tarifas.index(), sedeId ? { sede_id: sedeId } : {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Tarifas del gestor" />

    <PaginaPos>
        <header class="rv flex flex-col gap-2" style="--i: 1">
            <h1
                class="font-heading text-3xl leading-[1.15] font-extrabold md:text-[34px]"
            >
                Tarifas del gestor
            </h1>
            <p class="text-[17px] leading-[1.6] text-muted-foreground">
                La tarifa base aplica a todas las sedes. Cada sede puede tener
                un ajuste propio.
            </p>
        </header>

        <div
            class="rv flex flex-wrap items-end gap-4 rounded-[20px] border bg-card px-6 py-5"
            style="--i: 2"
        >
            <div class="flex min-w-[280px] flex-col gap-2">
                <Label for="sede" class="text-sm leading-[normal] font-semibold"
                    >Tarifas de</Label
                >
                <select
                    id="sede"
                    class="campo-sicop"
                    :value="sedeId ?? ''"
                    @change="elegirSede"
                >
                    <option v-if="puedeEditarBase" value="">
                        Tarifa base (todas las sedes)
                    </option>
                    <option
                        v-for="sede in sedes"
                        :key="sede.id"
                        :value="sede.id"
                    >
                        {{ sede.nombre }}
                    </option>
                </select>
            </div>
        </div>

        <PanelTabla :titulo="tituloTabla" :indice="3" relleno="16px">
            <table class="min-w-[720px]">
                <thead>
                    <tr>
                        <th scope="col">Tipo</th>
                        <th v-if="sedeId" scope="col" class="text-right">
                            Tarifa base
                        </th>
                        <th scope="col">
                            {{ sedeId ? 'Ajuste de la sede' : 'Tarifa base' }}
                        </th>
                        <th scope="col">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="fila in filas" :key="`${sedeId}-${fila.codigo}`">
                        <td class="font-semibold">Tipo {{ fila.codigo }}</td>
                        <td v-if="sedeId" class="text-right font-heading">
                            {{ formatearPesos(fila.valor_base) }}
                        </td>
                        <td class="!py-3">
                            <Form
                                :id="`tarifa-${sedeId ?? 'base'}-${fila.codigo}`"
                                v-bind="TarifaGestorController.store.form()"
                                class="flex flex-col gap-1"
                                v-slot="{ errors }"
                            >
                                <input
                                    type="hidden"
                                    name="codigo_infraccion"
                                    :value="fila.codigo"
                                />
                                <input
                                    v-if="sedeId"
                                    type="hidden"
                                    name="sede_id"
                                    :value="sedeId"
                                />
                                <InputDinero
                                    name="valor_pago_gestor"
                                    class="campo-sicop max-w-[200px] [--alto-campo:42px]"
                                    :aria-label="`Valor para el tipo ${fila.codigo}`"
                                    :placeholder="sedeId ? 'Sin ajuste' : '0'"
                                    :default-value="
                                        sedeId
                                            ? fila.ajuste
                                                ? Number(
                                                      fila.ajuste
                                                          .valor_pago_gestor,
                                                  )
                                                : ''
                                            : Number(fila.valor_base)
                                    "
                                    required
                                />
                                <InputError
                                    :message="errors.valor_pago_gestor"
                                />
                            </Form>
                        </td>
                        <td class="!py-3 whitespace-nowrap">
                            <div class="flex items-start justify-end gap-2">
                                <Button
                                    type="submit"
                                    :form="`tarifa-${sedeId ?? 'base'}-${fila.codigo}`"
                                    class="boton-plano min-h-[38px] rounded-[10px] px-4 text-[13px] font-bold hover:bg-primary"
                                    >Guardar</Button
                                >
                                <Form
                                    v-if="sedeId && fila.ajuste"
                                    v-bind="
                                        TarifaGestorController.destroy.form(
                                            fila.ajuste.id,
                                        )
                                    "
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        variant="outline"
                                        :disabled="processing"
                                        class="boton-plano min-h-[38px] rounded-[10px] border-line-strong bg-card px-3.5 text-[13px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                                        >Quitar ajuste</Button
                                    >
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filas.length === 0">
                        <td
                            :colspan="sedeId ? 4 : 3"
                            class="text-center text-muted-foreground"
                        >
                            No hay tarifas disponibles.
                        </td>
                    </tr>
                </tbody>
            </table>
        </PanelTabla>
    </PaginaPos>
</template>
