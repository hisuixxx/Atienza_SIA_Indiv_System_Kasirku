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
        title: 'User Management',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Sales Reports',
        href: '/admin/reports',
        icon: BarChart3,
    },
    {
        title: 'Kitchen Queue',
        href: '/admin/kitchen',
        icon: BellRing,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const role = auth.user?.role ?? 'customer';

    // Role-based visibility
    // Admin sees everything
    // Staff sees everything except User Management
    // Kitchen sees only Overview + Kitchen Queue
    const visibleNavItems = mainNavItems.filter((item) => {
        if (role === 'kitchen') {
            return ['/admin/dashboard', '/admin/kitchen'].includes(item.href);
        }

        if (role === 'staff') {
            // Staff (Cashier) cannot access User Management
            return item.href !== '/admin/users';
        }

        // Admin and any other elevated role
        return role === 'admin';
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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}