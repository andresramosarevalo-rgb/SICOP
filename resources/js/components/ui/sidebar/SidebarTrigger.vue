<script setup lang="ts">
import { computed, type HTMLAttributes } from "vue"
import { PanelLeftClose, PanelLeftOpen } from "@lucide/vue"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, openMobile, state, toggleSidebar } = useSidebar()
const esAbierto = computed(() => isMobile.value ? openMobile.value : state.value === "expanded")
const etiqueta = computed(() => isMobile.value
  ? (esAbierto.value ? "Cerrar menú" : "Abrir menú")
  : (esAbierto.value ? "Contraer menú" : "Expandir menú"))
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('size-10 shrink-0', props.class)"
    :aria-expanded="esAbierto"
    :aria-label="etiqueta"
    :title="etiqueta"
    @click="toggleSidebar"
  >
    <PanelLeftOpen v-if="!esAbierto" />
    <PanelLeftClose v-else />
    <span class="sr-only">{{ etiqueta }}</span>
  </Button>
</template>
