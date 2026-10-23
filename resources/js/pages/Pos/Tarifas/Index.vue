<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import TarifaGestorController from '@/actions/App/Http/Controllers/Pos/TarifaGestorController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Button } from '@/components/ui/button';
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

defineProps<{
    sedes: Pick<Sede, 'id' | 'nombre'>[];
    sedeId: number | null;
    filas: FilaTarifaGestor[];
}>();

const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';

function elegirSede(evento: Event): void {
    const sedeId = (evento.target as HTMLSelectElement).value;

    router.get(pos.tarifas.index(), sedeId ? { sede_id: sedeId } : {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Tarifas del gestor" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Tarifas del gestor"
                description="Lo que se paga al gestor por curso según el tipo de comparendo. Una sede sin ajuste usa la tarifa base."
            />
            <div class="grid gap-2">
                <Label for="sede">Tarifas de</Label>
                <select
                    id="sede"
                    :class="claseSelect"
                    :value="sedeId ?? ''"
                    @change="elegirSede"
                >
                    <option value="">Tarifa base (todas las sedes)</option>
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

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Tipo</th>
                        <th v-if="sedeId" class="px-4 py-2 font-medium">
                            Tarifa base
                        </th>
                        <th class="px-4 py-2 font-medium">
                            {{ sedeId ? 'Ajuste de la sede' : 'Tarifa base' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="fila in filas" :key="`${sedeId}-${fila.codigo}`">
                        <td class="px-4 py-2 font-medium">
                            Tipo {{ fila.codigo }}
                        </td>
                        <td v-if="sedeId" class="px-4 py-2">
                            {{ formatearPesos(fila.valor_base) }}
                        </td>
                        <td class="px-4 py-2">
                            <Form
                                v-bind="TarifaGestorController.store.form()"
                                class="flex flex-wrap items-start gap-2"
                                v-slot="{ errors, processing }"
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
                                <div class="grid gap-1">
                                    <Input
                                        name="valor_pago_gestor"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-40"
                                        :aria-label="`Valor para el tipo ${fila.codigo}`"
                                        :placeholder="
                                            sedeId
                                                ? String(
                                                      Number(fila.valor_base),
                                                  )
                                                : '0'
                                        "
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
                                </div>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    :disabled="processing"
                                    >Guardar</Button
                                >
                            </Form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
