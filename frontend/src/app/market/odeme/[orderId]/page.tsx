'use client';

import { useEffect, useState, useCallback } from 'react';
import { useParams } from 'next/navigation';
import { paymentsApi, PaymentInitResponse } from '@/lib/api';
import { CardForm } from '@/components/payment/CardForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { CreditCard, AlertCircle, ArrowLeft, Shield, RefreshCw, X } from 'lucide-react';
import Link from 'next/link';

type PageState = 'loading' | 'error' | 'form' | '3d_redirect';

export default function PaymentPage() {
    const params = useParams();
    const orderId = Number(params.orderId);

    const [pageState, setPageState] = useState<PageState>('loading');
    const [error, setError] = useState<string | null>(null);
    const [paymentData, setPaymentData] = useState<PaymentInitResponse | null>(null);
    const [threeDHtml, setThreeDHtml] = useState<string | null>(null);

    const initializePayment = useCallback(async () => {
        if (!orderId || isNaN(orderId)) {
            setError('Geçersiz sipariş.');
            setPageState('error');
            return;
        }

        setPageState('loading');
        setError(null);

        try {
            const response = await paymentsApi.initialize(orderId);

            if (response.data?.success) {
                setPaymentData(response.data);
                setPageState('form');
            } else {
                setError(response.data?.error || response.error || 'Ödeme başlatılamadı.');
                setPageState('error');
            }
        } catch {
            setError('Ödeme sistemi ile bağlantı kurulamadı.');
            setPageState('error');
        }
    }, [orderId]);

    useEffect(() => {
        initializePayment();
    }, [initializePayment]);

    // Execute scripts in 3D HTML after render
    useEffect(() => {
        if (pageState === '3d_redirect' && threeDHtml) {
            const container = document.getElementById('three-d-container');
            if (container) {
                // Find and execute script tags
                const scripts = container.querySelectorAll('script');
                scripts.forEach((oldScript) => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach((attr) => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode?.replaceChild(newScript, oldScript);
                });
            }
        }
    }, [pageState, threeDHtml]);

    const handlePaymentSuccess = (html: string) => {
        setThreeDHtml(html);
        setPageState('3d_redirect');
    };

    const handlePaymentError = (message: string) => {
        setError(message);
        setPageState('error');
    };

    const handleCancelThreeD = () => {
        setThreeDHtml(null);
        setPageState('form');
    };

    // Loading state
    if (pageState === 'loading') {
        return (
            <div className="max-w-2xl mx-auto px-4 py-8">
                <div className="space-y-4">
                    <Skeleton className="h-8 w-64" />
                    <Card>
                        <CardHeader>
                            <Skeleton className="h-6 w-48" />
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Skeleton className="h-4 w-full" />
                            <Skeleton className="h-12 w-full" />
                            <Skeleton className="h-12 w-full" />
                            <div className="grid grid-cols-2 gap-4">
                                <Skeleton className="h-12 w-full" />
                                <Skeleton className="h-12 w-full" />
                            </div>
                            <Skeleton className="h-12 w-full" />
                        </CardContent>
                    </Card>
                </div>
            </div>
        );
    }

    // 3D Secure redirect state
    if (pageState === '3d_redirect' && threeDHtml) {
        return (
            <div className="fixed inset-0 z-50 bg-white dark:bg-slate-950">
                <div className="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
                    <div className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <Shield className="h-4 w-4 text-[#ea580c]" />
                        <span>3D Secure Doğrulama</span>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleCancelThreeD}
                        className="gap-1 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                    >
                        <X className="h-4 w-4" />
                        Geri Dön
                 </Button>
                </div>
                <div
                    id="three-d-container"
                    className="w-full h-[calc(100vh-52px)] overflow-auto"
                    dangerouslySetInnerHTML={{ __html: threeDHtml }}
                />
            </div>
        );
    }

    // Error state
    if (pageState === 'error') {
        return (
            <div className="max-w-2xl mx-auto px-4 py-8">
                <Button variant="ghost" asChild className="gap-2 text-slate-600 dark:text-slate-400 hover:text-[#ea580c] -ml-4 mb-6">
                    <Link href="/market">
                        <ArrowLeft className="h-4 w-4" />
                        Markete Dön
                    </Link>
                </Button>

                <Card className="border-red-200 dark:border-red-800">
                    <CardContent className="pt-6">
                        <div className="text-center py-8">
                            <div className="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                <AlertCircle className="h-8 w-8 text-red-600 dark:text-red-400" />
                            </div>
                            <h2 className="text-xl font-bold text-slate-900 dark:text-white mb-2">Ödeme Başlatılamadı</h2>
                            <p className="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">{error}</p>
                            <div className="flex items-center justify-center gap-3">
                                <Button onClick={initializePayment} className="gap-2 bg-[#1e3a8a] hover:bg-[#1e40af]">
                                    <RefreshCw className="h-4 w-4" />
                                    Tekrar Dene
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/market/hesabim?tab=siparislerim&sub=satin-aldiklarim">
                                        Siparişlerime Dön
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        );
    }

    // Form state (main content)
    return (
        <div className="max-w-2xl mx-auto px-4 py-8">
            <Button variant="ghost" asChild className="gap-2 text-slate-600 dark:text-slate-400 hover:text-[#ea580c] -ml-4 mb-6">
                <Link href="/market/hesabim?tab=siparislerim&sub=satin-aldiklarim">
                    <ArrowLeft className="h-4 w-4" />
                    Siparişlerime Dön
                </Link>
            </Button>

            {/* Order summary card */}
            {paymentData?.order && (
                <div className="mb-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-[#1e3a8a]/10 rounded-lg flex items-center justify-center">
                                <CreditCard className="h-5 w-5 text-[#ea580c]" />
                            </div>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Sipariş</p>
                                <p className="font-medium text-slate-900 dark:text-white">#{paymentData.order.order_number}</p>
                            </div>
                        </div>
                        <div className="text-right">
                            <p className="text-sm text-slate-500 dark:text-slate-400">Toplam</p>
                            <p className="text-lg font-bold text-[#ea580c]">
                                {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(paymentData.order.total_amount)}
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Card Form */}
            <CardForm
                orderId={orderId}
                totalAmount={paymentData?.order?.total_amount ?? 0}
                onSuccess={handlePaymentSuccess}
                onError={handlePaymentError}
            />

            {/* Security badge */}
            <div className="mt-4 flex items-center justify-center gap-2 text-sm text-slate-400 dark:text-slate-500">
                <Shield className="h-4 w-4" />
                <span>256-bit SSL ile güvenli ödeme</span>
            </div>
        </div>
    );
}
