import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';

export default function PaymentUnfinish() {
    const goHome = () => {
        router.visit('/');
    };

    const tryAgain = () => {
        router.visit('/checkout');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-4">
            <Head title="Payment Incomplete - Kasirku" />

            <Card className="w-full max-w-md text-center">
                <CardHeader>
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100">
                        <AlertCircle className="h-8 w-8 text-yellow-600" />
                    </div>
                    <CardTitle className="text-2xl font-bold text-yellow-600">Payment Incomplete</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-muted-foreground">
                        Your payment was not completed. Please try again or contact the cashier if you have any issues.
                    </p>

                    <div className="space-y-2">
                        <Button className="w-full" onClick={tryAgain}>
                            Try Again
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
