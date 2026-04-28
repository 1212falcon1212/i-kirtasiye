'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { cmsApi, CmsPage } from '@/lib/api';
import { Skeleton } from '@/components/ui/skeleton';
import { ArrowRight, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function AboutPage() {
    const [page, setPage] = useState<CmsPage | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    useEffect(() => {
        const load = async () => {
            const res = await cmsApi.getPage('hakkimizda');
            // Backend returns { status, data: {...} }, api.get wraps whole body as res.data
            const body = res.data as unknown as { data?: CmsPage } | undefined;
            if (body?.data) {
                setPage(body.data);
            } else {
                setError(true);
            }
            setLoading(false);
        };
        load();
    }, []);

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white">
                <div className="bg-gradient-to-br from-slate-900 via-[#0a4f63] to-[#b8651a] py-20">
                    <div className="max-w-7xl mx-auto px-4 text-center">
                        <Skeleton className="h-12 w-96 mx-auto mb-4 bg-white/20" />
                        <Skeleton className="h-6 w-[500px] mx-auto bg-white/20" />
                    </div>
                </div>
                <div className="max-w-4xl mx-auto px-4 py-16 space-y-6">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-3/4" />
                    <Skeleton className="h-8 w-48 mt-8" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-full" />
                </div>
            </div>
        );
    }

    if (error || !page) {
        return (
            <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white flex items-center justify-center">
                <div className="text-center">
                    <AlertCircle className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                    <h2 className="text-xl font-semibold text-slate-900 mb-2">Sayfa yüklenemedi</h2>
                    <p className="text-slate-500 mb-4">Lütfen daha sonra tekrar deneyin.</p>
                    <Button variant="outline" onClick={() => window.location.reload()}>
                        Tekrar Dene
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white">
            {/* Hero Section */}
            <section className="relative py-20 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-slate-900 via-[#0a4f63] to-[#b8651a]" />
                <div className="absolute inset-0 bg-[url('/grid.svg')] opacity-10" />
                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 className="text-4xl md:text-5xl font-bold text-white mb-6">
                        {page.meta_title || page.title}
                    </h1>
                    {page.excerpt && (
                        <p className="text-xl text-white/75 max-w-3xl mx-auto leading-relaxed">
                            {page.excerpt}
                        </p>
                    )}
                </div>
            </section>

            {/* Content */}
            <section className="py-16">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div
                        className="prose prose-lg prose-slate max-w-none
                            prose-headings:text-slate-900 prose-headings:font-bold
                            prose-p:text-slate-600 prose-p:leading-relaxed
                            prose-a:text-[#b8651a] prose-a:no-underline hover:prose-a:underline
                            prose-strong:text-slate-900
                            prose-img:rounded-xl prose-img:shadow-lg"
                        dangerouslySetInnerHTML={{ __html: page.content }}
                    />
                </div>
            </section>

            {/* CTA */}
            <section className="py-16 bg-gradient-to-br from-slate-900 via-[#0a4f63] to-[#b8651a] relative overflow-hidden">
                <div className="absolute inset-0 bg-[url('/grid.svg')] opacity-10" />
                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl md:text-4xl font-extrabold text-white mb-4">
                        Hemen Başlayın
                    </h2>
                    <p className="text-white/75 mb-8 max-w-2xl mx-auto text-lg leading-relaxed">
                        i-kirtasiye ailesine katılarak avantajlı fiyatlarla ürün tedarik etmeye başlayın.
                    </p>
                    <Link
                        href="/register"
                        className="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#b8651a] font-bold rounded-xl hover:bg-slate-100 hover:shadow-xl transition-all"
                    >
                        Ücretsiz Kayıt Ol
                        <ArrowRight className="w-5 h-5" />
                    </Link>
                </div>
            </section>
        </div>
    );
}
