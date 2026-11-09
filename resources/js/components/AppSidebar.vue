<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, ShieldCheck, Store, Users, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import nomina from '@/routes/nomina';
import pos from '@/routes/pos';
import type { NavItem } from '@/types';

const page = usePage();

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
            class="border-b border-sidebar-border px-4 py-5 group-data-[collapsible=icon]:px-2"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        class="h-auto justify-center py-2"
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
                        >
                            <AppLogo
                                class="group-data-[collapsible=icon]:hidden"
                            />
                            <!-- El logo requiere al menos 120 px; en modo compacto
                                 este icono representa el enlace de navegación. -->
                            <LayoutGrid
                                v-if="page.props.auth.sedeId === null"
                                aria-hidden="true"
                                class="hidden group-data-[collapsible=icon]:block"
                            />
                            <Store
                                v-else
                                aria-hidden="true"
                                class="hidden group-data-[collapsible=icon]:block"
                            />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="py-5">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter
            class="border-t border-sidebar-border p-3 group-data-[collapsible=icon]:p-2"
        >
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
