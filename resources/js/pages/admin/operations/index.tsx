import AppLayout from '@/layouts/app-layout';
import { OrdersByStatusChart } from '@/components/dashboard/orders-by-status-chart';
import { RevenueTrendChart } from '@/components/dashboard/revenue-trend-chart';
import { TopProductsChart } from '@/components/dashboard/top-products-chart';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Check, Clock3, Coffee, Download, Package, RefreshCw, Search, Wallet } from 'lucide-react';
import { useEffect, useState } from 'react';

type Branch = { id: number; name: string; code: string; address?: string | null; is_active?: boolean; users?: { id: number; name: string; role?: string }[] };
type Item = { name: string; quantity: number; notes: string | null };
type Order = { id: number; number: string; customer: string; branch: string; status: string; total: number; order_type?: string | null; service_type?: string | null; created_at: string; items: Item[]; status_history?: { from_status: string | null; to_status: string; employee: string; branch: string | null; notes: string | null; created_at: string }[] };
type PaymentRow = { id: number; order_id: number; order_number: string; customer: string; branch: string; method: string; amount: number; status: string; order_status: string | null; verification_status: string | null; rejection_reason: string | null; reference: string | null; receipt_url: string | null; date: string; verification: { id: number; status: string; reference_number: string } | null };
type Refund = { id: number; order_number: string; customer_name: string | null; branch_name: string | null; reason: string; amount: number; status: string; authorized_employee?: string | null; authorized_at?: string | null; processed_employee?: string | null; processed_at?: string | null; processing_reference?: string | null };
type Cancellation = { id: number; order_number: string | null; customer_name: string | null; branch_name: string | null; reason: string; status: string; employee_name?: string | null; cancelled_at?: string | null };
type Employee = { id: number; name: string; email: string; role: string; is_active: boolean };
type SalesRow = { id: number; order_id: number; order_number: string; customer: string; branch: string; method: string; amount: number; payment_status: string; order_status: string | null; date: string };
type RefundRow = { id: number; order_number: string; customer: string; branch: string; method: string; amount: number; reason: string; date: string };
type ClosingReport = { id: number; branch_id: number; branch: string; closing_date: string; total_orders: number; completed_orders: number; cancelled_orders: number; cash_sales: number; gcash_sales: number; other_sales: number; gross_sales: number; refund_total: number; net_sales: number; generated_by: string | null; generated_at: string | null };
type Props = {
    module: string;
    title: string;
    branches: Branch[];
    selectedBranch: number | null;
    orders?: Order[];
    payments?: PaymentRow[];
    refunds?: Refund[];
    cancellations?: Cancellation[];
    employees?: Employee[];
    filters?: { period?: string; date?: string; start_date?: string; end_date?: string; payment_method?: string; order_status?: string };
    range?: { start_date: string; end_date: string };
    summary?: { gross_sales: number; cash_sales: number; gcash_sales: number; refund_total: number; net_sales: number; total_orders: number; completed_orders: number; cancelled_orders: number; average_order_value: number; paid_orders: number };
    breakdown?: { method: string; count: number; amount: number; refunds: number; net: number }[];
    salesRows?: SalesRow[];
    refundRows?: RefundRow[];
    salesTrend?: { date: string; amount: number }[];
    orderStatuses?: { status: string; count: number }[];
    topProducts?: { product_id: string; product_name: string; quantity: number; sales: number }[];
    branchComparison?: { branch_id: number; branch: string; gross_sales: number; refund_total: number; net_sales: number; paid_orders: number }[];
    closingReports?: ClosingReport[];
    closingReport?: ClosingReport;
};

const moduleHref: Record<string, string> = { kitchen: '/admin/kitchen', payments: '/admin/payments', handoff: '/admin/handoff', reports: '/admin/reports', 'closing-detail': '/admin/reports', refunds: '/admin/refunds', branches: '/admin/branches' };

function Panel({ children, title, note }: { children: React.ReactNode; title: string; note?: string }) {
    return <section className="brew-panel"><div className="brew-panel-heading"><div><h2>{title}</h2>{note && <p>{note}</p>}</div></div><div className="mt-4">{children}</div></section>;
}

export default function Operations({ module, title, branches, selectedBranch, orders = [], payments = [], refunds = [], cancellations = [], employees = [], filters = {}, range, summary, breakdown = [], salesRows = [], refundRows = [], salesTrend = [], orderStatuses = [], topProducts = [], branchComparison = [], closingReports = [], closingReport }: Props) {
    const [search, setSearch] = useState('');
    useEffect(() => {
        if (module !== 'kitchen') return;
        const timer = window.setInterval(() => router.reload({ only: ['orders'] }), 20000);
        return () => window.clearInterval(timer);
    }, [module]);
    const filteredOrders = orders.filter(order => `${order.number} ${order.customer} ${order.branch}`.toLowerCase().includes(search.toLowerCase()));
    const branchFilter = moduleHref[module] && <select aria-label="Branch" className="brew-control" value={selectedBranch ?? ''} onChange={event => {
        const branchParams = event.target.value ? { branch_id: event.target.value } : {};
        const reportParams = module === 'reports' ? filters : {};
        router.get(moduleHref[module], { ...reportParams, ...branchParams }, { preserveState: true });
    }}><option value="">{branches.length > 1 ? 'All branches' : branches[0]?.name ?? 'No branch assigned'}</option>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select>;
    const applyReportFilters = (changes: Partial<NonNullable<Props['filters']>>) => {
        const next = { ...filters, ...changes };
        if (next.period === 'custom' && (!next.start_date || !next.end_date)) return;
        router.get('/admin/reports', {
            ...next,
            ...(selectedBranch ? { branch_id: selectedBranch } : {}),
        }, { preserveState: true, preserveScroll: true });
    };
    const statusCounts = orderStatuses.reduce<Record<string, number>>((counts, row) => {
        const status = ['ready_for_pickup', 'out_for_delivery'].includes(row.status) ? 'ready' : row.status;
        counts[status] = (counts[status] ?? 0) + row.count;
        return counts;
    }, {});
    const statusChartData = (['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'] as const).map(status => ({
        status,
        label: status[0].toUpperCase() + status.slice(1),
        count: statusCounts[status] ?? 0,
    }));
    const maxBranchSales = Math.max(1, ...branchComparison.map(branch => branch.net_sales));
    const reportQuery = new URLSearchParams();
    Object.entries({
        ...filters,
        ...(selectedBranch ? { branch_id: String(selectedBranch) } : {}),
    }).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') reportQuery.set(key, String(value));
    });
    const ledgerRows = [
        ...salesRows.map(row => ({ ...row, type: 'Sale', signedAmount: row.amount, detail: row.payment_status })),
        ...refundRows.map(row => ({ ...row, type: 'Refund', signedAmount: -row.amount, detail: row.reason, payment_status: 'processed_manual', order_status: null })),
    ].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

    return <AppLayout><Head title={title} /><main className="brew-page">
        <header className="brew-page-heading"><div><div className="brew-overline mb-2">BREWSYNC <span className="mx-2 text-[#bba99c]">/</span> OPERATIONS</div><h1>{title}</h1><p>Branch activity and records from your live workspace.</p></div><div className="flex flex-wrap items-center gap-2">{branchFilter}<Link href="/admin/orders" className="brew-button"><Search size={15} /> Order history</Link></div></header>

        {module === 'kitchen' && <><div className="mb-4 flex items-center gap-2 rounded-xl border border-[#dce9dc] bg-[#e8f0e8] px-4 py-3 text-xs text-[#58735e]"><RefreshCw size={15} /> Kitchen queue refreshes automatically every 20 seconds.</div><div className="grid gap-4 xl:grid-cols-4">{(['pending', 'confirmed', 'preparing', 'ready'] as const).map(status => <Panel key={status} title={status[0].toUpperCase() + status.slice(1)} note={`${filteredOrders.filter(order => order.status === status).length} active tickets`}><div className="space-y-3">{filteredOrders.filter(order => order.status === status).map(order => <article key={order.id} className="rounded-xl border border-[#e9e1d7] bg-[#fffefa] p-4"><div className="flex justify-between"><div><strong className="text-lg">{order.number}</strong><p className="text-xs text-[#8d7e73]">{order.branch} · {order.customer}</p></div><span className="brew-pill brew-pill-gold">{order.status}</span></div><div className="my-3 space-y-1 border-y border-[#f0ebe5] py-3 text-sm">{order.items.map((item, index) => <div key={`${item.name}-${index}`}><span>{item.quantity} × {item.name}</span>{item.notes && <p className="text-xs text-[#98745e]">{item.notes}</p>}</div>)}</div><div className="flex items-center justify-between text-xs text-[#8d7e73]"><span><Clock3 className="mr-1 inline" size={13} />{formatDate(order.created_at)}</span>{status !== 'ready' && <form method="post" action={`/admin/kitchen/${order.id}`} onSubmit={event => { event.preventDefault(); const nextStatus = status === 'pending' ? 'confirmed' : status === 'confirmed' ? 'preparing' : 'ready'; router.patch(`/admin/kitchen/${order.id}`, { status: nextStatus }); }}><button className="brew-button px-3 py-2">{status === 'pending' ? 'Confirm order' : status === 'confirmed' ? 'Start preparing' : 'Mark ready'} <ArrowRight size={14} /></button></form>}</div></article>)}{!filteredOrders.some(order => order.status === status) && <div className="brew-empty"><Coffee size={22} /><p>No {status} orders.</p></div>}</div></Panel>)}</div></>}

        {module === 'handoff' && <div className="grid gap-4 xl:grid-cols-[1.1fr_.9fr]"><Panel title="Ready for handoff" note="Only paid, kitchen-ready orders or dispatched deliveries are listed."><div className="space-y-3">{filteredOrders.map(order => {
            const isDelivery = order.order_type === 'delivery';
            const isDispatched = order.status === 'out_for_delivery';
            const action = isDelivery && !isDispatched ? 'Dispatch delivery' : isDelivery ? 'Confirm delivery' : 'Confirm pickup';
            const endpoint = isDelivery && !isDispatched ? `/admin/handoff/${order.id}/dispatch` : `/admin/handoff/${order.id}`;
            return <article key={order.id} className="rounded-xl border border-[#e9e1d7] p-4"><div className="flex items-start justify-between"><div><strong className="text-lg">{order.number}</strong><p className="text-xs text-[#8d7e73]">{order.customer} · {order.branch} · {isDelivery ? `Delivery · ${order.status.replaceAll('_', ' ')}` : order.service_type ?? 'Pickup'}</p></div><strong>{formatCurrency(order.total)}</strong></div><p className="my-3 text-sm">{order.items.map(item => `${item.quantity} × ${item.name}`).join(' · ')}</p><form onSubmit={event => { event.preventDefault(); router.post(endpoint); }}><button className="brew-button"><Check size={15} /> {action}</button></form></article>;
        })}{!filteredOrders.length && <div className="brew-empty"><Check size={22} /><p>No paid orders waiting for pickup or delivery.</p></div>}</div></Panel><Panel title="Handoff workflow"><p className="text-sm leading-6 text-[#81766d]">Pickup orders can be confirmed when ready. Delivery orders move from ready to out for delivery, then a staff member confirms delivery to complete the order. Both actions record the responsible employee and status history.</p></Panel></div>}

        {module === 'payments' && <Panel title="Payments & verification" note="GCash submissions remain pending until an authorized employee verifies the reference. Uploaded receipts never auto-approve payments.">
            <div className="mb-3 flex items-center gap-2"><input className="brew-control" placeholder="Search reference or order" value={search} onChange={event => setSearch(event.target.value)} /></div>
            <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Order</th><th>Customer / branch</th><th>Method</th><th>Reference</th><th>Date</th><th>Status</th><th>Verification</th><th>Actions</th></tr></thead><tbody>
                {payments.filter(payment => `${payment.order_number} ${payment.customer} ${payment.reference ?? ''}`.toLowerCase().includes(search.toLowerCase())).map(payment => <tr key={payment.id}>
                    <td className="font-semibold">{payment.order_number}</td><td>{payment.customer}<small className="block">{payment.branch}</small></td><td className="capitalize">{payment.method}</td><td>{payment.reference ?? '—'}{payment.receipt_url && <a className="block text-blue-700 underline" href={payment.receipt_url} target="_blank" rel="noreferrer">View submitted receipt</a>}</td><td>{formatDate(payment.date)}</td>
                    <td><span className={`brew-pill ${payment.status === 'completed' ? 'brew-pill-green' : 'brew-pill-gold'}`}>{payment.status}</span>{payment.rejection_reason && <small className="block text-red-700">{payment.rejection_reason}</small>}</td>
                    <td>{payment.method.toLowerCase() === 'gcash' && !payment.verification && payment.status === 'pending'
                        ? <form className="flex gap-1" onSubmit={event => { event.preventDefault(); const reference = new FormData(event.currentTarget).get('reference_number'); router.post(`/admin/payments/${payment.id}/reference`, { reference_number: reference }); }}><input name="reference_number" required className="brew-control w-32" placeholder="GCash ref #" /><button className="brew-button px-2 py-1">Submit</button></form>
                        : payment.verification?.status === 'pending'
                            ? <div className="flex flex-col gap-1"><span className="text-xs">{payment.verification.reference_number}</span><div className="flex gap-1"><button className="brew-button px-2 py-1" onClick={() => router.post(`/admin/payments/verifications/${payment.verification!.id}`, { decision: 'approve' })}>Verify</button><button className="brew-control px-2 py-1" onClick={() => { const review_note = window.prompt('Required reason for rejecting this payment'); if (review_note?.trim()) router.post(`/admin/payments/verifications/${payment.verification!.id}`, { decision: 'reject', review_note }); }}>Reject</button></div></div>
                            : payment.verification?.status ?? '—'}
                    </td>
                    <td><div className="flex flex-col items-start gap-1">
                        {payment.status === 'completed' && <button className="brew-control px-2 py-1" onClick={() => { const amount = window.prompt('Refund amount', String(payment.amount)); const reason = window.prompt('Refund reason'); if (amount && reason?.trim()) router.post(`/admin/orders/${payment.order_id}/refund`, { amount, reason }); }}>Request refund</button>}
                        {['pending', 'confirmed', 'preparing', 'ready'].includes(payment.order_status ?? '') && <button className="brew-control px-2 py-1" onClick={() => { const reason = window.prompt('Cancellation reason'); if (reason?.trim()) router.post(`/admin/orders/${payment.order_id}/cancellation`, { reason }); }}>Cancel order</button>}
                    </div></td>
                </tr>)}
            </tbody></table>{!payments.length && <div className="brew-empty"><Wallet size={22} /><p>No payments to review.</p></div>}</div>
        </Panel>}

        {module === 'kitchen' && <Panel title="Order status history" note="Every transition is retained with its responsible employee and branch.">
            <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Order</th><th>Status</th><th>Employee</th><th>Branch</th><th>Note</th><th>Timestamp</th></tr></thead><tbody>
                {orders.flatMap(order => (order.status_history ?? []).map((history, index) => <tr key={`${order.id}-${index}`}><td>{order.number}</td><td>{history.from_status ? `${history.from_status} → ${history.to_status}` : history.to_status}</td><td>{history.employee}</td><td>{history.branch ?? order.branch}</td><td>{history.notes ?? '—'}</td><td>{formatDate(history.created_at)}</td></tr>))}
            </tbody></table></div>
        </Panel>}

        {module === 'branches' && (
            <div className="grid gap-4 xl:grid-cols-[.7fr_1.3fr]">
                <Panel title="Add branch" note="Create a branch location in the shared workspace.">
                    <BranchCreateForm />
                </Panel>
                <Panel title="Branches & team access" note="Assign staff and kitchen employees to their service locations.">
                    <div className="space-y-4">
                        {branches.map(branch => (
                            <form key={branch.id} className="rounded-xl border border-[#e9e1d7] p-4" onSubmit={event => {
                                event.preventDefault();
                                const form = new FormData(event.currentTarget);
                                router.put(`/admin/branches/${branch.id}`, {
                                    name: String(form.get('name') ?? ''),
                                    code: String(form.get('code') ?? ''),
                                    address: String(form.get('address') ?? ''),
                                    is_active: form.get('is_active') === 'on',
                                    employee_ids: form.getAll('employee_ids[]').map(Number),
                                }, { preserveScroll: true });
                            }}>
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <div><h3 className="font-semibold text-[#49362c]">{branch.name}</h3><p className="text-xs text-[#8d7e73]">{branch.code} · {branch.address ?? 'No address set'}</p></div>
                                    <span className={`brew-pill ${branch.is_active ? 'brew-pill-green' : 'bg-stone-100 text-stone-600'}`}>{branch.is_active ? 'Active' : 'Inactive'}</span>
                                </div>
                                <div className="grid gap-2 sm:grid-cols-[1fr_.5fr_1fr_auto]">
                                    <input aria-label={`${branch.name} name`} className="brew-control" name="name" defaultValue={branch.name} required />
                                    <input aria-label={`${branch.name} code`} className="brew-control uppercase" name="code" defaultValue={branch.code} required />
                                    <input aria-label={`${branch.name} address`} className="brew-control" name="address" defaultValue={branch.address ?? ''} placeholder="Address" />
                                    <label className="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" defaultChecked={branch.is_active} /> Active</label>
                                </div>
                                <p className="brew-overline mt-3">Assigned staff and kitchen</p>
                                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                                    {employees.map(employee => <label className="flex items-center gap-1.5 text-xs text-[#81766d]" key={employee.id}>
                                        <input type="checkbox" name="employee_ids[]" value={employee.id} defaultChecked={branch.users?.some(user => user.id === employee.id)} />
                                        {employee.name} · {employee.role}{employee.is_active ? '' : ' · inactive'}
                                    </label>)}
                                    {!employees.length && <span className="text-xs text-[#8d7e73]">No staff or kitchen accounts to assign.</span>}
                                </div>
                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                    <button className="brew-button">Save branch</button>
                                    <Link href={`/admin/orders?branch_id=${branch.id}`} className="brew-text-link">Branch orders <ArrowRight size={14} /></Link>
                                    <Link href={`/admin/dashboard?branch_id=${branch.id}`} className="brew-text-link">Performance <ArrowRight size={14} /></Link>
                                </div>
                            </form>
                        ))}
                    </div>
                </Panel>
            </div>
        )}

        {module === 'reports' && <>
            <form className="mb-4 flex flex-wrap items-end gap-2 rounded-xl border border-[#e9e1d7] bg-[#fffefa] p-3" onSubmit={event => event.preventDefault()}>
                <label className="text-xs text-[#81766d]">Period
                    <select className="brew-control mt-1 block" value={filters.period ?? 'daily'} onChange={event => applyReportFilters({ period: event.target.value })}>
                        <option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="custom">Custom range</option>
                    </select>
                </label>
                {filters.period === 'custom'
                    ? <><label className="text-xs text-[#81766d]">From<input className="brew-control mt-1 block" type="date" value={filters.start_date ?? ''} onChange={event => applyReportFilters({ start_date: event.target.value })} /></label><label className="text-xs text-[#81766d]">To<input className="brew-control mt-1 block" type="date" value={filters.end_date ?? ''} onChange={event => applyReportFilters({ end_date: event.target.value })} /></label></>
                    : <label className="text-xs text-[#81766d]">Date<input className="brew-control mt-1 block" type="date" value={filters.date ?? ''} onChange={event => applyReportFilters({ date: event.target.value })} /></label>}
                <label className="text-xs text-[#81766d]">Payment method
                    <select className="brew-control mt-1 block" value={filters.payment_method ?? ''} onChange={event => applyReportFilters({ payment_method: event.target.value })}>
                        <option value="">All methods</option>{['cash', 'gcash', 'card', 'digital', 'midtrans', 'other'].map(method => <option key={method} value={method}>{method}</option>)}
                    </select>
                </label>
                <label className="text-xs text-[#81766d]">Order status
                    <select className="brew-control mt-1 block" value={filters.order_status ?? ''} onChange={event => applyReportFilters({ order_status: event.target.value })}>
                        <option value="">All statuses</option>{['pending', 'confirmed', 'preparing', 'ready', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled'].map(status => <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>)}
                    </select>
                </label>
                <Link className="brew-button" href={`/admin/reports/export?${reportQuery.toString()}`}><Download size={14} /> CSV</Link>
                <button type="button" className="brew-control" onClick={() => window.print()}>Print report</button>
            </form>

            <p className="mb-3 text-xs text-[#81766d]">Report period: {range?.start_date} – {range?.end_date}. Collected revenue includes completed or approved payments only; only processed refunds reduce net sales.</p>
            <section className="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Metric label="Gross collections" value={formatCurrency(summary?.gross_sales ?? 0)} />
                <Metric label="Cash sales" value={formatCurrency(summary?.cash_sales ?? 0)} />
                <Metric label="GCash sales" value={formatCurrency(summary?.gcash_sales ?? 0)} />
                <Metric label="Processed refunds" value={formatCurrency(summary?.refund_total ?? 0)} />
                <Metric label="Net sales" value={formatCurrency(summary?.net_sales ?? 0)} />
                <Metric label="Completed orders" value={String(summary?.completed_orders ?? 0)} />
                <Metric label="Cancelled orders" value={String(summary?.cancelled_orders ?? 0)} />
                <Metric label="Average completed order" value={formatCurrency(summary?.average_order_value ?? 0)} />
            </section>

            <div className="mb-4 grid gap-4 xl:grid-cols-2">
                <Panel title="Collected sales trend" note="Settled payment amounts by payment date.">
                    {salesTrend.length ? <RevenueTrendChart data={salesTrend.map(point => ({ date: point.date, revenue: point.amount }))} /> : <div className="brew-empty"><Wallet size={22} /><p>No collected sales in this period.</p></div>}
                </Panel>
                <Panel title="Top-selling products" note="Quantities from completed orders with settled payments.">
                    {topProducts.length ? <TopProductsChart data={topProducts.map(product => ({ id: product.product_id, name: product.product_name, quantity_sold: product.quantity }))} /> : <div className="brew-empty"><Package size={22} /><p>No completed product sales in this period.</p></div>}
                </Panel>
            </div>

            <div className="mb-4 grid gap-4 xl:grid-cols-2">
                <Panel title="Orders by status" note="Order counts for the selected date and filters.">
                    <OrdersByStatusChart data={statusChartData} />
                </Panel>
                <Panel title="Branch comparison" note="Net collected sales by branch for the selected period.">
                    <div className="space-y-3">{branchComparison.map(branch => <div key={branch.branch_id}>
                        <div className="mb-1 flex justify-between gap-3 text-xs"><span className="font-medium text-[#49362c]">{branch.branch}</span><span>{formatCurrency(branch.net_sales)} · {branch.paid_orders} paid orders</span></div>
                        <div className="h-2 overflow-hidden rounded-full bg-[#f0ebe5]"><div className="h-full rounded-full bg-[#805840]" style={{ width: `${Math.max(0, (branch.net_sales / maxBranchSales) * 100)}%` }} /></div>
                    </div>)}{!branchComparison.length && <div className="brew-empty"><Package size={22} /><p>No branches found.</p></div>}</div>
                </Panel>
            </div>

            <div className="mb-4 grid gap-4 xl:grid-cols-2">
                <Panel title="Payment method breakdown" note="Pending, rejected, and failed payments are excluded from collections.">
                    <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Method</th><th>Orders</th><th>Collected</th><th>Refunds</th><th>Net</th></tr></thead><tbody>{breakdown.map(row => <tr key={row.method}><td className="capitalize">{row.method}</td><td>{row.count}</td><td>{formatCurrency(row.amount)}</td><td>{formatCurrency(row.refunds)}</td><td>{formatCurrency(row.net)}</td></tr>)}</tbody></table></div>
                </Panel>
                <Panel title="Daily closing reports" note="Reports are saved per branch and closing date; generating the same date refreshes its snapshot.">
                    <form className="mb-3 flex flex-wrap items-end gap-2" onSubmit={event => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.post('/admin/reports/daily-closing', { closing_date: form.get('closing_date'), branch_id: form.get('branch_id') }, { preserveScroll: true });
                    }}>
                        <label className="text-xs text-[#81766d]">Closing date<input className="brew-control mt-1 block" type="date" name="closing_date" defaultValue={filters.date ?? new Date().toISOString().slice(0, 10)} required /></label>
                        <label className="text-xs text-[#81766d]">Branch<select className="brew-control mt-1 block" name="branch_id" defaultValue={selectedBranch ?? ''}><option value="">All branches</option>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select></label>
                        <button className="brew-button">Generate closing</button>
                    </form>
                    <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Date / branch</th><th>Orders</th><th>Cash</th><th>GCash</th><th>Refunds</th><th>Net</th><th></th></tr></thead><tbody>{closingReports.map(report => <tr key={report.id}><td>{report.closing_date}<small className="block">{report.branch}</small></td><td>{report.total_orders}</td><td>{formatCurrency(report.cash_sales)}</td><td>{formatCurrency(report.gcash_sales)}</td><td>{formatCurrency(report.refund_total)}</td><td>{formatCurrency(report.net_sales)}</td><td><Link className="brew-text-link" href={`/admin/reports/daily-closing/${report.id}`}>Details <ArrowRight size={13} /></Link></td></tr>)}</tbody></table>{!closingReports.length && <div className="brew-empty"><Clock3 size={22} /><p>No saved closing reports yet.</p></div>}</div>
                </Panel>
            </div>

            <Panel title="Collected transactions" note="One settled payment per order is included; processed refunds are shown as separate negative entries.">
                <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Type / order</th><th>Customer / branch</th><th>Payment method</th><th>Date</th><th>Status / reason</th><th className="text-right">Amount</th></tr></thead><tbody>
                    {ledgerRows.map((row, index) => <tr key={`${row.type}-${row.id}-${index}`}><td>{row.type} · {row.order_number}</td><td>{row.customer}<small className="block">{row.branch}</small></td><td className="capitalize">{row.method.replaceAll('_', ' ')}</td><td>{formatDate(row.date)}</td><td>{row.type === 'Sale' ? `${row.detail} · ${row.order_status ?? 'unknown'}` : row.detail}</td><td className={`text-right ${row.type === 'Refund' ? 'text-red-700' : ''}`}>{formatCurrency(row.signedAmount)}</td></tr>)}
                </tbody></table>{!ledgerRows.length && <div className="brew-empty"><Wallet size={22} /><p>No collected transactions in this selection.</p></div>}</div>
            </Panel>
        </>}

        {module === 'closing-detail' && closingReport && <div className="space-y-4">
            <div className="flex items-center justify-between"><div><p className="brew-overline">{closingReport.branch} · {closingReport.closing_date}</p><p className="mt-1 text-sm text-[#81766d]">Generated {closingReport.generated_at ? formatDate(closingReport.generated_at) : '—'}{closingReport.generated_by ? ` by ${closingReport.generated_by}` : ''}</p></div><Link className="brew-button" href="/admin/reports">Back to reports</Link></div>
            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Metric label="Total orders" value={String(closingReport.total_orders)} />
                <Metric label="Completed orders" value={String(closingReport.completed_orders)} />
                <Metric label="Cancelled orders" value={String(closingReport.cancelled_orders)} />
                <Metric label="Cash sales" value={formatCurrency(closingReport.cash_sales)} />
                <Metric label="GCash sales" value={formatCurrency(closingReport.gcash_sales)} />
                <Metric label="Other settled sales" value={formatCurrency(closingReport.other_sales)} />
                <Metric label="Gross sales" value={formatCurrency(closingReport.gross_sales)} />
                <Metric label="Processed refunds" value={formatCurrency(closingReport.refund_total)} />
                <Metric label="Net sales" value={formatCurrency(closingReport.net_sales)} />
            </section>
        </div>}

        {module === 'refunds' && <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Cancellation register" note="Cancellations retain the order, payment, responsible employee, and cancellation timestamp.">
                <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Order</th><th>Customer / branch</th><th>Reason</th><th>Employee / time</th><th>Status</th><th>Review</th></tr></thead><tbody>
                    {cancellations.map(cancellation => <tr key={cancellation.id}><td className="font-semibold">{cancellation.order_number ?? `#${cancellation.id}`}</td><td>{cancellation.customer_name ?? 'Walk-in'}<small className="block">{cancellation.branch_name ?? 'Unassigned'}</small></td><td>{cancellation.reason}</td><td>{cancellation.employee_name ?? '—'}<small className="block">{cancellation.cancelled_at ? formatDate(cancellation.cancelled_at) : '—'}</small></td><td>{cancellation.status}</td><td>{cancellation.status === 'pending' && <div className="flex gap-1"><button className="brew-button px-2 py-1" onClick={() => router.post(`/admin/cancellations/${cancellation.id}/review`, { decision: 'approve' })}>Approve</button><button className="brew-control px-2 py-1" onClick={() => router.post(`/admin/cancellations/${cancellation.id}/review`, { decision: 'reject' })}>Reject</button></div>}</td></tr>)}
                </tbody></table>{!cancellations.length && <div className="brew-empty"><Package size={22} /><p>No cancellations recorded.</p></div>}</div>
            </Panel>
            <Panel title="Refund register" note="Approved refunds remain pending until an employee confirms actual processing with a reference.">
                <div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Order</th><th>Customer</th><th>Reason</th><th>Status</th><th>Amount</th><th>Approval / processing</th></tr></thead><tbody>
                    {refunds.map(refund => <tr key={refund.id}><td className="font-semibold">{refund.order_number ?? `#${refund.id}`}</td><td>{refund.customer_name ?? 'Walk-in'}</td><td>{refund.reason}</td><td>{refund.status}</td><td>{formatCurrency(Number(refund.amount))}</td><td>
                        {refund.authorized_at && <small className="mb-1 block">Authorized by {refund.authorized_employee ?? 'employee'} · {formatDate(refund.authorized_at)}</small>}
                        {refund.status === 'pending' && <div className="flex gap-1"><button className="brew-button px-2 py-1" onClick={() => router.post(`/admin/refunds/${refund.id}/review`, { decision: 'approve' })}>Approve</button><button className="brew-control px-2 py-1" onClick={() => router.post(`/admin/refunds/${refund.id}/review`, { decision: 'reject' })}>Reject</button></div>}
                        {refund.status === 'approved_manual' && <button className="brew-button px-2 py-1" onClick={() => { if (!window.confirm('Confirm that the refund has actually been sent to the customer?')) return; const processing_reference = window.prompt('Refund transaction/reference number'); if (processing_reference?.trim()) router.post(`/admin/refunds/${refund.id}/process`, { processing_confirmation: true, processing_reference }); }}>Confirm processed</button>}
                        {refund.processed_at && <small className="block">Processed by {refund.processed_employee ?? 'employee'} · {formatDate(refund.processed_at)} · {refund.processing_reference}</small>}
                    </td></tr>)}
                </tbody></table>{!refunds.length && <div className="brew-empty"><Package size={22} /><p>No refund requests.</p></div>}</div>
            </Panel>
        </div>}
    </main></AppLayout>;
}

function Metric({ label, value }: { label: string; value: string }) { return <article className="brew-metric"><p className="brew-overline">{label}</p><p className="mt-3 text-2xl font-semibold">{value}</p></article>; }

function BranchCreateForm() {
    const form = useForm({ name: '', code: '', address: '' });
    return <form className="space-y-2" onSubmit={event => { event.preventDefault(); form.post('/admin/branches', { preserveScroll: true, onSuccess: () => form.reset() }); }}><input className="brew-control w-full" placeholder="Branch name" value={form.data.name} onChange={event => form.setData('name', event.target.value)} required /><input className="brew-control w-full uppercase" placeholder="Unique code" value={form.data.code} onChange={event => form.setData('code', event.target.value.toUpperCase())} required /><input className="brew-control w-full" placeholder="Address" value={form.data.address} onChange={event => form.setData('address', event.target.value)} /><button className="brew-button">Add branch</button></form>;
}
