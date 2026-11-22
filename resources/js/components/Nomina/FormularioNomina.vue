<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineOptions({ inheritAttrs: false });

defineProps<{
    accion: { action: string; method: 'post' };
    etiquetaGuardar: string;
    hrefCancelar: InertiaLinkProps['href'];
}>();
</script>

<template>
    <div :class="$slots.lateral ? 'flex flex-wrap items-start gap-6' : ''">
        <Form
            v-bind="{ ...accion, ...$attrs }"
            class="rv flex min-w-0 flex-col gap-[22px] rounded-[22px] border bg-card p-8"
            :class="$slots.lateral ? 'flex-[2_1_560px]' : 'max-w-[760px]'"
            style="--i: 2"
            v-slot="{ errors, processing }"
        >
            <slot :errors="errors" :processing="processing" />
            <div
                class="flex flex-wrap justify-end gap-3 border-t border-line-faint pt-3"
            >
                <Button
                    variant="outline"
                    as-child
                    class="boton-plano h-12 rounded-full border-line-strong bg-card px-[22px] text-[15px] font-semibold shadow-none hover:bg-card dark:bg-card dark:hover:bg-card"
                >
                    <Link :href="hrefCancelar">Cancelar</Link>
                </Button>
                <Button
                    :disabled="processing"
                    class="boton-plano h-12 rounded-full px-[26px] text-[15px] font-bold hover:bg-primary"
                >
                    {{ etiquetaGuardar }}
                </Button>
            </div>
        </Form>
        <slot name="lateral" />
    </div>
</template>
