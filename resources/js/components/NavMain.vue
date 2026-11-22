<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
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
    <SidebarGroup class="p-0">
        <SidebarMenu class="gap-1.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    class="h-auto gap-3 rounded-xl px-3.5 py-3 text-[15px] leading-[normal] font-medium text-brand-cream transition-[background,transform] duration-200 hover:translate-x-[3px] hover:bg-white/8 hover:text-brand-cream data-[active=true]:bg-brand-gold/16 data-[active=true]:font-semibold data-[active=true]:text-white data-[active=true]:hover:bg-brand-gold/16 motion-reduce:transition-none motion-reduce:hover:translate-x-0 [&>svg]:size-5 [&>svg]:stroke-[1.8] data-[active=true]:[&>svg]:text-brand-gold"
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
                        <span
                            class="flex-1 group-data-[collapsible=icon]:hidden"
                            >{{ item.title }}</span
                        >
                        <span
                            v-if="isCurrentOrParentUrl(item.href)"
                            class="size-[7px] shrink-0 rounded-full bg-brand-gold group-data-[collapsible=icon]:hidden"
                            aria-hidden="true"
                        />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
