<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isMobile, setOpenMobile } = useSidebar();
const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-3 py-0 group-data-[collapsible=icon]:px-4">
        <SidebarGroupLabel
            class="mb-2 text-[11px] font-semibold tracking-wider text-sidebar-foreground/75 uppercase"
            >Navegación</SidebarGroupLabel
        >
        <SidebarMenu class="gap-2">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    class="h-10 border-l-2 border-transparent px-3 data-[active=true]:border-brand-gold"
                    :is-active="isCurrentOrParentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link
                        :href="item.href"
                        :aria-label="item.title"
                        @click="isMobile && setOpenMobile(false)"
                        :aria-current="
                            isCurrentOrParentUrl(item.href) ? 'page' : undefined
                        "
                    >
                        <component :is="item.icon" aria-hidden="true" />
                        <span class="group-data-[collapsible=icon]:hidden">{{
                            item.title
                        }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
