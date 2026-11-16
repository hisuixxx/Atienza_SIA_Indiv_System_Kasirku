import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import { Head, router, useForm } from '@inertiajs/react';
import { Coffee, Minus, Plus, Search, ShoppingBag, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

type Product = { id: string; name: string; description: string | null; category: string; price: number; available: boolean; image?: string | null };
type Category = { id: number; name: string };
type Branch = { id: number; name: string };
type CartLine = { product_id: string; quantity: number; notes: string };
type Props = { products: Product[]; categories: Category[]; branches: Branch[]; selectedBranch: number | null; taxRate: number };

export default function CreateOrder({ products, categories, branches, selectedBranch, taxRate }: Props) {
    const [category, setCategory] = useState('All items');
    const [search, setSearch] = useState('');
    const form = useForm({ customer_name: 'Walk-in', branch_id: Number(selectedBranch ?? 0), service_type: 'takeout', table_number: '', notes: '', payment_method: 'cash', items: [] as CartLine[] });
    const visibleProducts = products.filter(product => (category === 'All items' || product.category.toLowerCase() === category.toLowerCase()) && `${product.name} ${product.description ?? ''}`.toLowerCase().includes(search.toLowerCase()));
    const cart = form.data.items;
    const subtotal = useMemo(() => cart.reduce((sum, line) => sum + (products.find(product => product.id === line.product_id)?.price ?? 0) * line.quantity, 0), [cart, products]);
    const tax = subtotal * taxRate;
    const total = subtotal + tax;

    const changeQuantity = (productId: string, delta: number) => {
        const existing = cart.find(line => line.product_id === productId);
        const next = existing
            ? cart.map(line => line.product_id === productId ? { ...line, quantity: line.quantity + delta } : line).filter(line => line.quantity > 0)
            : delta > 0 ? [...cart, { product_id: productId, quantity: 1, notes: '' }] : cart;
        form.setData('items', next);
    };

    return <AppLayout><Head title="New order" /><main className="brew-page">
        <header className="brew-page-heading"><div><div className="brew-overline mb-2">BREWSYNC / CASHIER WORKSPACE</div><h1>Something good, made to order.</h1><p>Menu availability is checked against branch stock when you place the order.</p></div><span className="brew-pill brew-pill-green">Cashier · Kitchen synced</span></header>
        {form.errors.items && <div className="mb-4 rounded-lg border border-[#f1d6d0] bg-[#f8e8e4] px-4 py-3 text-sm text-[#a65f50]">{form.errors.items}</div>}
        <div className="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_370px]">
            <section><div className="mb-3 flex flex-wrap items-center justify-between gap-3"><label className="relative"><Search className="absolute left-3 top-1/2 -translate-y-1/2 text-[#9a897e]" size={15} /><input className="brew-control pl-9" placeholder="Search the menu" value={search} onChange={event => setSearch(event.target.value)} /></label><span className="text-xs text-[#8d7e73]">{visibleProducts.length} available items</span></div>
                <nav className="mb-4 flex flex-wrap gap-2">{['All items', ...categories.map(item => item.name)].map(item => <button key={item} className={`brew-control ${category === item ? '!border-[#805840] !bg-[#805840] !text-white' : ''}`} onClick={() => setCategory(item)}>{item}</button>)}</nav>
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{visibleProducts.map(product => {
                    const count = cart.find(line => line.product_id === product.id)?.quantity ?? 0;
                    return <article key={product.id} className="overflow-hidden rounded-xl border border-[#ece5dc] bg-[#fffefa]">
                        {product.image ? <img src={product.image} alt="" className="h-36 w-full object-cover" /> : <div className="grid h-36 place-items-center bg-[#f1e9df] text-[#98745e]"><Coffee size={32} /></div>}
                        <div className="p-3"><div className="flex items-start justify-between gap-2"><div><h2 className="text-sm font-semibold text-[#49372d]">{product.name}</h2><p className="mt-1 line-clamp-2 text-xs text-[#988b80]">{product.description || product.category}</p></div><span className={`brew-pill ${product.available ? 'brew-pill-green' : 'brew-pill-red'}`}>{product.available ? 'Available' : 'Out of stock'}</span></div><div className="mt-3 flex items-center justify-between"><strong className="text-sm text-[#805840]">{formatCurrency(product.price)}</strong>{count === 0 ? <button disabled={!product.available} className="brew-button px-3 py-2 disabled:cursor-not-allowed disabled:opacity-40" onClick={() => changeQuantity(product.id, 1)}><Plus size={14} /> Add</button> : <div className="flex items-center gap-2"><button aria-label="Remove one" className="brew-control px-2" onClick={() => changeQuantity(product.id, -1)}><Minus size={14} /></button><strong>{count}</strong><button aria-label="Add one" className="brew-control px-2" onClick={() => changeQuantity(product.id, 1)}><Plus size={14} /></button></div>}</div></div>
                    </article>;
                })}</div>
                {!visibleProducts.length && <div className="brew-empty"><ShoppingBag size={24} /><p>No products match that menu filter.</p></div>}
            </section>
            <aside className="brew-panel xl:sticky xl:top-4"><div className="brew-panel-heading"><div><h2>Current order</h2><p>Cashier workspace · stock validated at entry</p></div><span className="brew-pill brew-pill-gold">Draft</span></div>
                <div className="mt-4 space-y-3"><label className="block text-xs text-[#8d7e73]">Branch<select className="brew-control mt-1 w-full" value={form.data.branch_id} onChange={event => { const branchId = Number(event.target.value); form.setData('branch_id', branchId); router.get('/admin/orders/create', { branch_id: branchId }, { preserveState: true, preserveScroll: true }); }} required>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select></label><label className="block text-xs text-[#8d7e73]">Customer<input className="brew-control mt-1 w-full" value={form.data.customer_name} onChange={event => form.setData('customer_name', event.target.value)} required /></label><div className="grid grid-cols-2 gap-2"><label className="block text-xs text-[#8d7e73]">Service<select className="brew-control mt-1 w-full" value={form.data.service_type} onChange={event => form.setData('service_type', event.target.value)}><option value="takeout">Takeout</option><option value="dine_in">Dine-in</option></select></label><label className="block text-xs text-[#8d7e73]">Table number<input className="brew-control mt-1 w-full" type="number" min="1" disabled={form.data.service_type !== 'dine_in'} value={form.data.table_number} onChange={event => form.setData('table_number', event.target.value)} /></label></div></div>
                <div className="my-4 max-h-[300px] space-y-3 overflow-auto border-y border-[#f0ebe5] py-3">{cart.map(line => { const product = products.find(item => item.id === line.product_id); if (!product) return null; return <div key={line.product_id}><div className="flex items-start justify-between gap-2"><div><strong className="text-sm">{product.name}</strong><p className="text-xs text-[#8d7e73]">{line.quantity} × {formatCurrency(product.price)}</p></div><div className="flex items-center gap-2"><strong className="text-sm">{formatCurrency(product.price * line.quantity)}</strong><button aria-label={`Remove ${product.name}`} onClick={() => form.setData('items', cart.filter(row => row.product_id !== line.product_id))}><Trash2 size={14} className="text-[#a65f50]" /></button></div></div><input className="brew-control mt-2 w-full" placeholder="Special instructions" value={line.notes} onChange={event => form.setData('items', cart.map(row => row.product_id === line.product_id ? { ...row, notes: event.target.value } : row))} /></div>; })}{!cart.length && <p className="py-8 text-center text-sm text-[#a79588]">Add a menu item to start.</p>}</div>
                <label className="block text-xs text-[#8d7e73]">Order note<textarea className="brew-control mt-1 w-full" rows={2} value={form.data.notes} onChange={event => form.setData('notes', event.target.value)} /></label><label className="mt-3 block text-xs text-[#8d7e73]">Payment method<select className="brew-control mt-1 w-full" value={form.data.payment_method} onChange={event => form.setData('payment_method', event.target.value)}><option value="cash">Cash</option><option value="gcash">GCash · pending verification</option><option value="card">Card · pending</option><option value="other">Other · pending</option></select></label>
                <div className="mt-4 space-y-2 text-sm"><div className="flex justify-between text-[#8d7e73]"><span>Subtotal</span><span>{formatCurrency(subtotal)}</span></div><div className="flex justify-between text-[#8d7e73]"><span>Tax ({Math.round(taxRate * 100)}%)</span><span>{formatCurrency(tax)}</span></div><div className="flex justify-between border-t border-[#f0ebe5] pt-3 text-base font-semibold"><span>Total due</span><span>{formatCurrency(total)}</span></div></div>
                <button disabled={!cart.length || form.processing || !branches.length} className="brew-button mt-4 w-full" onClick={() => form.post('/admin/orders')}>{form.processing ? 'Saving order…' : 'Place order · ' + formatCurrency(total)}</button>
            </aside>
        </div>
    </main></AppLayout>;
}
