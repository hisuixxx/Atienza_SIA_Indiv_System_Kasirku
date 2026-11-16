import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowDownRight, ArrowRight, Check, Coffee, Flame, Menu, PackageCheck, ShoppingBag, Sparkles, Star, Store } from 'lucide-react';
import { useState } from 'react';

const features = [
    { icon: ShoppingBag, title: 'Order Easily', body: 'Experience the signature coffee, from first click to pickup.' },
    { icon: PackageCheck, title: 'Always Ready', body: 'Whatever you want, we will deliver it to you in a moment.' },
    { icon: Store, title: 'Quality Service', body: 'Keep teams, daily service, and sales visible across your café locations.' },
];

export default function Landing() {
    const [menuOpen, setMenuOpen] = useState(false);
    const { auth } = usePage<{ auth?: { user?: any } }>().props;
    const user = auth?.user;

    return (
        <>
            <Head title="Coffee, connected" />
            <div className="min-h-screen overflow-hidden bg-[#f7f4ef] text-[#33231d]">
                <header className="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-12">
                    <Link href="/" className="flex items-center gap-2.5" aria-label="BrewSync home">
                        <span className="grid size-10 place-items-center rounded-xl bg-[#33231d] text-[#f7f4ef]"><Coffee size={21} /></span>
                        <span className="text-xl font-semibold tracking-tight text-[#33231d]">BrewSync</span>
                    </Link>
                    <button className="rounded-lg border border-[#e5dbd0] p-2 md:hidden" aria-label="Toggle navigation" onClick={() => setMenuOpen(!menuOpen)}><Menu size={19} /></button>
                    <nav className={`${menuOpen ? 'flex' : 'hidden'} absolute top-[72px] right-5 left-5 flex-col gap-4 rounded-2xl border border-[#eadfd3] bg-[#fffefa] p-5 shadow-lg md:static md:flex md:flex-row md:items-center md:border-0 md:bg-transparent md:p-0 md:shadow-none`}>
                        
                        {user ? (
                            <div className="flex items-center gap-3">
                                <Link
                                    href={user.role === 'customer' ? '/orders' : '/admin/dashboard'}
                                    className="inline-flex items-center justify-center rounded-lg border border-[#dfd2c4] bg-[#fffefa] px-4 py-2 text-sm font-semibold text-[#493329] transition hover:bg-[#f2ebe2]"
                                >
                                    {user.role === 'customer' ? 'My Orders' : 'Dashboard'}
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="text-xs text-[#8d7e73] transition hover:text-[#33231d]"
                                >
                                    Logout
                                </Link>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2.5">
                                <Link
                                    href="/login"
                                    className="inline-flex items-center justify-center rounded-lg border border-[#dfd2c4] bg-[#fffefa] px-4 py-2 text-sm font-semibold text-[#493329] transition hover:bg-[#f2ebe2]"
                                >
                                    Login
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#805840] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#694631]"
                                >
                                    Create Account
                                </Link>
                            </div>
                        )}
                    </nav>
                </header>

                <main>
                    <section className="mx-auto grid max-w-7xl items-center gap-12 px-5 pt-10 pb-20 sm:px-8 md:pt-16 lg:grid-cols-[1.05fr_.95fr] lg:px-12 lg:pb-28">
                        <div className="relative z-10">
                            <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-[#e9dfd3] bg-[#fffefa] px-3 py-1.5 text-xs font-medium text-[#735b49]"><Sparkles size={14} className="text-[#a97951]" /> A calmer way to run your café</div>
                            <h1 className="max-w-2xl font-serif text-5xl leading-[1.04] tracking-[-.045em] text-[#33231d] sm:text-6xl lg:text-[4.4rem]">Exceptional Coffee.<br /><span className="text-[#805840]">Lighten your day with just One Sip.</span></h1>
                            <p className="mt-6 max-w-xl text-base leading-7 text-[#76675d] sm:text-lg">Together in one warm, easy-to-use workspace for your café.</p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link href="/menu" className="inline-flex items-center gap-2 rounded-lg bg-[#805840] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#694631]">Explore the menu <ArrowRight size={16} /></Link>
                            </div>
                        </div>

                        {/* Right-side Dynamic Floating Coffee Showcase */}
                        <div className="relative mx-auto w-full max-w-[560px]">
                            {/* Decorative ambient coffee aura */}
                            <div className="pointer-events-none absolute -top-10 -right-6 size-56 rounded-full bg-[#e8d5be]/70 blur-3xl" />
                            <div className="pointer-events-none absolute -bottom-10 -left-6 size-60 rounded-full bg-[#d7be9f]/50 blur-3xl" />
                            <div className="pointer-events-none absolute top-1/2 left-1/2 size-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#f2e6d6]/60 blur-2xl" />

                            {/* Floating decorative coffee bean accent */}
                            <div className="absolute -top-3 left-8 z-20 flex size-9 -rotate-12 items-center justify-center rounded-full bg-[#3d271d] text-[#f7f4ef] shadow-lg shadow-[#3d271d]/20 transition-transform hover:scale-110">
                                <Coffee size={16} />
                            </div>

                            <div className="absolute -bottom-2 right-12 z-20 hidden items-center gap-1.5 rounded-full border border-[#e5d8cb] bg-[#fffefa]/95 px-3 py-1 text-[11px] font-semibold text-[#805840] shadow-md backdrop-blur-sm sm:flex">
                                <Sparkles size={12} className="text-[#a97951]" />
                                <span>Single-origin Benguet & Mount Apo</span>
                            </div>

                            {/* Main Hero Visual Card: Layered Iced Spanish Latte */}
                            <div className="group relative overflow-hidden rounded-[2.5rem] border border-[#e8ded3] bg-[#fffefa] p-3.5 shadow-[0_32px_80px_rgba(73,51,41,0.14)] transition-all duration-500 hover:shadow-[0_40px_90px_rgba(73,51,41,0.18)] sm:p-4">
                                <div className="relative aspect-[4/5] w-full overflow-hidden rounded-[2rem] sm:aspect-[1/1] md:aspect-[4/5]">
                                    <img
                                        src="https://images.unsplash.com/photo-1517256064527-09c73fc73e38?auto=format&fit=crop&w=1200&q=85"
                                        alt="Specialty Iced Spanish Latte with espresso swirl"
                                        className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                    />
                                    {/* Gradient overlay for readability and atmospheric depth */}
                                    <div className="absolute inset-0 bg-gradient-to-t from-[#2c1b14]/85 via-[#2c1b14]/20 to-transparent" />

                                    {/* Floating Top Tag inside hero */}
                                    <div className="absolute top-4 left-4 flex items-center gap-1.5 rounded-full border border-white/20 bg-black/30 px-3 py-1.5 text-xs font-medium text-white shadow-sm backdrop-blur-md">
                                        <Sparkles size={13} className="text-[#f3caa4]" />
                                        <span>Signature Cold Specialty</span>
                                    </div>

                                    {/* Bottom Content within Hero Image */}
                                    <div className="absolute right-4 bottom-4 left-4">
                                        <div className="flex items-end justify-between gap-3">
                                            <div>
                                                <p className="text-[11px] font-semibold tracking-[0.18em] text-[#d9c5b2] uppercase">Signature Roast</p>
                                                <h3 className="mt-1 font-serif text-2xl font-bold tracking-tight text-white sm:text-3xl">
                                                    Iced Spanish Latte
                                                </h3>
                                                <p className="mt-1 line-clamp-1 text-xs text-[#e8ded3]/90">
                                                    Double espresso · Condensed milk · Silky dairy · Ice
                                                </p>
                                            </div>
                                            <div className="flex flex-col items-end">
                                                <span className="text-xs text-[#d9c5b2]">Handcrafted</span>
                                                <span className="text-xl font-bold text-white sm:text-2xl">₱175</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Floating Satellite 1: Top-Right Artisan Flat White / Latte Art */}
                            <div className="absolute -top-6 -right-3 z-30 flex items-center gap-3 rounded-2xl border border-[#eadfd3] bg-[#fffefa]/95 p-3 shadow-[0_18px_40px_rgba(73,51,41,0.13)] backdrop-blur-md transition-transform duration-300 hover:-translate-y-1 sm:-right-6 sm:p-3.5">
                                <img
                                    src="https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=200&q=80"
                                    alt="Silky Flat White with Latte Art"
                                    className="size-12 rounded-xl object-cover shadow-sm sm:size-14"
                                />
                                <div>
                                    <div className="flex items-center gap-1 text-[11px] font-bold text-[#805840]">
                                        <Star size={12} className="fill-[#805840]" />
                                        <span>4.9 / 5.0</span>
                                    </div>
                                    <h4 className="text-xs font-semibold text-[#33231d] sm:text-sm">Silky Flat White</h4>
                                    <p className="text-[10px] text-[#88786c]">Artisan rosetta crema</p>
                                </div>
                            </div>

                            {/* Floating Satellite 2: Bottom-Left Extraction Details */}
                            <div className="absolute -bottom-6 -left-3 z-30 flex items-center gap-3 rounded-2xl border border-[#eadfd3] bg-[#fffefa]/95 p-3 shadow-[0_20px_45px_rgba(73,51,41,0.13)] backdrop-blur-md transition-transform duration-300 hover:-translate-y-1 sm:-left-6 sm:p-3.5">
                                <div className="grid size-11 place-items-center rounded-xl bg-[#f2e7da] text-[#805840]">
                                    <Flame size={20} />
                                </div>
                                <div>
                                    <span className="text-[10px] font-bold tracking-wider text-[#98745e] uppercase">Fresh Extraction</span>
                                    <h4 className="text-xs font-semibold text-[#33231d] sm:text-sm">9-Bar Espresso</h4>
                                    <p className="text-[10px] text-[#88786c]">Rich chocolate & caramel notes</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="how-it-works" className="border-y border-[#ebe2d8] bg-[#fffefa]">
                        <div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-12 lg:py-20">
                            <div className="max-w-xl"><p className="text-xs font-semibold tracking-[.16em] text-[#98745e] uppercase">A better daily rhythm</p><h2 className="mt-3 font-serif text-3xl leading-tight tracking-tight sm:text-4xl">From our mugs to yours.<br />Have a sip of the Haven's Specialty.</h2></div>
                            <div id="for-cafes" className="mt-9 grid gap-4 md:grid-cols-3">{features.map(({ icon: Icon, title, body }, index) => <article className="rounded-2xl border border-[#eee7de] bg-[#fffefa] p-5 sm:p-6" key={title}><div className="flex items-center justify-between"><span className="grid size-11 place-items-center rounded-xl bg-[#f1e9df] text-[#805840]"><Icon size={20} /></span><span className="font-serif text-2xl text-[#d5c6b6]">0{index + 1}</span></div><h3 className="mt-5 text-base font-semibold">{title}</h3><p className="mt-2 text-sm leading-6 text-[#81766d]">{body}</p></article>)}</div>
                        </div>
                    </section>

                    <section className="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-5 py-14 sm:px-8 md:flex-row md:items-center lg:px-12">
                        <div><p className="text-xs font-semibold tracking-[.16em] text-[#98745e] uppercase">Come on in</p><h2 className="mt-2 font-serif text-3xl tracking-tight">Your next cup is waiting.</h2><p className="mt-2 text-sm text-[#81766d]">Browse what’s brewing, or sign in to your café workspace.</p></div>
                        <div className="flex flex-wrap gap-3"><Link href="/menu" className="inline-flex items-center gap-2 rounded-lg bg-[#805840] px-5 py-3 text-sm font-semibold text-white hover:bg-[#694631]">Browse menu <ArrowRight size={15} /></Link></div>
                    </section>
                </main>
                <footer className="border-t border-[#e9dfd3] bg-[#33231d] text-[#eee5da]"><div className="mx-auto flex max-w-7xl flex-col gap-20 px-10 py-6 text-xs sm:flex-row sm:items-center sm:justify-center sm:px-8 lg:px-12"><div className="flex items-center gap-5 font-semibold"><Coffee size={15} /> BrewSync <span className="font-normal text-[#bba99c]">· Café operations, connected.</span></div><div className="flex gap-4 text-[#cbbbaf]"></div></div></footer>
            </div>
        </>
    );
}
