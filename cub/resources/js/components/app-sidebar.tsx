import { Link } from '@inertiajs/react';
import { KeyRound, LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useI18n } from '@/i18n';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useI18n('common');
    const { t: addT } = useI18n('add');
    const mainNavItems: NavItem[] = [
        {
            title: t('dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: addT('title'),
            href: '/add',
            icon: KeyRound,
        },
    ];
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="[&_[data-sidebar=sidebar]]:border-white/10 [&_[data-sidebar=sidebar]]:bg-[linear-gradient(180deg,_#08111d_0%,_#101d31_58%,_#16253c_100%)] [&_[data-sidebar=sidebar]]:text-white"
        >
            <SidebarHeader className="border-b border-white/6 px-3 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-auto rounded-[1.2rem] border border-white/8 bg-white/6 p-3 hover:bg-white/10"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2 py-4">
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <div className="mt-auto border-t border-white/6 px-2 py-3">
                <NavUser />
            </div>
        </Sidebar>
    );
}
