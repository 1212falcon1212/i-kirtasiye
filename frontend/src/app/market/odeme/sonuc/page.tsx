'use client';

import { useSearchParams } from 'next/navigation';
import { Suspense, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2, XCircle, ArrowRight, RotateCw, ShoppingBag } from 'lucide-react';
import Link from 'next/link';

function PaymentResultContent() {
    const searchParams = useSearchParams();
    const status = searchParams.get('status');
    const orderNumber = searchParams.get('order');

    // If we're inside an iframe (3D Secure), break out to top window
    useEffect(() => {
        if (window.self !== window.top) {
            try {
                window.top!.location.href = window.location.href;
            } catch {
                // cross-origin fallback
                window.location.replace(window.location.href);
            }
        }
    }, []);

    const isSuccess = status === 'success';
    const isFailed = status === 'failed';

    if (isSuccess) {
        return (
            <div className="max-w-lg mx-auto px-4 py-12">
                <Card className="border-[#b8651a]/20">
                    <CardContent className="pt-8 pb-8">
                        <div className="text-center">
                            <div className="w-20 h-20 bg-[#b8651a]/10 rounded-full flex items-center justify-center mx-auto mb-6">
                                <CheckCircle2 className="h-10 w-10 text-[#b8651a]" />
                            </div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                                Ödemeniz Başarıyla Alındı
                            </h1>
                            <p className="text-slate-500 dark:text-slate-400 mb-2">
                                Siparişiniz onaylandı ve hazırlanıyor.
                            </p>
                            {orderNumber && (
                                <p className="text-sm text-slate-400 dark:text-slate-500 mb-8">
                                    Sipariş No: <span className="font-mono font-medium text-slate-600 dark:text-slate-300">{orderNumber}</span>
                                </p>
                            )}

                            <div className="space-y-3">
                                <Button asChild className="w-full h-12 bg-[#b8651a] hover:bg-[#934f12] gap-2">
                                    <Link href="/market/hesabim?tab=siparislerim&sub=satin-aldiklarim">
                                        <ShoppingBag className="h-4 w-4" />
                                        Siparişlerimi Gör
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild className="w-full h-12 gap-2">
                                    <Link href="/market">
                                        Alışverişe Devam Et
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        );
    }

    if (isFailed) {
        return (
            <div className="max-w-lg mx-auto px-4 py-12">
                <Card className="border-red-200 dark:border-red-800">
                    <CardContent className="pt-8 pb-8">
                        <div className="text-center">
                            <div className="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                                <XCircle className="h-10 w-10 text-red-600 dark:text-red-400" />
                            </div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                                Ödeme Başarısız
                            </h1>
                            <p className="text-slate-500 dark:text-slate-400 mb-2">
                                Ödemeniz işlenemedi. Lütfen tekrar deneyin veya farklı bir kart kullanın.
                            </p>
                            {orderNumber && (
                                <p className="text-sm text-slate-400 dark:text-slate-500 mb-8">
                                    Sipariş No: <span className="font-mono font-medium text-slate-600 dark:text-slate-300">{orderNumber}</span>
                                </p>
                            )}

                            <div className="space-y-3">
                                <Button
                                    onClick={() => window.history.back()}
                                    className="w-full h-12 bg-red-600 hover:bg-red-700 gap-2"
                                >
                                    <RotateCw className="h-4 w-4" />
                                    Tekrar Dene
                                </Button>
                                <Button variant="outline" asChild className="w-full h-12 gap-2">
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

    // Unknown / loading state
    return (
        <div className="max-w-lg mx-auto px-4 py-12">
            <Card>
                <CardContent className="pt-8 pb-8">
                    <div className="text-center">
                        <div className="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6">
                            <div className="h-8 w-8 animate-spin rounded-full border-4 border-[#b8651a] border-t-transparent" />
                        </div>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white mb-2">
                            Ödeme Durumu Kontrol Ediliyor
                        </h1>
                        <p className="text-slate-500 dark:text-slate-400 mb-6">
                            Lütfen bekleyin...
                        </p>
                        <Button variant="outline" asChild>
                            <Link href="/market/hesabim?tab=siparislerim&sub=satin-aldiklarim">
                                Siparişlerime Dön
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

export default function PaymentResultPage() {
    return (
        <Suspense fallback={
            <div className="max-w-lg mx-auto px-4 py-12">
                <Card>
                    <CardContent className="pt-8 pb-8">
                        <div className="text-center">
                            <div className="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6">
                                <div className="h-8 w-8 animate-spin rounded-full border-4 border-[#b8651a] border-t-transparent" />
                            </div>
                            <p className="text-slate-500 dark:text-slate-400">Yükleniyor...</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        }>
            <PaymentResultContent />
        </Suspense>
    );
}
