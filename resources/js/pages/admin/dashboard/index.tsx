import { OrdersByStatusChart } from '@/components/dashboard/orders-by-status-chart';
import { RevenueTrendChart } from '@/components/dashboard/revenue-trend-chart';
import { TopProductsChart } from '@/components/dashboard/top-products-chart';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, Bell, CheckCircle2, Clock3, Coffee, GitBranch, Package, ReceiptText, ShoppingBag, TrendingUp, Wallet } from 'lucide-react';

interface Stats {
    total_revenue: number;
    total_orders: number;
    pending_orders: number;
    active_orders: number;
    completed_orders: number;
    cancelled_orders: number;
    today_orders: number;
    today_revenue: number;
    total_branches: number;
    total_products: number;
    total_categories: number;
}
interface TopProduct { id: string; name: string; quantity_sold: number; revenue: number }
interface RecentOrder { id: number; order_number: string | null; branch_name: string | null; customer_name: string | null; status: string; total: number; created_at: string }
interface RecentTransaction { id: number; order_id: number; order_number: string; branch_name: string | null; method: string; status: string; amount: number; created_at: string }
interface BranchOption { id: number; name: string; code: string }
interface BranchPerformance { id: number; name: string; sales: number; orders: number; share: number }
interface RevenuePoint { date: string; revenue: number }
interface OrderStatusPoint { status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'completed' | 'cancelled'; label: string; count: number }
interface Props { stats: Stats; topProducts: TopProduct[]; recentOrders: RecentOrder[]; recentTransactions: RecentTransaction[]; revenueTrend: RevenuePoint[]; ordersByStatus: OrderStatusPoint[]; branches: BranchOption[]; selectedBranch: number | null; isAdministrator: boolean; filters: { period: string; start_date: string; end_date: string }; branchPerformance: BranchPerformance[]; pendingPayments: number }

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Overview', href: '/admin/dashboard' }];
const statusStyle: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800', completed: 'bg-emerald-100 text-emerald-800', cancelled: 'bg-rose-100 text-rose-800',
};

function Metric({ label, value, hint, icon: Icon, tone }: { label: string; value: string; hint: string; icon: typeof Wallet; tone: string }) {
    return <article className="brew-metric"><div className="flex items-start justify-between"><p className="brew-overline">{label}</p><span className={`brew-icon ${tone}`}><Icon size={18} /></span></div><p className="mt-3 text-[1.8rem] font-semibold tracking-tight text-[#33251f]">{value}</p><p className="mt-1 text-xs text-[#8b7b71]">{hint}</p></article>;
}

export default function Dashboard({ stats, topProducts, recentOrders, recentTransactions, revenueTrend, ordersByStatus, branches, selectedBranch, isAdministrator, filters, branchPerformance, pendingPayments }: Props) {
    const activeOrders = stats.active_orders;
    const selectedBranchName = branches.find(branch => branch.id === selectedBranch)?.name;
    const scopeLabel = selectedBranchName ?? (isAdministrator ? 'HEAD OFFICE' : branches.length > 1 ? 'ASSIGNED BRANCHES' : branches[0]?.name ?? 'NO BRANCH ASSIGNED');
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Overview" />
            <main className="brew-page">
                <header className="brew-page-heading">
                    <div><div className="brew-overline mb-2">SIP N BREW HAVEN INC. <span className="mx-2 text-[#bba99c]">/</span> {scopeLabel}</div><h1>{isAdministrator && !selectedBranch ? 'A good day, across every café.' : selectedBranch ? `${selectedBranchName ?? 'Your branch'}, running smoothly.` : 'Your assigned branches, running smoothly.'}</h1><p>{isAdministrator && !selectedBranch ? 'Consolidated sales and service' : 'Branch orders and transactions'} <span className="mx-1">•</span> {new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</p></div>
                    <div className="flex flex-wrap gap-2"><select aria-label="Dashboard branch" className="brew-control" value={selectedBranch ?? ''} onChange={event => router.get('/admin/dashboard', { ...(event.target.value ? { branch_id: event.target.value } : {}), period: filters.period, ...(filters.period === 'custom' ? { start_date: filters.start_date, end_date: filters.end_date } : {}) })}><option value="">{branches.length > 1 ? (isAdministrator ? 'All branches' : 'All assigned branches') : branches[0]?.name ?? 'No branch assigned'}</option>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select><select aria-label="Dashboard date range" className="brew-control" value={filters.period} onChange={event => router.get('/admin/dashboard', { ...(selectedBranch ? { branch_id: selectedBranch } : {}), period: event.target.value, ...(event.target.value === 'custom' ? { start_date: filters.start_date, end_date: filters.end_date } : {}) })}><option value="today">Today</option><option value="last7">Last 7 days</option><option value="last30">Last 30 days</option><option value="custom">Custom range</option></select>{filters.period === 'custom' && <><input aria-label="Start date" className="brew-control" type="date" value={filters.start_date} onChange={event => router.get('/admin/dashboard', { ...(selectedBranch ? { branch_id: selectedBranch } : {}), period: 'custom', start_date: event.target.value, end_date: filters.end_date })} /><input aria-label="End date" className="brew-control" type="date" value={filters.end_date} onChange={event => router.get('/admin/dashboard', { ...(selectedBranch ? { branch_id: selectedBranch } : {}), period: 'custom', start_date: filters.start_date, end_date: event.target.value })} /></>}{isAdministrator && <Link href="/admin/reports" className="brew-button"><ArrowUpRight size={15} /> View reports</Link>}</div>
                </header>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <Metric label="Branches in scope" value={stats.total_branches.toLocaleString()} hint={branches.length > 1 ? 'Filter by assigned branch' : 'Assigned locations'} icon={GitBranch} tone="brew-icon-tan" />
                    <Metric label="Sales · selected range" value={formatCurrency(stats.total_revenue)} hint={`${formatCurrency(stats.today_revenue)} collected in range`} icon={Wallet} tone="brew-icon-green" />
                    <Metric label="Orders placed" value={stats.total_orders.toLocaleString()} hint={`${stats.today_orders} orders in range`} icon={ShoppingBag} tone="brew-icon-tan" />
                    <Metric label="Average order value" value={formatCurrency(stats.total_orders ? stats.total_revenue / stats.total_orders : 0)} hint="Revenue ÷ orders placed" icon={TrendingUp} tone="brew-icon-gold" />
                    <Metric label="Active orders" value={activeOrders.toString()} hint={`${stats.completed_orders} completed · ${stats.cancelled_orders} cancelled`} icon={Clock3} tone="brew-icon-rust" />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
                    <article className="brew-panel"><div className="brew-panel-heading"><div><h2>Revenue through the day</h2><p>Completed payments over the last 14 days</p></div><span className="brew-pill brew-pill-green"><span className="h-1.5 w-1.5 rounded-full bg-emerald-600" /> Live snapshot</span></div><div className="mt-5"><RevenueTrendChart data={revenueTrend} /></div></article>
                    <article className="brew-panel"><div className="brew-panel-heading"><div><h2>Order status</h2><p>Current fulfillment across the store</p></div><ReceiptText size={18} className="text-[#98745e]" /></div><div className="mt-5"><OrdersByStatusChart data={ordersByStatus} /></div><div className="mt-4 flex flex-wrap gap-2">{ordersByStatus.map(item => <span key={item.status} className={`brew-pill ${item.status === 'completed' ? 'brew-pill-green' : item.status === 'cancelled' ? 'brew-pill-red' : 'brew-pill-gold'}`}>{item.count} {item.label}</span>)}</div></article>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.55fr_1fr]">
                    <article className="brew-panel overflow-hidden"><div className="brew-panel-heading"><div><h2>Live orders</h2><p>Recent orders and payment status</p></div><Link href={selectedBranch ? `/admin/orders?branch_id=${selectedBranch}` : '/admin/orders'} className="brew-text-link">Open orders <ArrowRight size={14} /></Link></div>
                        {recentOrders.length ? <div className="mt-4 overflow-x-auto"><table className="brew-table"><thead><tr><th>Order</th><th>Branch</th><th>Customer</th><th>Placed</th><th>Status</th><th className="text-right">Total</th></tr></thead><tbody>{recentOrders.slice(0, 6).map(order => <tr key={order.id}><td className="font-semibold text-[#49362c]">{order.order_number ?? `#${order.id}`}</td><td>{order.branch_name ?? 'Unassigned'}</td><td>{order.customer_name ?? 'Walk-in'}</td><td>{formatDate(order.created_at)}</td><td><span className={`brew-pill ${statusStyle[order.status] ?? 'bg-stone-100 text-stone-700'}`}>{order.status}</span></td><td className="text-right font-semibold text-[#49362c]">{formatCurrency(order.total)}</td></tr>)}</tbody></table></div> : <div className="brew-empty"><Coffee size={24} /><p>No orders yet. Your first cup is waiting.</p></div>}
                    </article>
                    <article className="brew-panel"><div className="brew-panel-heading"><div><h2>Top selling products</h2><p>Most ordered items</p></div><Package size={18} className="text-[#98745e]" /></div>{topProducts.length ? <div className="mt-4"><TopProductsChart data={topProducts} /></div> : <div className="brew-empty"><Package size={24} /><p>Sales insights will appear here.</p></div>}<div className="brew-summary"><span><CheckCircle2 size={15} /> {stats.total_products} products in your catalog</span><span><Bell size={15} /> {stats.total_categories} menu categories</span></div></article>
                </section>
                <section className="mt-4">
                    <article className="brew-panel"><div className="brew-panel-heading"><div><h2>Branch performance</h2><p>Collections and share in the selected date range</p></div></div>{branchPerformance.length ? <div className="mt-4 space-y-4">{branchPerformance.map(branch => <div key={branch.id}><div className="flex items-center justify-between gap-2 text-xs"><Link href={`/admin/orders?branch_id=${branch.id}`} className="font-semibold text-[#49372d] hover:underline">{branch.name} <small className="font-normal text-[#8d7e73]">· {branch.orders} orders · View orders</small></Link><span className="font-semibold">{formatCurrency(branch.sales)} <small className="font-normal text-[#8d7e73]">{branch.share}%</small></span></div><div className="mt-2 h-1.5 overflow-hidden rounded-full bg-[#eee5da]"><div className="h-full rounded-full bg-[#805840]" style={{ width: `${Math.min(branch.share, 100)}%` }} /></div></div>)}</div> : <div className="brew-empty"><ShoppingBag size={22} /><p>No branches available in this scope.</p></div>}</article>
                </section>
                <section className="mt-4 grid gap-4 xl:grid-cols-[1.2fr_.8fr]">
                    <article className="brew-panel overflow-hidden"><div className="brew-panel-heading"><div><h2>Recent branch transactions</h2><p>Recorded payments in your accessible branches</p></div></div>{recentTransactions.length ? <div className="mt-4 overflow-x-auto"><table className="brew-table"><thead><tr><th>Order</th><th>Branch</th><th>Method</th><th>Status</th><th className="text-right">Amount</th></tr></thead><tbody>{recentTransactions.map(transaction => <tr key={transaction.id}><td className="font-semibold text-[#49362c]">{transaction.order_number}</td><td>{transaction.branch_name ?? 'Unassigned'}</td><td className="capitalize">{transaction.method}</td><td><span className={`brew-pill ${statusStyle[transaction.status] ?? 'bg-stone-100 text-stone-700'}`}>{transaction.status}</span></td><td className="text-right font-semibold">{formatCurrency(transaction.amount)}</td></tr>)}</tbody></table></div> : <div className="brew-empty"><ReceiptText size={22} /><p>No transactions recorded for this branch scope.</p></div>}</article>
                    <article className="brew-panel"><div className="brew-panel-heading"><div><h2>Pending payments</h2><p>{pendingPayments} payments need review</p></div></div><div className="brew-empty"><Wallet size={22} /><p>Payment verification is available from the payments workspace.</p></div><Link href="/admin/payments" className="brew-text-link">Review payments <ArrowRight size={14} /></Link></article>
                </section>
            </main>
        </AppLayout>
    );
}
