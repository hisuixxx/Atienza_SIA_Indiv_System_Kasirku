import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';
import { useEffect } from 'react';

interface Props {
    order_id: string;
    status: string;
}

export default function PaymentFinish({ order_id, status }: Props) {
    useEffect(() => {
        // Clear cart after successful payment
        localStorage.removeItem('kasirku_cart');
    }, []);

    const goToOrderStatus = () => {
        router.visit(`/order/${order_id}/status`);
    };

    const goHome = () => {
        router.visit('/');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-4">
            <Head title="Payment Successful - Kasirku" />

            <Card className="w-full max-w-md text-center">
                <CardHeader>
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <CheckCircle className="h-8 w-8 text-green-600" />
                    </div>
                    <CardTitle className="text-2xl font-bold text-green-600">Payment Successful!</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-muted-foreground">Thank you! Your payment was processed successfully. Your order is being prepared.</p>

                    <div className="space-y-2">
                        <Button className="w-full" onClick={goToOrderStatus}>
                            View Order Status
                        </Button>
                        <Button variant="outline" className="w-full" onClick={goHome}>
                            Back to Home
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
