'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { BookOpen, Store, ShoppingCart, Home, ChevronRight } from 'lucide-react';
import { MarketHeader } from '@/components/market/MarketHeader';
import { MarketFooter } from '@/components/market/MarketFooter';
import { WhatsAppBubble } from '@/components/market/WhatsAppBubble';
import { medicalBgImage } from '@/components/ui/medical-background';
import { cmsApi, type CmsPageSummary } from '@/lib/api';
import {
    HELP_GROUP,
    helpDbSlugFromPath,
    helpUrlFromDbSlug,
    HELP_SECTIONS,
} from './help-routes';

type ApiResponse<T> = T | { data?: T };

function unwrap<T>(payload: unknown): T | null {
    if (!payload) return null;
    if (Array.isArray(payload)) return payload as unknown as T;
    if (typeof payload === 'object' && 'data' in (payload as Record<string, unknown>)) {
        return ((payload as { data?: T }).data ?? null);
    }
    return payload as T;
}

interface SidebarSection {
    title: string;
    icon: typeof BookOpen;
    items: Array<{ slug: string; title: string; href: string }>;
}

function buildSections(pages: CmsPageSummary[]): SidebarSection[] {
    const sections: SidebarSection[] = HELP_SECTIONS.map((s) => ({
        title: s.title,
        icon: s.icon === 'book' ? BookOpen : s.icon === 'store' ? Store : ShoppingCart,
        items: [],
    }));

    for (const page of pages) {
        if (page.slug === HELP_GROUP) continue; // skip ana hub

        const section = HELP_SECTIONS.findIndex((s) =>
            page.slug.startsWith(`${HELP_GROUP}-${s.prefix}`),
        );
        if (section === -1) continue;

        sections[section].items.push({
            slug: page.slug,
            title: page.title,
            href: helpUrlFromDbSlug(page.slug),
        });
    }

    // sort by sort_order via the original list (already returned ordered by API);
    // here, just stable per-section
    return sections.filter((s) => s.items.length > 0);
}

export default function YardimLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const [pages, setPages] = useState<CmsPageSummary[] | null>(null);
    const [pagesError, setPagesError] = useState(false);

    useEffect(() => {
        let cancelled = false;
        cmsApi
            .getPagesByGroup(HELP_GROUP)
            .then((res) => {
                if (cancelled) return;
                const list = unwrap<CmsPageSummary[]>(res.data as ApiResponse<CmsPageSummary[]>);
                if (Array.isArray(list)) {
                    setPages(list);
                } else {
                    setPagesError(true);
                }
            })
            .catch(() => {
                if (!cancelled) setPagesError(true);
            });
        return () => {
            cancelled = true;
        };
    }, []);

    const activeDbSlug = helpDbSlugFromPath(pathname ?? '/yardim');
    const sections = pages ? buildSections(pages) : [];
    const activeTitle = pages?.find((p) => p.slug === activeDbSlug)?.title ?? null;

    return (
        <div
            className="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 relative"
            style={{
                backgroundImage: medicalBgImage,
                backgroundRepeat: 'repeat',
                backgroundSize: '240px 240px',
            }}
        >
            <MarketHeader />

            <main className="relative">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="flex gap-8">
                        {/* Sidebar */}
                        <aside className="hidden lg:block w-72 flex-shrink-0">
                            <nav className="sticky top-24 space-y-6">
                                <Link
                                    href="/yardim"
                                    className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors ${
                                        pathname === '/yardim'
                                            ? 'bg-[#ffedd5] text-[#ea580c]'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                                >
                                    <Home className="w-4 h-4" />
                                    Yardım Ana Sayfa
                                </Link>

                                {pages === null && !pagesError && (
                                    <div className="px-4 space-y-3">
                                        {[0, 1, 2].map((i) => (
                                            <div key={i} className="space-y-2">
                                                <div className="h-3 w-24 bg-slate-200 rounded animate-pulse" />
                                                <div className="space-y-1.5">
                                                    <div className="h-3 w-44 bg-slate-100 rounded animate-pulse" />
                                                    <div className="h-3 w-40 bg-slate-100 rounded animate-pulse" />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {sections.map((section) => (
                                    <div key={section.title}>
                                        <h3 className="flex items-center gap-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                            <section.icon className="w-4 h-4" />
                                            {section.title}
                                        </h3>
                                        <ul className="space-y-1">
                                            {section.items.map((item) => {
                                                const active = item.slug === activeDbSlug;
                                                return (
                                                    <li key={item.slug}>
                                                        <Link
                                                            href={item.href}
                                                            className={`block px-4 py-2 rounded-lg text-sm transition-colors ${
                                                                active
                                                                    ? 'bg-[#ffedd5] text-[#ea580c] font-medium'
                                                                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                                            }`}
                                                        >
                                                            {item.title}
                                                        </Link>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>
                                ))}
                            </nav>
                        </aside>

                        {/* Main */}
                        <section className="flex-1 min-w-0">
                            {/* Breadcrumb */}
                            <nav className="flex items-center gap-2 text-sm text-gray-500 mb-6">
                                <Link href="/" className="hover:text-[#ea580c]">
                                    Ana Sayfa
                                </Link>
                                <ChevronRight className="w-4 h-4" />
                                <Link href="/yardim" className="hover:text-[#ea580c]">
                                    Yardım Merkezi
                                </Link>
                                {pathname !== '/yardim' && activeTitle && (
                                    <>
                                        <ChevronRight className="w-4 h-4" />
                                        <span className="text-gray-900 font-medium line-clamp-1">
                                            {activeTitle}
                                        </span>
                                    </>
                                )}
                            </nav>

                            {children}
                        </section>
                    </div>
                </div>
            </main>

            <MarketFooter />
            <WhatsAppBubble />
        </div>
    );
}
