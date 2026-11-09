<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, ShieldCheck, Store, Users, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import isotipoSidebar from '../../images/identidad/sicop-isotipo-sidebar.svg';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import nomina from '@/routes/nomina';
import pos from '@/routes/pos';
import type { NavItem } from '@/types';

const page = usePage();
const { isMobile, setOpenMobile } = useSidebar();

const mainNavItems = computed<NavItem[]>(() => [
    ...(page.props.auth.sedeId === null
        ? [
              {
                  title: 'Inicio',
                  href: dashboard(),
                  icon: LayoutGrid,
              },
          ]
        : []),
    ...(page.props.auth.permisos.gestionarNomina
        ? [
              {
                  title: 'Nómina',
                  href: nomina.inicio.index(),
                  icon: Wallet,
              },
          ]
        : []),
    ...(page.props.auth.permisos.usarPos
        ? [
              {
                  title: 'Punto de venta',
                  href: pos.inicio.index(),
                  icon: Store,
              },
          ]
        : []),
    ...(page.props.auth.permisos.verAuditoria
        ? [
              {
                  title: 'Auditoría',
                  href: admin.auditoria.index(),
                  icon: ShieldCheck,
              },
          ]
        : []),
    ...(page.props.auth.permisos.gestionarUsuarios
        ? [
              {
                  title: 'Usuarios',
                  href: admin.usuarios.index(),
                  icon: Users,
              },
          ]
        : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader
            class="relative border-b border-sidebar-border px-4 py-5"
        >
            <SidebarMenu :class="{ 'pr-12': isMobile }">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        class="h-12 gap-3 p-0"
                        tooltip="Inicio"
                        as-child
                    >
                        <Link
                            :href="
                                page.props.auth.sedeId === null
                                    ? dashboard()
                                    : pos.inicio.index()
                            "
                            aria-label="SICOP — Inicio"
                            @click="isMobile && setOpenMobile(false)"
                        >
                            <img
                                :src="isotipoSidebar"
                                alt=""
                                width="100"
                                height="100"
                                class="size-12 shrink-0"
                            />
                            <span
                                class="font-heading text-2xl font-bold tracking-wide group-data-[collapsible=icon]:hidden"
                            >
                                SICOP
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarTrigger
                v-if="isMobile"
                class="absolute top-6 right-3 text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
            />
        </SidebarHeader>

        <SidebarContent class="py-5">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter
            class="border-t border-sidebar-border p-3 group-data-[collapsible=icon]:p-4"
        >
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
