<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const inicial = computed(
    () => Array.from(user.value.name)[0]?.toUpperCase() ?? '',
);
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        class="h-auto gap-3 rounded-[14px] border border-sidebar-border bg-white/6 p-3 text-brand-cream transition-[background,transform] duration-200 group-data-[collapsible=icon]:size-12! group-data-[collapsible=icon]:p-2! hover:translate-x-[3px] hover:bg-white/10 hover:text-brand-cream data-[state=open]:bg-white/10 data-[state=open]:text-brand-cream motion-reduce:transition-none motion-reduce:hover:translate-x-0"
                        data-test="sidebar-menu-button"
                        :aria-label="'Menú de ' + user.name"
                        :tooltip="user.name"
                    >
                        <span
                            class="flex size-[38px] shrink-0 items-center justify-center rounded-full bg-brand-gold font-heading font-bold text-brand-navy"
                            aria-hidden="true"
                        >
                            {{ inicial }}
                        </span>
                        <span
                            class="flex min-w-0 flex-1 flex-col group-data-[collapsible=icon]:hidden"
                        >
                            <span class="truncate text-sm font-semibold">{{
                                user.name
                            }}</span>
                            <span class="truncate text-xs text-[#b9c6d6]">{{
                                user.email
                            }}</span>
                        </span>
                        <ChevronsUpDown
                            class="size-4 group-data-[collapsible=icon]:hidden"
                        />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'bottom'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
