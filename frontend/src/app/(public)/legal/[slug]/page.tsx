'use client';

import { useEffect, useState, useMemo } from 'react';
import { useParams } from 'next/navigation';
import DOMPurify from 'dompurify';
import { legalApi } from '@/lib/api';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { AlertCircle, FileText } from 'lucide-react';

const SLUG_TITLES: Record<string, string> = {
    kvkk: 'KVKK Aydinlatma Metni',
    terms: 'Kullanim Kosullari',
    privacy: 'Gizlilik Politikasi',
    cookies: 'Cerez Politikasi',
    'mesafeli-satis-sozlesmesi': 'Mesafeli Satış Sözleşmesi',
    'iptal-iade': 'Iptal ve Iade Kosullari',
    'uyelik-sozlesmesi': 'Uyelik Sözleşmesi',
};

export default function LegalPage() {
    const params = useParams();
    const slug = params.slug as string;
    const [content, setContent] = useState<string | null>(null);
    const [title, setTitle] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    useEffect(() => {
        if (!slug) return;

        const loadContent = async () => {
            setLoading(true);
            setError(false);
            try {
                const res = await legalApi.getDocument(slug);
                if (res.data) {
                    setContent(res.data.content);
                    setTitle(res.data.title || SLUG_TITLES[slug] || slug);
                } else {
                    setError(true);
                }
            } catch {
                setError(true);
            } finally {
                setLoading(false);
            }
        };

        loadContent();
    }, [slug]);

    const sanitizedContent = useMemo(() => {
        if (!content) return null;
        return DOMPurify.sanitize(content, {
            ALLOWED_TAGS: [
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'p', 'br', 'hr',
                'ul', 'ol', 'li',
                'strong', 'em', 'b', 'i', 'u',
                'a', 'span', 'div',
                'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'blockquote', 'pre', 'code'
            ],
            ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'id'],
            ALLOW_DATA_ATTR: false,
        });
    }, [content]);

    const pageTitle = title || SLUG_TITLES[slug] || 'Yasal Belge';

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white">
                <div className="bg-gradient-to-br from-slate-700 via-slate-800 to-slate-900 py-16">
                    <div className="max-w-4xl mx-auto px-4 text-center">
                        <Skeleton className="h-10 w-80 mx-auto mb-3 bg-white/20" />
                        <Skeleton className="h-5 w-96 mx-auto bg-white/10" />
                    </div>
                </div>
                <div className="max-w-4xl mx-auto px-4 py-12 space-y-4">
                    <Skeleton className="h-6 w-48" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-3/4" />
                    <Skeleton className="h-6 w-56 mt-6" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-full" />
                </div>
            </div>
        );
    }

    if (error || !sanitizedContent) {
        return (
            <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white flex items-center justify-center">
                <div className="text-center">
                    <AlertCircle className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                    <h2 className="text-xl font-semibold text-slate-900 mb-2">Belge Bulunamadi</h2>
                    <p className="text-slate-500 mb-4">Aradiginiz yasal belge bulunamadi.</p>
                    <Button variant="outline" onClick={() => window.history.back()}>
                        Geri Don
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white">
            {/* Hero */}
            <section className="relative py-16 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-slate-700 via-slate-800 to-slate-900" />
                <div className="absolute inset-0 bg-[url('/grid.svg')] opacity-5" />
                <div className="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 rounded-full text-sm text-slate-300 mb-4">
                        <FileText className="w-4 h-4" />
                        Yasal
                    </div>
                    <h1 className="text-3xl md:text-4xl font-bold text-white">
                        {pageTitle}
                    </h1>
                </div>
            </section>

            {/* Content */}
            <section className="py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-8 md:p-12">
                        <div
                            className="prose prose-slate max-w-none
                                prose-headings:text-slate-900 prose-headings:font-bold
                                prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4 prose-h2:pb-2 prose-h2:border-b prose-h2:border-slate-100
                                prose-h3:text-lg prose-h3:mt-6 prose-h3:mb-3
                                prose-h4:text-base prose-h4:mt-4 prose-h4:mb-2
                                prose-p:text-slate-600 prose-p:leading-relaxed
                                prose-li:text-slate-600
                                prose-a:text-[#ea580c] prose-a:no-underline hover:prose-a:underline
                                prose-strong:text-slate-800
                                prose-ul:space-y-1"
                            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
                        />
                    </div>
                </div>
            </section>
        </div>
    );
}
