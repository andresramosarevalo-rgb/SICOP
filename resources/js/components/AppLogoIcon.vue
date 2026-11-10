<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import logotipoCircular from '../../images/logotypes/sicop-circular.png';

defineOptions({ inheritAttrs: false });

defineProps<{
    className?: HTMLAttributes['class'];
}>();
</script>

<template>
    <img
        :src="logotipoCircular"
        alt="SICOP — Sistema Integral COVIACOL de Operaciones"
        width="1000"
        height="1000"
        class="block h-auto w-30 max-w-full min-w-30 object-contain"
        :class="className"
        v-bind="$attrs"
    />
</template>
