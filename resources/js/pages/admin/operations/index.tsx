import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Check, Clock3, Coffee, Download, Package, RefreshCw, Search, Wallet } from 'lucide-react';
import { useEffect, useState } from 'react';

type Branch = { id: number; name: string; code: string; address?: string | null; is_active?: boolean; users?: { id: number; name: string; role?: string }[] };
type Item = { name: string; quantity: number; notes: string | null };
type Order = { id: number; number: string; customer: string; branch: string; status: string; total: number; created_at: string; items: Item[]; status_history?: { from_status: string | null; to_status: string; employee: string; branch: string | null; notes: string | null; created_at: string }[] };
type PaymentRow = { id: number; order_id: number; order_number: string; customer: string; branch: string; method: string; amount: number; status: string; order_status: string | null; verification_status: string | null; rejection_reason: string | null; reference: string | null; receipt_url: string | null; date: string; verification: { id: number; status: string; reference_number: string } | null };
type Refund = { id: number; order_number: string; customer_name: string | null; branch_name: string | null; reason: string; amount: number; status: string; authorized_employee?: string | null; authorized_at?: string | null; processed_employee?: string | null; processed_at?: string | null; processing_reference?: string | null };
type Cancellation = { id: number; order_number: string | null; customer_name: string | null; branch_name: string | null; reason: string; status: string; employee_name?: string | null; cancelled_at?: string | null };
type Employee = { id: number; name: string; email: string; role: string; is_active: boolean };
type Props = { module: string; title: string; branches: Branch[]; selectedBranch: number | null; orders?: Order[]; payments?: PaymentRow[]; refunds?: Refund[]; cancellations?: Cancellation[]; employees?: Employee[]; filters?: { start_date?: string; end_date?: string; payment_method?: string }; summary?: { gross: number; refunds: number; net: number; orders: number; average: number }; breakdown?: { method: string; count: number; amount: number; refunds: number; net: number }[] };

const moduleHref: Record<string, string> = { kitchen: '/admin/kitchen', payments: '/admin/payments', handoff: '/admin/handoff', reports: '/admin/reports', refunds: '/admin/refunds', branches: '/admin/branches' };

function Panel({ children, title, note }: { children: React.ReactNode; title: string; note?: string }) {
    return <section className="brew-panel"><div className="brew-panel-heading"><div><h2>{title}</h2>{note && <p>{note}</p>}</div></div><div className="mt-4">{children}</div></section>;
}

export default function Operations({ module, title, branches, selectedBranch, orders = [], payments = [], refunds = [], cancellations = [], employees = [], filters = {}, summary, breakdown = [] }: Props) {
    const [search, setSearch] = useState('');
    useEffect(() => {
        if (module !== 'kitchen') return;
        const timer = window.setInterval(() => router.reload({ only: ['orders'] }), 20000);
        return () => window.clearInterval(timer);
    }, [module]);
    const filteredOrders = orders.filter(order => `${order.number} ${order.customer} ${order.branch}`.toLowerCase().includes(search.toLowerCase()));
    const branchFilter = <select aria-label="Branch" className="brew-control" value={selectedBranch ?? ''} onChange={event => router.get(moduleHref[module], event.target.value ? { branch_id: event.target.value } : {}, { preserveState: true })}><option value="">{branches.length > 1 ? 'All branches' : branches[0]?.name ?? 'No branch assigned'}</option>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select>;

    return <AppLayout><Head title={title} /><main className="brew-page">
        <header className="brew-page-heading"><div><div className="brew-overline mb-2">BREWSYNC <span className="mx-2 text-[#bba99c]">/</span> OPERATIONS</div><h1>{title}</h1><p>Branch activity and records from your live workspace.</p></div><div className="flex flex-wrap items-center gap-2">{branchFilter}<Link href="/admin/orders" className="brew-button"><Search size={15} /> Order history</Link></div></header>

        {module === 'kitchen' && <><div className="mb-4 flex items-center gap-2 rounded-xl border border-[#dce9dc] bg-[#e8f0e8] px-4 py-3 text-xs text-[#58735e]"><RefreshCw size={15} /> Kitchen queue refreshes automatically every 20 seconds.</div><div className="grid gap-4 xl:grid-cols-4">{(['pending', 'confirmed', 'preparing', 'ready'] as const).map(status => <Panel key={status} title={status[0].toUpperCase() + status.slice(1)} note={`${filteredOrders.filter(order => order.status === status).length} active tickets`}><div className="space-y-3">{filteredOrders.filter(order => order.status === status).map(order => <article key={order.id} className="rounded-xl border border-[#e9e1d7] bg-[#fffefa] p-4"><div className="flex justify-between"><div><strong className="text-lg">{order.number}</strong><p className="text-xs text-[#8d7e73]">{order.branch} · {order.customer}</p></div><span className="brew-pill brew-pill-gold">{order.status}</span></div><div className="my-3 space-y-1 border-y border-[#f0ebe5] py-3 text-sm">{order.items.map((item, index) => <div key={`${item.name}-${index}`}><span>{item.quantity} × {item.name}</span>{item.notes && <p className="text-xs text-[#98745e]">{item.notes}</p>}</div>)}</div><div className="flex items-center justify-between text-xs text-[#8d7e73]"><span><Clock3 className="mr-1 inline" size={13} />{formatDate(order.created_at)}</span>{status !== 'ready' && <form method="post" action={`/admin/kitchen/${order.id}`} onSubmit={event => { event.preventDefault(); const nextStatus = status === 'pending' ? 'confirmed' : status === 'confirmed' ? 'preparing' : 'ready'; router.patch(`/admin/kitchen/${order.id}`, { status: nextStatus }); }}><button className="brew-button px-3 py-2">{status === 'pending' ? 'Confirm order' : status === 'confirmed' ? 'Start preparing' : 'Mark ready'} <ArrowRight size={14} /></button></form>}</div></article>)}{!filteredOrders.some(order => order.status === status) && <div className="brew-empty"><Coffee size={22} /><p>No {status} orders.</p></div>}</div></Panel>)}</div></>}

        {module === 'handoff' && <div className="grid gap-4 xl:grid-cols-[1.1fr_.9fr]"><Panel title="Ready for handoff" note="Only kitchen ready orders with completed payments are listed."><div className="space-y-3">{filteredOrders.map(order => <article key={order.id} className="rounded-xl border border-[#e9e1d7] p-4"><div className="flex items-start justify-between"><div><strong className="text-lg">{order.number}</strong><p className="text-xs text-[#8d7e73]">{order.customer} · {order.branch}</p></div><strong>{formatCurrency(order.total)}</strong></div><p className="my-3 text-sm">{order.items.map(item => `${item.quantity} × ${item.name}`).join(' · ')}</p><form onSubmit={event => { event.preventDefault(); router.post(`/admin/handoff/${order.id}`); }}><button className="brew-button"><Check size={15} /> Confirm pickup</button></form></article>)}{!filteredOrders.length && <div className="brew-empty"><Check size={22} /><p>No paid orders waiting for pickup.</p></div>}</div></Panel><Panel title="Handoff workflow"><p className="text-sm leading-6 text-[#81766d]">An order appears here only after the kitchen marks it ready and its payment is completed. Pickup confirmation records the employee and timestamp, then moves the order to completed.</p></Panel></div>}

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

        {module === 'reports' && <><div className="mb-4 flex flex-wrap items-center gap-2"><label className="brew-overline">From <input className="brew-control ml-1" type="date" value={filters.start_date ?? ''} onChange={event => router.get('/admin/reports', { ...(selectedBranch ? { branch_id: selectedBranch } : {}), ...filters, start_date: event.target.value }, { preserveState: true })} /></label><label className="brew-overline">To <input className="brew-control ml-1" type="date" value={filters.end_date ?? ''} onChange={event => router.get('/admin/reports', { ...(selectedBranch ? { branch_id: selectedBranch } : {}), ...filters, end_date: event.target.value }, { preserveState: true })} /></label><select aria-label="Filter payment method" className="brew-control" value={filters.payment_method ?? ''} onChange={event => router.get('/admin/reports', { ...(selectedBranch ? { branch_id: selectedBranch } : {}), ...filters, payment_method: event.target.value }, { preserveState: true })}><option value="">All payment methods</option>{['cash', 'gcash', 'card', 'digital', 'midtrans', 'other'].map(method => <option key={method} value={method}>{method}</option>)}</select><Link className="brew-button" href={`/admin/reports/export?${new URLSearchParams({ ...(selectedBranch ? { branch_id: String(selectedBranch) } : {}), ...(filters.start_date ? { start_date: filters.start_date } : {}), ...(filters.end_date ? { end_date: filters.end_date } : {}), ...(filters.payment_method ? { payment_method: filters.payment_method } : {}) }).toString()}`}><Download size={14} /> CSV</Link><button className="brew-control" onClick={() => window.print()}>Print report</button></div><section className="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><Metric label="Gross sales" value={formatCurrency(summary?.gross ?? 0)} /><Metric label="Refunds" value={formatCurrency(summary?.refunds ?? 0)} /><Metric label="Net sales" value={formatCurrency(summary?.net ?? 0)} /><Metric label="Paid orders" value={String(summary?.orders ?? 0)} /></section><Panel title="Payment reconciliation" note="Collections less processed manual refunds in the selected branch and date range."><div className="overflow-x-auto"><table className="brew-table"><thead><tr><th>Payment method</th><th>Transactions</th><th>Gross</th><th>Refunds</th><th className="text-right">Net collections</th></tr></thead><tbody>{breakdown.map(row => <tr key={row.method}><td className="capitalize">{row.method}</td><td>{row.count}</td><td>{formatCurrency(row.amount)}</td><td>{formatCurrency(row.refunds)}</td><td className="text-right">{formatCurrency(row.net)}</td></tr>)}</tbody></table>{!breakdown.length && <div className="brew-empty"><Wallet size={22} /><p>No completed collections in this selection.</p></div>}</div></Panel></>}

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
