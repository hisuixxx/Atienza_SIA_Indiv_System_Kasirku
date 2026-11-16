import { formatCurrency } from '@/lib/utils';
import { type CartItem, type Category, type CustomizationOption, type Pagination, type Product, type ProductPhoto } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    Coffee,
    Flame,
    ImageIcon,
    Menu,
    Minus,
    Plus,
    Search,
    ShoppingBag,
    ShoppingCart,
    Sparkles,
    Star,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

interface Props {
    products: Product[];
    categories: Category[];
    pagination?: Pagination;
    selectedProduct?: Product;
}

export default function CustomerIndex({ products: initialProducts, categories, pagination, selectedProduct: initialSelectedProduct }: Props) {
    const { auth } = usePage<{ auth?: { user?: any } }>().props;
    const user = auth?.user;

    // State
    const [products, setProducts] = useState<Product[]>(initialProducts);
    const [searchTerm, setSearchTerm] = useState('');
    const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
    const [categoryFilter, setCategoryFilter] = useState<string>('all');
    const [cart, setCart] = useState<CartItem[]>([]);
    const [isCartOpen, setIsCartOpen] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Customization Modal State
    const [customizingProduct, setCustomizingProduct] = useState<Product | null>(initialSelectedProduct || null);
    const [isCustomizeOpen, setIsCustomizeOpen] = useState(Boolean(initialSelectedProduct));
    const [selectedOptions, setSelectedOptions] = useState<Record<number, CustomizationOption>>({});
    const [itemQuantity, setItemQuantity] = useState(1);
    const [itemNotes, setItemNotes] = useState('');
    const [orderSuccessToast, setOrderSuccessToast] = useState<string | null>(null);

    // Infinite scroll state
    const [currentPage, setCurrentPage] = useState(pagination?.current_page || 1);
    const [hasMorePages, setHasMorePages] = useState(pagination?.has_more_pages || false);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const observerRef = useRef<HTMLDivElement>(null);

    // Load cart from localStorage on mount
    useEffect(() => {
        const savedCart = localStorage.getItem('kasirku_cart');
        if (savedCart) {
            try {
                setCart(JSON.parse(savedCart));
            } catch (error) {
                console.error('Error loading cart from localStorage:', error);
                localStorage.removeItem('kasirku_cart');
            }
        }
    }, []);

    // Save cart to localStorage whenever it changes
    useEffect(() => {
        localStorage.setItem('kasirku_cart', JSON.stringify(cart));
    }, [cart]);

    // Update products when props change
    useEffect(() => {
        setProducts(initialProducts);
        setCurrentPage(pagination?.current_page || 1);
        setHasMorePages(pagination?.has_more_pages || false);
    }, [initialProducts, pagination]);

    // Debounce search term
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearchTerm(searchTerm);
        }, 400);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    // Filter products based on search term and category
    const filteredProducts = useMemo(() => {
        return products.filter((product) => {
            const matchesSearch =
                debouncedSearchTerm === '' ||
                product.name.toLowerCase().includes(debouncedSearchTerm.toLowerCase()) ||
                (product.description && product.description.toLowerCase().includes(debouncedSearchTerm.toLowerCase())) ||
                (product.category?.name && product.category.name.toLowerCase().includes(debouncedSearchTerm.toLowerCase()));

            const matchesCategory =
                categoryFilter === 'all' ||
                product.category_id.toString() === categoryFilter ||
                (product.category?.slug && product.category.slug === categoryFilter);

            return matchesSearch && matchesCategory;
        });
    }, [products, debouncedSearchTerm, categoryFilter]);

    // Infinite scroll load more
    const loadMoreProducts = useCallback(async () => {
        if (isLoadingMore || !hasMorePages) return;

        setIsLoadingMore(true);

        try {
            const params = new URLSearchParams({
                page: (currentPage + 1).toString(),
            });

            if (debouncedSearchTerm) {
                params.append('search', debouncedSearchTerm);
            }

            if (categoryFilter && categoryFilter !== 'all') {
                params.append('category', categoryFilter);
            }

            const response = await fetch(`/menu?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setProducts((prev) => [...prev, ...data.products]);
                setCurrentPage(data.pagination.current_page);
                setHasMorePages(data.pagination.has_more_pages);
            }
        } catch (error) {
            console.error('Failed to load more products:', error);
        } finally {
            setIsLoadingMore(false);
        }
    }, [currentPage, hasMorePages, isLoadingMore, debouncedSearchTerm, categoryFilter]);

    // Intersection Observer for infinite scroll
    useEffect(() => {
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && hasMorePages && !isLoadingMore) {
                    loadMoreProducts();
                }
            },
            {
                threshold: 0.1,
                rootMargin: '100px',
            },
        );

        if (observerRef.current) {
            observer.observe(observerRef.current);
        }

        return () => {
            if (observerRef.current) {
                observer.unobserve(observerRef.current);
            }
        };
    }, [loadMoreProducts, hasMorePages, isLoadingMore]);

    // Reset pagination when search or filter changes
    useEffect(() => {
        setProducts(initialProducts);
        setCurrentPage(1);
        setHasMorePages(pagination?.has_more_pages || false);
    }, [debouncedSearchTerm, categoryFilter, initialProducts, pagination]);

    // Primary photo getter
    const getProductImage = (product: Product) => {
        if (product.photos && product.photos.length > 0) {
            const primary = product.photos.find((p) => p.is_primary);
            return primary?.url || product.photos[0].url;
        }
        if (product.image_path) {
            return product.image_path;
        }
        return 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80';
    };

    // Calculate current unit price in customization modal
    const modalCalculatedUnitPrice = useMemo(() => {
        if (!customizingProduct) return 0;
        const base = Number(customizingProduct.price || customizingProduct.base_price || 0);
        const extras = Object.values(selectedOptions).reduce((sum, opt) => sum + Number(opt.additional_price || 0), 0);
        return base + extras;
    }, [customizingProduct, selectedOptions]);

    const modalTotalPrice = modalCalculatedUnitPrice * itemQuantity;

    // Open Customization Modal or Order
    const openCustomizeModal = (product: Product) => {
        setCustomizingProduct(product);
        setItemQuantity(1);
        setItemNotes('');

        // Preselect default options
        const defaults: Record<number, CustomizationOption> = {};
        if (product.customizations && product.customizations.length > 0) {
            product.customizations.forEach((customization) => {
                if (customization.options && customization.options.length > 0) {
                    const defaultOpt = customization.options.find((o) => o.is_default) || customization.options[0];
                    if (defaultOpt) {
                        defaults[customization.id] = defaultOpt;
                    }
                }
            });
        }
        setSelectedOptions(defaults);
        setIsCustomizeOpen(true);
    };

    // Authentication Guard for Ordering
    const handleOrderClick = (product: Product) => {
        if (!user) {
            // Unauthenticated: redirect to login with target redirect back to menu
            router.visit(`/login?redirect=${encodeURIComponent('/menu')}`);
            return;
        }

        // Active customer session: proceed to order / customize
        openCustomizeModal(product);
    };

    // Confirm Order in Modal
    const handleConfirmOrder = () => {
        if (!customizingProduct) return;

        // Build options summary
        const optionsList = Object.entries(selectedOptions).map(([customizationId, option]) => {
            const customization = customizingProduct.customizations?.find((c) => c.id === Number(customizationId));
            return {
                customization_id: Number(customizationId),
                customization_name: customization?.name || 'Option',
                option_id: option.id,
                option_name: option.name,
                additional_price: Number(option.additional_price || 0),
            };
        });

        const notesParts: string[] = [];
        if (optionsList.length > 0) {
            const summary = optionsList
                .filter((o) => !o.option_name.toLowerCase().includes('standard') && !o.option_name.toLowerCase().includes('none'))
                .map((o) => `${o.customization_name}: ${o.option_name}`)
                .join(', ');
            if (summary) notesParts.push(summary);
        }
        if (itemNotes.trim()) {
            notesParts.push(`Special: ${itemNotes.trim()}`);
        }
        const fullNotes = notesParts.join(' | ');

        const effectivePrice = modalCalculatedUnitPrice;

        setCart((prevCart) => {
            const existingIndex = prevCart.findIndex(
                (item) => item.product.id === customizingProduct.id && (item.notes || '') === (fullNotes || ''),
            );

            if (existingIndex > -1) {
                const updated = [...prevCart];
                updated[existingIndex] = {
                    ...updated[existingIndex],
                    quantity: updated[existingIndex].quantity + itemQuantity,
                };
                return updated;
            } else {
                return [
                    ...prevCart,
                    {
                        product: {
                            ...customizingProduct,
                            price: effectivePrice,
                        },
                        quantity: itemQuantity,
                        notes: fullNotes,
                        selected_options: optionsList,
                        unit_price: effectivePrice,
                    },
                ];
            }
        });

        setIsCustomizeOpen(false);
        setCustomizingProduct(null);

        // Toast feedback
        setOrderSuccessToast(`Added ${itemQuantity}x ${customizingProduct.name} to order.`);
        setTimeout(() => setOrderSuccessToast(null), 3000);
    };

    // Cart management
    const updateCartQuantity = (index: number, newQty: number) => {
        if (newQty <= 0) {
            removeCartItem(index);
            return;
        }
        setCart((prev) => {
            const updated = [...prev];
            updated[index] = { ...updated[index], quantity: newQty };
            return updated;
        });
    };

    const removeCartItem = (index: number) => {
        setCart((prev) => prev.filter((_, i) => i !== index));
    };

    const clearCart = () => {
        setCart([]);
    };

    const totalCartItems = useMemo(() => {
        return cart.reduce((total, item) => total + item.quantity, 0);
    }, [cart]);

    const totalCartPrice = useMemo(() => {
        return cart.reduce((total, item) => total + (item.unit_price || item.product.price) * item.quantity, 0);
    }, [cart]);

    // Guard for Checkout
    const handleProceedToCheckout = () => {
        if (!user) {
            setIsCartOpen(false);
            router.visit(`/login?redirect=${encodeURIComponent('/checkout')}`);
            return;
        }

        setIsCartOpen(false);
        router.visit('/checkout');
    };

    return (
        <>
            <Head title="Explore Menu — BrewSync Coffee" />

            <div className="min-h-screen bg-[#f7f4ef] text-[#33231d] antialiased">
                {/* Header matching landing page */}
                <header className="sticky top-0 z-40 border-b border-[#eadfd3] bg-[#f7f4ef]/90 backdrop-blur-md">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8 lg:px-12">
                        {/* Logo */}
                        <Link href="/" className="flex items-center gap-2.5 transition hover:opacity-90" aria-label="BrewSync home">
                            <span className="grid size-10 place-items-center rounded-xl bg-[#33231d] text-[#f7f4ef] shadow-sm">
                                <Coffee size={21} />
                            </span>
                            <span className="text-xl font-semibold tracking-tight text-[#33231d]">BrewSync</span>
                        </Link>

                        {/* Navigation Links */}
                        <nav className="hidden items-center gap-8 md:flex">
                            <Link href="/" className="text-sm font-medium text-[#6f6056] transition hover:text-[#33231d]">
                                Home
                            </Link>
                            <Link href="/menu" className="text-sm font-semibold text-[#805840] transition">
                                Explore Menu
                            </Link>
                        </nav>

                        {/* Actions: Cart + Header Authentication Buttons */}
                        <div className="flex items-center gap-3">
                            {/* Cart Trigger */}
                            <button
                                onClick={() => setIsCartOpen(true)}
                                className="relative inline-flex items-center gap-2 rounded-lg border border-[#dfd2c4] bg-[#fffefa] px-3.5 py-2 text-sm font-semibold text-[#493329] transition hover:bg-[#f2ebe2] active:scale-95"
                                aria-label="View shopping cart"
                            >
                                <ShoppingCart size={17} className="text-[#805840]" />
                                <span className="hidden sm:inline">Order</span>
                                {totalCartItems > 0 && (
                                    <span className="flex size-5 items-center justify-center rounded-full bg-[#805840] text-[11px] font-bold text-white shadow-sm">
                                        {totalCartItems}
                                    </span>
                                )}
                            </button>

                            {/* Authentication Buttons */}
                            {user ? (
                                <div className="hidden items-center gap-3 sm:flex">
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
                                        className="text-xs font-medium text-[#8d7e73] transition hover:text-[#33231d]"
                                    >
                                        Logout
                                    </Link>
                                </div>
                            ) : (
                                <div className="hidden items-center gap-2.5 sm:flex">
                                    <Link
                                        href={`/login?redirect=${encodeURIComponent('/menu')}`}
                                        className="inline-flex items-center justify-center rounded-lg border border-[#dfd2c4] bg-[#fffefa] px-4 py-2 text-sm font-semibold text-[#493329] transition hover:bg-[#f2ebe2]"
                                    >
                                        Login
                                    </Link>
                                    <Link
                                        href={`/register?redirect=${encodeURIComponent('/menu')}`}
                                        className="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#805840] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#694631]"
                                    >
                                        Create Account
                                    </Link>
                                </div>
                            )}

                            {/* Mobile menu trigger */}
                            <button
                                className="rounded-lg border border-[#e5dbd0] p-2 text-[#493329] md:hidden"
                                aria-label="Toggle navigation"
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            >
                                <Menu size={19} />
                            </button>
                        </div>
                    </div>

                    {/* Mobile Navigation Dropdown */}
                    {mobileMenuOpen && (
                        <div className="border-t border-[#eadfd3] bg-[#fffefa] px-5 py-4 shadow-lg md:hidden">
                            <div className="flex flex-col gap-3">
                                <Link href="/" className="text-sm font-medium text-[#6f6056] py-1.5">
                                    Home
                                </Link>
                                <Link href="/menu" className="text-sm font-semibold text-[#805840] py-1.5">
                                    Explore Menu
                                </Link>
                                <div className="my-2 border-t border-[#eadfd3]" />
                                {user ? (
                                    <div className="flex flex-col gap-2">
                                        <Link
                                            href={user.role === 'customer' ? '/orders' : '/admin/dashboard'}
                                            className="text-sm font-semibold text-[#493329]"
                                        >
                                            {user.role === 'customer' ? 'My Orders' : 'Staff Dashboard'}
                                        </Link>
                                        <Link href="/logout" method="post" as="button" className="text-left text-xs text-[#8d7e73]">
                                            Logout
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="flex flex-col gap-2 pt-1">
                                        <Link
                                            href={`/login?redirect=${encodeURIComponent('/menu')}`}
                                            className="inline-flex w-full items-center justify-center rounded-lg border border-[#dfd2c4] bg-[#fffefa] py-2 text-sm font-semibold text-[#493329]"
                                        >
                                            Login
                                        </Link>
                                        <Link
                                            href={`/register?redirect=${encodeURIComponent('/menu')}`}
                                            className="inline-flex w-full items-center justify-center rounded-lg bg-[#805840] py-2 text-sm font-semibold text-white"
                                        >
                                            Create Account
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </header>

                {/* Toast Notification */}
                {orderSuccessToast && (
                    <div className="fixed top-20 right-5 z-50 flex items-center gap-2.5 rounded-xl border border-[#dfd2c4] bg-[#fffefa] px-4 py-3 text-sm font-medium text-[#33231d] shadow-lg transition-all animate-in fade-in slide-in-from-top-4">
                        <span className="grid size-6 place-items-center rounded-full bg-[#805840] text-white">
                            <Check size={14} />
                        </span>
                        <span>{orderSuccessToast}</span>
                    </div>
                )}

                {/* Menu Hero Banner */}
                <section className="border-b border-[#eadfd3] bg-[#fffefa]">
                    <div className="mx-auto max-w-7xl px-5 pt-10 pb-8 sm:px-8 lg:px-12">
                        <div className="inline-flex items-center gap-2 rounded-full border border-[#e9dfd3] bg-[#f7f4ef] px-3.5 py-1.5 text-xs font-semibold text-[#735b49]">
                            <Sparkles size={13} className="text-[#a97951]" />
                            <span>Handcrafted Specialty Coffee</span>
                        </div>
                        <h1 className="mt-3 font-serif text-3xl font-bold tracking-tight text-[#33231d] sm:text-4xl md:text-5xl">
                            Exceptional Coffee. Made for Every Rhythm.
                        </h1>
                        <p className="mt-3 max-w-2xl text-sm leading-relaxed text-[#76675d] sm:text-base">
                            Savor our single-origin roasts, silky lattes, refreshing cold brews, and fresh treats. Every cup is dialed to
                            perfection under 9 bars of pressure.
                        </p>

                        {/* Search and Filters */}
                        <div className="mt-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            {/* Search Input */}
                            <div className="relative w-full md:max-w-md">
                                <Search size={17} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-[#9c8e82]" />
                                <input
                                    type="text"
                                    placeholder="Search by name, category, or flavor..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full rounded-xl border border-[#dfd2c4] bg-[#f7f4ef] py-2.5 pr-4 pl-10 text-sm text-[#33231d] placeholder-[#9c8e82] transition outline-none focus:border-[#805840] focus:ring-1 focus:ring-[#805840]"
                                />
                                {searchTerm && (
                                    <button
                                        onClick={() => setSearchTerm('')}
                                        className="absolute top-1/2 right-3 -translate-y-1/2 text-[#9c8e82] hover:text-[#33231d]"
                                    >
                                        <X size={15} />
                                    </button>
                                )}
                            </div>

                            {/* Category Quick Count */}
                            <div className="text-xs font-medium text-[#8d7e73]">
                                Showing <span className="font-semibold text-[#33231d]">{filteredProducts.length}</span> signature drinks & treats
                            </div>
                        </div>

                        {/* Category Pills Navigation */}
                        <div className="no-scrollbar mt-6 flex items-center gap-2 overflow-x-auto pb-2">
                            <button
                                onClick={() => setCategoryFilter('all')}
                                className={`shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition ${
                                    categoryFilter === 'all'
                                        ? 'bg-[#805840] text-white shadow-sm'
                                        : 'border border-[#dfd2c4] bg-[#f7f4ef] text-[#6f6056] hover:border-[#805840] hover:text-[#33231d]'
                                }`}
                            >
                                All Items
                            </button>
                            {categories.map((cat) => {
                                const isSelected = categoryFilter === cat.id.toString() || (cat.slug && categoryFilter === cat.slug);
                                return (
                                    <button
                                        key={cat.id}
                                        onClick={() => setCategoryFilter(cat.id.toString())}
                                        className={`shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition ${
                                            isSelected
                                                ? 'bg-[#805840] text-white shadow-sm'
                                                : 'border border-[#dfd2c4] bg-[#f7f4ef] text-[#6f6056] hover:border-[#805840] hover:text-[#33231d]'
                                        }`}
                                    >
                                        {cat.name}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Products Grid */}
                <main className="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
                    {filteredProducts.length === 0 && !isLoadingMore ? (
                        <div className="mx-auto max-w-md rounded-3xl border border-[#eadfd3] bg-[#fffefa] p-10 text-center shadow-sm">
                            <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-[#f2ebe2] text-[#805840]">
                                <Coffee size={28} />
                            </span>
                            <h3 className="mt-4 font-serif text-xl font-bold text-[#33231d]">No drinks found</h3>
                            <p className="mt-1 text-xs text-[#76675d]">
                                {searchTerm || categoryFilter !== 'all'
                                    ? 'Try adjusting your search keyword or selected category.'
                                    : 'No items are currently active on the menu.'}
                            </p>
                            {(searchTerm || categoryFilter !== 'all') && (
                                <button
                                    onClick={() => {
                                        setSearchTerm('');
                                        setCategoryFilter('all');
                                    }}
                                    className="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-[#805840] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#694631]"
                                >
                                    Reset Filters
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {filteredProducts.map((product) => {
                                const imageUrl = getProductImage(product);
                                const price = Number(product.price || product.base_price || 0);

                                return (
                                    <div
                                        key={product.id}
                                        className="group flex flex-col justify-between overflow-hidden rounded-2xl border border-[#eadfd3] bg-[#fffefa] p-4 shadow-[0_6px_20px_rgba(73,51,41,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_36px_rgba(73,51,41,0.12)]"
                                    >
                                        <div>
                                            {/* Image */}
                                            <div className="relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-[#ede5db]">
                                                <img
                                                    src={imageUrl}
                                                    alt={product.name}
                                                    className="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                                                />
                                                <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-60" />

                                                {/* Category Badge */}
                                                {product.category && (
                                                    <span className="absolute top-2.5 left-2.5 rounded-full border border-white/20 bg-black/40 px-2.5 py-0.5 text-[11px] font-medium text-white backdrop-blur-md">
                                                        {product.category.name}
                                                    </span>
                                                )}

                                                {/* Featured Badge */}
                                                {product.is_featured && (
                                                    <span className="absolute top-2.5 right-2.5 flex items-center gap-1 rounded-full border border-white/20 bg-[#805840]/90 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm backdrop-blur-md">
                                                        <Sparkles size={10} className="text-[#f7cca6]" />
                                                        <span>Featured</span>
                                                    </span>
                                                )}
                                            </div>

                                            {/* Details */}
                                            <div className="mt-3.5">
                                                <h3 className="font-serif text-base font-bold text-[#33231d] transition-colors group-hover:text-[#805840]">
                                                    {product.name}
                                                </h3>
                                                <p className="mt-1 line-clamp-2 min-h-[32px] text-xs leading-relaxed text-[#76675d]">
                                                    {product.description || 'Specialty handcrafted recipe prepared fresh by our baristas.'}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Price and Order Button */}
                                        <div className="mt-4 flex items-center justify-between border-t border-[#f0e6dc] pt-3.5">
                                            <div>
                                                <span className="text-[10px] tracking-wider text-[#98745e] uppercase">From</span>
                                                <div className="font-serif text-lg font-bold text-[#33231d]">{formatCurrency(price)}</div>
                                            </div>

                                            {/* Renamed strictly to "Order" with auth guard check */}
                                            <button
                                                onClick={() => handleOrderClick(product)}
                                                className="inline-flex items-center gap-1.5 rounded-lg bg-[#805840] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#694631] active:scale-95"
                                            >
                                                <Coffee size={14} />
                                                <span>Order</span>
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {/* Infinite Scroll Indicator */}
                    {hasMorePages && (
                        <div ref={observerRef} className="flex justify-center py-10">
                            {isLoadingMore ? (
                                <div className="flex items-center gap-2.5 text-xs font-semibold text-[#805840]">
                                    <div className="size-4 animate-spin rounded-full border-2 border-[#805840] border-t-transparent" />
                                    <span>Dialing more specialty drinks...</span>
                                </div>
                            ) : (
                                <span className="text-xs text-[#98745e]">Scroll down to load more recipes</span>
                            )}
                        </div>
                    )}
                </main>

                {/* Drink Customization & Order Dialog */}
                {isCustomizeOpen && customizingProduct && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        {/* Backdrop */}
                        <div
                            className="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                            onClick={() => setIsCustomizeOpen(false)}
                        />

                        {/* Modal Dialog Card */}
                        <div className="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-[#eadfd3] bg-[#fffefa] p-6 shadow-2xl">
                            {/* Close button */}
                            <button
                                onClick={() => setIsCustomizeOpen(false)}
                                className="absolute top-5 right-5 grid size-8 place-items-center rounded-full bg-[#f2ebe2] text-[#6f6056] transition hover:bg-[#e6dccf] hover:text-[#33231d]"
                                aria-label="Close dialog"
                            >
                                <X size={16} />
                            </button>

                            {/* Product Header */}
                            <div className="flex gap-4 border-b border-[#f0e6dc] pb-4">
                                <img
                                    src={getProductImage(customizingProduct)}
                                    alt={customizingProduct.name}
                                    className="size-20 shrink-0 rounded-2xl object-cover shadow-sm"
                                />
                                <div>
                                    <span className="text-[11px] font-semibold text-[#805840] uppercase">
                                        {customizingProduct.category?.name || 'Beverage'}
                                    </span>
                                    <h2 className="font-serif text-xl font-bold text-[#33231d]">{customizingProduct.name}</h2>
                                    <p className="mt-0.5 line-clamp-2 text-xs text-[#76675d]">
                                        {customizingProduct.description || 'Specialty handcrafted recipe.'}
                                    </p>
                                    <div className="mt-1 font-semibold text-[#805840]">
                                        Base: {formatCurrency(Number(customizingProduct.price || customizingProduct.base_price || 0))}
                                    </div>
                                </div>
                            </div>

                            {/* Customization Options */}
                            <div className="mt-4 space-y-4">
                                {customizingProduct.customizations && customizingProduct.customizations.length > 0 ? (
                                    customizingProduct.customizations.map((customization) => (
                                        <div key={customization.id} className="rounded-xl border border-[#eee5dc] bg-[#fbf9f6] p-3.5">
                                            <div className="flex items-center justify-between text-xs font-semibold text-[#33231d]">
                                                <span>{customization.name}</span>
                                                {customization.is_required && (
                                                    <span className="text-[10px] font-medium text-[#805840] uppercase">Required</span>
                                                )}
                                            </div>

                                            <div className="mt-2.5 flex flex-wrap gap-2">
                                                {customization.options?.map((option) => {
                                                    const isSelected = selectedOptions[customization.id]?.id === option.id;
                                                    const extra = Number(option.additional_price || 0);

                                                    return (
                                                        <button
                                                            key={option.id}
                                                            type="button"
                                                            onClick={() => {
                                                                setSelectedOptions((prev) => ({
                                                                    ...prev,
                                                                    [customization.id]: option,
                                                                }));
                                                            }}
                                                            className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                                                                isSelected
                                                                    ? 'bg-[#805840] text-white shadow-sm'
                                                                    : 'border border-[#dfd2c4] bg-[#fffefa] text-[#6f6056] hover:border-[#805840] hover:text-[#33231d]'
                                                            }`}
                                                        >
                                                            <span>{option.name}</span>
                                                            {extra > 0 && (
                                                                <span className={isSelected ? 'text-[#f5d9c0]' : 'text-[#805840]'}>
                                                                    (+{formatCurrency(extra)})
                                                                </span>
                                                            )}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-xs text-[#8d7e73]">
                                        Standard recipe prepared with signature espresso extraction and whole milk.
                                    </p>
                                )}

                                {/* Special Notes */}
                                <div>
                                    <label className="text-xs font-semibold text-[#33231d]">Special Requests / Notes</label>
                                    <textarea
                                        rows={2}
                                        placeholder="e.g. Extra hot, light ice, less foam..."
                                        value={itemNotes}
                                        onChange={(e) => setItemNotes(e.target.value)}
                                        className="mt-1.5 w-full rounded-xl border border-[#dfd2c4] bg-[#fbf9f6] p-2.5 text-xs text-[#33231d] placeholder-[#9c8e82] transition outline-none focus:border-[#805840] focus:ring-1 focus:ring-[#805840]"
                                    />
                                </div>

                                {/* Quantity Control & Total */}
                                <div className="flex items-center justify-between border-t border-[#f0e6dc] pt-3">
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setItemQuantity((q) => Math.max(1, q - 1))}
                                            className="grid size-8 place-items-center rounded-lg border border-[#dfd2c4] bg-[#fffefa] text-[#493329] transition hover:bg-[#f2ebe2]"
                                        >
                                            <Minus size={14} />
                                        </button>
                                        <span className="w-6 text-center text-sm font-bold text-[#33231d]">{itemQuantity}</span>
                                        <button
                                            type="button"
                                            onClick={() => setItemQuantity((q) => q + 1)}
                                            className="grid size-8 place-items-center rounded-lg border border-[#dfd2c4] bg-[#fffefa] text-[#493329] transition hover:bg-[#f2ebe2]"
                                        >
                                            <Plus size={14} />
                                        </button>
                                    </div>

                                    <div className="text-right">
                                        <span className="text-[10px] text-[#8d7e73] uppercase">Total</span>
                                        <div className="font-serif text-lg font-bold text-[#805840]">{formatCurrency(modalTotalPrice)}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Submit Order Button */}
                            <div className="mt-5">
                                <button
                                    type="button"
                                    onClick={handleConfirmOrder}
                                    className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#805840] py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[#694631] active:scale-98"
                                >
                                    <Coffee size={16} />
                                    <span>Order · {formatCurrency(modalTotalPrice)}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Shopping Cart Drawer */}
                {isCartOpen && (
                    <div className="fixed inset-0 z-50 flex justify-end">
                        <div
                            className="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                            onClick={() => setIsCartOpen(false)}
                        />

                        <div className="relative z-10 flex h-full w-full max-w-md flex-col border-l border-[#eadfd3] bg-[#fffefa] p-6 shadow-2xl">
                            {/* Drawer Header */}
                            <div className="flex items-center justify-between border-b border-[#f0e6dc] pb-4">
                                <div className="flex items-center gap-2.5">
                                    <span className="grid size-9 place-items-center rounded-xl bg-[#805840] text-white">
                                        <ShoppingCart size={17} />
                                    </span>
                                    <div>
                                        <h2 className="font-serif text-lg font-bold text-[#33231d]">Current Order</h2>
                                        <p className="text-xs text-[#8d7e73]">{totalCartItems} items selected</p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setIsCartOpen(false)}
                                    className="grid size-8 place-items-center rounded-full bg-[#f2ebe2] text-[#6f6056] hover:bg-[#e6dccf]"
                                    aria-label="Close cart"
                                >
                                    <X size={16} />
                                </button>
                            </div>

                            {/* Cart Items List */}
                            <div className="flex-1 space-y-3 overflow-y-auto py-4">
                                {cart.length === 0 ? (
                                    <div className="flex h-full flex-col items-center justify-center text-center">
                                        <span className="grid size-16 place-items-center rounded-3xl bg-[#f2ebe2] text-[#805840]">
                                            <ShoppingBag size={28} />
                                        </span>
                                        <h3 className="mt-4 font-serif text-base font-bold text-[#33231d]">Your order is empty</h3>
                                        <p className="mt-1 text-xs text-[#8d7e73]">
                                            Browse our menu and click Order to select your specialty drinks.
                                        </p>
                                    </div>
                                ) : (
                                    cart.map((item, index) => {
                                        const unitPrice = Number(item.unit_price || item.product.price);
                                        const itemTotal = unitPrice * item.quantity;

                                        return (
                                            <div
                                                key={`${item.product.id}-${index}`}
                                                className="flex gap-3 rounded-2xl border border-[#eee5dc] bg-[#fbf9f6] p-3 transition hover:border-[#dfd2c4]"
                                            >
                                                <img
                                                    src={getProductImage(item.product)}
                                                    alt={item.product.name}
                                                    className="size-16 shrink-0 rounded-xl object-cover"
                                                />
                                                <div className="flex-1">
                                                    <div className="flex items-start justify-between">
                                                        <h4 className="text-xs font-bold text-[#33231d]">{item.product.name}</h4>
                                                        <button
                                                            onClick={() => removeCartItem(index)}
                                                            className="text-[#9c8e82] hover:text-red-600"
                                                            title="Remove item"
                                                        >
                                                            <Trash2 size={13} />
                                                        </button>
                                                    </div>
                                                    {item.notes && (
                                                        <p className="mt-0.5 line-clamp-1 text-[11px] text-[#805840]">{item.notes}</p>
                                                    )}
                                                    <div className="mt-2 flex items-center justify-between">
                                                        <div className="flex items-center gap-1.5">
                                                            <button
                                                                onClick={() => updateCartQuantity(index, item.quantity - 1)}
                                                                className="grid size-6 place-items-center rounded border border-[#dfd2c4] bg-[#fffefa] text-xs text-[#493329]"
                                                            >
                                                                <Minus size={11} />
                                                            </button>
                                                            <span className="w-5 text-center text-xs font-bold text-[#33231d]">
                                                                {item.quantity}
                                                            </span>
                                                            <button
                                                                onClick={() => updateCartQuantity(index, item.quantity + 1)}
                                                                className="grid size-6 place-items-center rounded border border-[#dfd2c4] bg-[#fffefa] text-xs text-[#493329]"
                                                            >
                                                                <Plus size={11} />
                                                            </button>
                                                        </div>
                                                        <div className="text-xs font-bold text-[#805840]">{formatCurrency(itemTotal)}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })
                                )}
                            </div>

                            {/* Cart Footer */}
                            {cart.length > 0 && (
                                <div className="border-t border-[#f0e6dc] pt-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="text-xs font-medium text-[#76675d]">Subtotal</span>
                                        <span className="font-serif text-lg font-bold text-[#33231d]">
                                            {formatCurrency(totalCartPrice)}
                                        </span>
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        <button
                                            onClick={handleProceedToCheckout}
                                            className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#805840] py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[#694631] active:scale-98"
                                        >
                                            <span>Proceed to Checkout</span>
                                            <ArrowRight size={15} />
                                        </button>
                                        <button
                                            onClick={clearCart}
                                            className="text-center text-xs font-medium text-[#8d7e73] hover:text-[#33231d]"
                                        >
                                            Clear Order
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Footer */}
                <footer className="mt-20 border-t border-[#e9dfd3] bg-[#33231d] text-[#eee5da]">
                    <div className="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                        <div className="flex items-center gap-3 font-semibold">
                            <Coffee size={16} /> BrewSync <span className="font-normal text-[#bba99c]">· Café operations, connected.</span>
                        </div>
                        <div className="flex items-center gap-4 text-[#cbbbaf]">
                            <Link href="/" className="hover:text-white">
                                Home
                            </Link>
                            <Link href="/menu" className="hover:text-white">
                                Menu
                            </Link>
                            {user ? (
                                <Link href={user.role === 'customer' ? '/orders' : '/admin/dashboard'} className="hover:text-white">
                                    {user.role === 'customer' ? 'My Orders' : 'Dashboard'}
                                </Link>
                            ) : (
                                <Link href="/login" className="hover:text-white">
                                    Sign In
                                </Link>
                            )}
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
