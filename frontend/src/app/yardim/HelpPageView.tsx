'use client';

import { useEffect, useMemo, useState } from 'react';
import Link from 'next/link';
import DOMPurify from 'dompurify';
import { AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { cmsApi, type CmsPage } from '@/lib/api';

type ApiResponse<T> = T | { data?: T };

function unwrap<T>(payload: unknown): T | null {
    if (!payload) return null;
    if (typeof payload === 'object' && payload !== null && 'data' in (payload as Record<string, unknown>)) {
        return ((payload as { data?: T }).data ?? null);
    }
    return payload as T;
}

interface HelpPageViewProps {
    slug: string;
    /** Eger slug bulunamazsa ana yardim sayfasina don. */
    fallbackHref?: string;
}

export function HelpPageView({ slug, fallbackHref = '/yardim' }: HelpPageViewProps) {
    const [page, setPage] = useState<CmsPage | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        setError(false);
        setPage(null);

        cmsApi
            .getPage(slug)
            .then((res) => {
                if (cancelled) return;
                if (res.status === 404 || !res.data) {
                    setError(true);
                    return;
                }
                const body = unwrap<CmsPage>(res.data as ApiResponse<CmsPage>);
                if (body) {
                    setPage(body);
                } else {
                    setError(true);
                }
            })
            .catch(() => {
                if (!cancelled) setError(true);
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [slug]);

    const sanitized = useMemo(() => {
        if (!page?.content) return null;
        return DOMPurify.sanitize(page.content, {
            ALLOWED_TAGS: [
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'p', 'br', 'hr',
                'ul', 'ol', 'li',
                'strong', 'em', 'b', 'i', 'u',
                'a', 'span', 'div',
                'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'blockquote', 'pre', 'code',
            ],
            ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'id'],
            ALLOW_DATA_ATTR: false,
        });
    }, [page?.content]);

    if (loading) {
        return (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 space-y-4">
                <Skeleton className="h-8 w-2/3" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-5/6" />
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-6 w-1/2 mt-6" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-5/6" />
            </div>
        );
    }

    if (error || !page || !sanitized) {
        return (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <AlertCircle className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                <h2 className="text-xl font-semibold text-slate-900 mb-2">Sayfa bulunamadı</h2>
                <p className="text-slate-500 mb-4">
                    Aradığınız yardım sayfası mevcut değil veya kaldırılmış olabilir.
                </p>
                <Link href={fallbackHref}>
                    <Button variant="outline">Yardım Merkezine Dön</Button>
                </Link>
            </div>
        );
    }

    return (
        <article className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sm:p-8">
            <header className="mb-6 pb-4 border-b border-gray-200">
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">{page.title}</h1>
                {page.excerpt && (
                    <p className="text-gray-500 mt-2 text-sm sm:text-base leading-relaxed">
                        {page.excerpt}
                    </p>
                )}
            </header>

            <div
                className="prose prose-slate max-w-none
                    prose-headings:text-gray-900 prose-headings:font-bold
                    prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4
                    prose-h3:text-xl prose-h3:mt-6 prose-h3:mb-3
                    prose-h4:text-base prose-h4:mt-4 prose-h4:mb-2
                    prose-p:text-gray-600 prose-p:leading-relaxed
                    prose-li:text-gray-600
                    prose-a:text-[#ea580c] prose-a:no-underline hover:prose-a:underline
                    prose-strong:text-gray-800
                    prose-ul:space-y-1
                    prose-ol:space-y-1"
                dangerouslySetInnerHTML={{ __html: sanitized }}
            />
        </article>
    );
}
