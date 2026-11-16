// Sidebar component for BrewSync admin dashboard
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BellRing, ChartBarStacked, LayoutGrid, ShoppingCart, Utensils, Wallet, BarChart3, Users } from 'lucide-react';
import AppLogo from './app-logo';

// Updated navigation items to match BrewSync requirements
const mainNavItems: NavItem[] = [
    {
        title: 'Overview',
        href: '/admin/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Orders',
        href: '/admin/orders',
        icon: ShoppingCart,
    },
    {
        title: 'Products',
        href: '/admin/products',
        icon: Utensils,
    },
    {
        title: 'Categories',
        href: '/admin/categories',
        icon: ChartBarStacked,
    },
    {
        title: 'Payments',
        href: '/admin/payments',
        icon: Wallet,
    },
    {
        title: 'Sales reports',
        href: '/admin/reports',
        icon: BarChart3,
    },
    {
        title: 'Kitchen queue',
        href: '/admin/kitchen',
        icon: BellRing,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const role = auth.user.role ?? 'cashier';
    const visibleNavItems = mainNavItems.filter(item => {
        // Kitchen staff only needs dashboard and kitchen queue
        if (role === 'kitchen_staff') return ['/admin/dashboard', '/admin/kitchen'].includes(item.href);
        // Admin, administrator, and staff see all items
        return role === 'admin' || role === 'administrator' || role === 'staff';
    });
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {/* Footer could include additional navigation or user info */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
