import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { TAX_RATE } from '@/lib/constants';
import { formatCurrency } from '@/lib/utils';
import { type CartItem, type ProductPhoto } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, Coffee, ImageIcon, ShoppingCart } from 'lucide-react';
import { useEffect, useState } from 'react';

declare global {
    interface Window {
        snap: any;
    }
}

export default function CheckoutIndex() {
    const { auth } = usePage<{ auth?: { user?: any } }>().props;
    const user = auth?.user;

    const [cart, setCart] = useState<CartItem[]>([]);
    const [customerName, setCustomerName] = useState(user?.name || '');
    const [tableNumber, setTableNumber] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isInitialLoading, setIsInitialLoading] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Auth guard for checkout: customer session check
    useEffect(() => {
        if (!user) {
            router.visit(`/login?redirect=${encodeURIComponent('/checkout')}`);
        }
    }, [user]);

    // Update customer name when user loads
    useEffect(() => {
        if (user?.name && !customerName) {
            setCustomerName(user.name);
        }
    }, [user, customerName]);

    // Load cart from localStorage on mount
    useEffect(() => {
        const savedCart = localStorage.getItem('kasirku_cart');

        if (savedCart) {
            try {
                const parsedCart = JSON.parse(savedCart);
                setCart(parsedCart);
            } catch (error) {
                console.error('Error loading cart from localStorage:', error);
                setCart([]);
            }
        } else {
            setCart([]);
        }

        setIsInitialLoading(false);

        // Load Midtrans Snap script
        const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY;
        const isProduction = import.meta.env.VITE_MIDTRANS_IS_PRODUCTION === 'true';

        const snapScript = document.createElement('script');
        snapScript.src = isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
        snapScript.setAttribute('data-client-key', clientKey);

        document.head.appendChild(snapScript);

        return () => {
            if (document.head.contains(snapScript)) {
                document.head.removeChild(snapScript);
            }
        };
    }, []);

    // Redirect if cart is empty (only after initial loading is complete)
    useEffect(() => {
        if (!isInitialLoading && cart.length === 0) {
            router.visit('/menu');
        }
    }, [cart, isInitialLoading]);

    // Get primary photo
    const getPrimaryPhoto = (item: CartItem): string => {
        if (item.product.photos && item.product.photos.length > 0) {
            const primary = item.product.photos.find((photo) => photo.is_primary);
            return primary?.url || item.product.photos[0]?.url || 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80';
        }
        if (item.product.image_path) {
            return item.product.image_path;
        }
        return 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80';
    };

    // Calculate totals
    const getSubtotal = () => {
        return cart.reduce((total, item) => total + (item.unit_price || item.product.price) * item.quantity, 0);
    };

    const getTaxAmount = () => {
        return getSubtotal() * TAX_RATE;
    };

    const getTotalPrice = () => {
        return getSubtotal() + getTaxAmount();
    };

    const getTotalItems = () => {
        return cart.reduce((total, item) => total + item.quantity, 0);
    };

    // Handle payment
    const handlePayment = async () => {
        if (!customerName.trim()) {
            setErrors({ customer_name: 'Customer name is required' });
            return;
        }

        if (!tableNumber.trim() || parseInt(tableNumber) < 1) {
            setErrors({ table_number: 'A valid table number is required' });
            return;
        }

        setErrors({});
        setIsLoading(true);

        try {
            const response = await fetch('/checkout/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    customer_name: customerName,
                    table_number: parseInt(tableNumber),
                    cart: cart,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                // Store order info in localStorage
                localStorage.setItem(
                    'kasirku_current_order',
                    JSON.stringify({
                        order_id: data.order_id,
                        payment_id: data.payment_id,
                    }),
                );

                // Check if Snap is available
                if (!window.snap) {
                    setErrors({ general: 'Midtrans Snap is not ready. Please refresh the page and try again.' });
                    setIsLoading(false);
                    return;
                }

                // Open Midtrans Snap
                try {
                    window.snap.pay(data.snap_token, {
                        onSuccess: function () {
                            localStorage.removeItem('kasirku_cart');
                            router.visit(`/order/${data.order_id}/status`);
                        },
                        onPending: function () {
                            router.visit(`/order/${data.order_id}/status`);
                        },
                        onError: function (result: any) {
                            console.error('Payment error:', result);
                            alert('Payment failed. Please try again.');
                            setIsLoading(false);
                        },
                        onClose: function () {
                            setIsLoading(false);
                        },
                    });
                } catch (error) {
                    console.error('Error opening Snap:', error);
                    setErrors({ general: 'Unable to open the payment page. Please try again.' });
                    setIsLoading(false);
                }
            } else {
                setErrors({ general: data.error || 'An error occurred while processing the order' });
                setIsLoading(false);
            }
        } catch (error) {
            console.error('Checkout error:', error);
            setErrors({ general: 'A network error occurred. Please try again.' });
            setIsLoading(false);
        }
    };

    const goBack = () => {
        router.visit('/menu');
    };

    // Show loading state while checking cart
    if (isInitialLoading) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-[#f7f4ef]">
                <Head title="Checkout — BrewSync Coffee" />
                <div className="flex items-center gap-2.5 text-[#805840]">
                    <div className="size-5 animate-spin rounded-full border-2 border-[#805840] border-t-transparent"></div>
                    <span className="text-sm font-medium text-[#76675d]">Preparing your order...</span>
                </div>
            </div>
        );
    }

    if (cart.length === 0) {
        return null;
    }

    return (
        <div className="min-h-screen bg-[#f7f4ef] text-[#33231d]">
            <Head title="Checkout — BrewSync Coffee" />

            {/* Header */}
            <header className="sticky top-0 z-40 border-b border-[#eadfd3] bg-[#f7f4ef]/90 backdrop-blur-md">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={goBack}
                            className="rounded-xl border border-[#dfd2c4] bg-[#fffefa] text-[#493329] hover:bg-[#f2ebe2]"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Button>
                        <div className="flex items-center gap-2.5">
                            <span className="grid size-9 place-items-center rounded-xl bg-[#33231d] text-[#f7f4ef]">
                                <Coffee size={18} />
                            </span>
                            <div>
                                <h1 className="font-serif text-lg font-bold text-[#33231d] sm:text-xl">Checkout</h1>
                                <p className="text-xs text-[#76675d]">Review and confirm your café order</p>
                            </div>
                        </div>
                    </div>

                    <Link
                        href="/menu"
                        className="text-xs font-semibold text-[#805840] transition hover:underline"
                    >
                        Back to Menu
                    </Link>
                </div>
            </header>

            {/* Main Content */}
            <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    {/* Order Summary */}
                    <div className="rounded-2xl border border-[#eadfd3] bg-[#fffefa] p-5 shadow-[0_8px_25px_rgba(73,51,41,0.05)] sm:p-6">
                        <div className="flex items-center justify-between border-b border-[#f0e6dc] pb-4">
                            <div className="flex items-center gap-2">
                                <ShoppingCart className="h-5 w-5 text-[#805840]" />
                                <h2 className="font-serif text-lg font-bold text-[#33231d]">Order Summary</h2>
                            </div>
                            <span className="rounded-full bg-[#f2ebe2] px-3 py-1 text-xs font-semibold text-[#805840]">
                                {getTotalItems()} items
                            </span>
                        </div>

                        <div className="mt-4 space-y-3 divide-y divide-[#f5ede4]">
                            {cart.map((item, index) => {
                                const photoUrl = getPrimaryPhoto(item);
                                const unitPrice = Number(item.unit_price || item.product.price);
                                return (
                                    <div key={`${item.product.id}-${index}`} className="flex items-center gap-4 pt-3 first:pt-0">
                                        <div className="size-16 shrink-0 overflow-hidden rounded-xl bg-[#ede5db]">
                                            <img
                                                src={photoUrl}
                                                alt={item.product.name}
                                                className="h-full w-full object-cover"
                                            />
                                        </div>

                                        <div className="flex-1">
                                            <h4 className="font-serif text-sm font-bold text-[#33231d]">{item.product.name}</h4>
                                            <p className="text-xs text-[#76675d]">
                                                {formatCurrency(unitPrice)} × {item.quantity}
                                            </p>
                                            {item.notes && (
                                                <p className="mt-1 line-clamp-1 text-[11px] text-[#805840]">{item.notes}</p>
                                            )}
                                        </div>

                                        <div className="text-right font-serif font-bold text-[#33231d]">
                                            {formatCurrency(unitPrice * item.quantity)}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="mt-6 border-t border-[#f0e6dc] pt-4 space-y-2 text-sm">
                            <div className="flex justify-between text-[#76675d]">
                                <span>Subtotal</span>
                                <span>{formatCurrency(getSubtotal())}</span>
                            </div>
                            <div className="flex justify-between text-[#76675d]">
                                <span>Tax ({TAX_RATE * 100}%)</span>
                                <span>{formatCurrency(getTaxAmount())}</span>
                            </div>
                            <div className="flex justify-between border-t border-[#f0e6dc] pt-2 text-base font-bold text-[#33231d]">
                                <span>Total Due</span>
                                <span className="font-serif text-xl text-[#805840]">{formatCurrency(getTotalPrice())}</span>
                            </div>
                        </div>
                    </div>

                    {/* Customer & Table Information */}
                    <div className="flex flex-col gap-5">
                        <div className="rounded-2xl border border-[#eadfd3] bg-[#fffefa] p-5 shadow-[0_8px_25px_rgba(73,51,41,0.05)] sm:p-6">
                            <h2 className="font-serif text-lg font-bold text-[#33231d]">Dine-In Details</h2>
                            <p className="mt-1 text-xs text-[#76675d]">
                                Please confirm your name and table number so our baristas can deliver your order fresh.
                            </p>

                            {errors.general && (
                                <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                                    {errors.general}
                                </div>
                            )}

                            <div className="mt-5 space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="customer_name" className="text-xs font-semibold text-[#33231d]">
                                        Customer Name *
                                    </Label>
                                    <Input
                                        id="customer_name"
                                        type="text"
                                        placeholder="e.g. Maria Santos"
                                        value={customerName}
                                        onChange={(e) => setCustomerName(e.target.value)}
                                        className={`rounded-xl border-[#dfd2c4] bg-[#fbf9f6] text-sm text-[#33231d] focus:border-[#805840] focus:ring-[#805840] ${
                                            errors.customer_name ? 'border-red-500' : ''
                                        }`}
                                    />
                                    {errors.customer_name && <p className="text-xs text-red-600">{errors.customer_name}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="table_number" className="text-xs font-semibold text-[#33231d]">
                                        Table Number *
                                    </Label>
                                    <Input
                                        id="table_number"
                                        type="number"
                                        min="1"
                                        placeholder="e.g. 5"
                                        value={tableNumber}
                                        onChange={(e) => setTableNumber(e.target.value)}
                                        className={`rounded-xl border-[#dfd2c4] bg-[#fbf9f6] text-sm text-[#33231d] focus:border-[#805840] focus:ring-[#805840] ${
                                            errors.table_number ? 'border-red-500' : ''
                                        }`}
                                    />
                                    {errors.table_number && <p className="text-xs text-red-600">{errors.table_number}</p>}
                                </div>
                            </div>

                            <div className="mt-6">
                                <Button
                                    className="w-full rounded-xl bg-[#805840] py-6 text-sm font-semibold text-white shadow-md hover:bg-[#694631] active:scale-98"
                                    onClick={handlePayment}
                                    disabled={isLoading}
                                >
                                    {isLoading ? 'Processing Order...' : `Pay ${formatCurrency(getTotalPrice())}`}
                                </Button>
                                <p className="mt-3 text-center text-[11px] text-[#8d7e73]">
                                    Secure payment powered by Midtrans · GCash, Cards & E-Wallets
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
