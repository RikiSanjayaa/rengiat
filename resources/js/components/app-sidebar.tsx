import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    FileSignature,
    FileSpreadsheet,
    NotebookPen,
    ScrollText,
    UsersRound,
} from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { Auth } from '@/types';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;

    const mainNavItems: NavItem[] = [];

    if (
        auth.user.role === 'operator' ||
        auth.user.role === 'admin' ||
        auth.user.role === 'super_admin'
    ) {
        mainNavItems.push({
            title: 'Input Harian',
            href: '/daily-input',
            icon: NotebookPen,
        });
    }

    mainNavItems.push({
        title: 'Generator Laporan',
        href: '/reports',
        icon: FileSpreadsheet,
    });

    if (auth.abilities.manage_users) {
        mainNavItems.push({
            title: 'Manajemen User',
            href: '/admin/users',
            icon: UsersRound,
        });
    }

    if (auth.abilities.manage_units) {
        mainNavItems.push({
            title: 'Manajemen Unit',
            href: '/admin/units',
            icon: Building2,
        });
    }

    if (auth.abilities.view_audit_logs) {
        mainNavItems.push({
            title: 'Log Audit',
            href: '/admin/audit-logs',
            icon: ScrollText,
        });
    }

    if (auth.abilities.export_rengiat) {
        mainNavItems.push({
            title: 'Pengaturan Laporan',
            href: '/settings/report',
            icon: FileSignature,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
