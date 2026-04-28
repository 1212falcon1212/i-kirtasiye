import Link from 'next/link';
import { Flame, Zap } from 'lucide-react';

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        n,
    ) + ' ₺';

interface MiniProduct {
    id: number | string;
    name: string;
    brand?: string;
    lowest?: number;
    href?: string;
}

interface WeeklyHighlightsProps {
    season?: MiniProduct[];
    weekly?: MiniProduct[];
}

const FALLBACK: MiniProduct[] = [
    { id: 1, name: 'Faber-Castell 9000 Kurşun Kalem 12\'li', brand: 'FABER-CASTELL', lowest: 184.5 },
    { id: 2, name: 'BIC Cristal Tükenmez 50\'li', brand: 'BIC', lowest: 121.0 },
    { id: 3, name: 'CANSON 200gr Resim Defteri 25\'li', brand: 'CANSON', lowest: 98.4 },
];

export function WeeklyHighlights({ season = FALLBACK, weekly = FALLBACK }: WeeklyHighlightsProps) {
    const sections = [
        {
            Icon: Flame,
            title: 'Sezonun Öne Çıkanları',
            subtitle: 'Bahar dönemi en çok satan toptan ürünler',
            tone: 'warning' as const,
            items: season.slice(0, 3),
        },
        {
            Icon: Zap,
            title: 'Haftanın Ürünleri',
            subtitle: 'Bu hafta editör seçimi ilanlar',
            tone: 'accent' as const,
            items: weekly.slice(0, 3),
        },
    ];

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {sections.map((sec) => {
                    const toneBg =
                        sec.tone === 'warning' ? 'var(--warning-soft)' : 'var(--accent-soft)';
                    const toneFg =
                        sec.tone === 'warning' ? 'var(--warning)' : 'var(--accent)';
                    return (
                        <div
                            key={sec.title}
                            className="overflow-hidden"
                            style={{
                                background: 'var(--bg-elevated)',
                                border: '1px solid var(--border)',
                                borderRadius: 'var(--radius-lg)',
                            }}
                        >
                            <div
                                className="px-5 py-3.5 flex justify-between items-center"
                                style={{
                                    background: toneBg,
                                    borderBottom: `1px solid ${toneBg}`,
                                }}
                            >
                                <div className="flex items-center gap-2.5">
                                    <div
                                        className="w-7 h-7 rounded-md flex items-center justify-center"
                                        style={{
                                            background: 'var(--bg-elevated)',
                                            color: toneFg,
                                        }}
                                    >
                                        <sec.Icon className="w-[15px] h-[15px]" />
                                    </div>
                                    <div>
                                        <div className="text-[15px] font-bold" style={{ color: toneFg }}>
                                            {sec.title}
                                        </div>
                                        <div
                                            className="text-[11px]"
                                            style={{ color: 'var(--fg-muted)' }}
                                        >
                                            {sec.subtitle}
                                        </div>
                                    </div>
                                </div>
                                <Link
                                    href="/market/products"
                                    className="text-xs font-semibold"
                                    style={{ color: toneFg }}
                                >
                                    Tümü →
                                </Link>
                            </div>
                            <div className="p-3 grid grid-cols-3 gap-2.5">
                                {sec.items.map((p) => (
                                    <Link
                                        key={p.id}
                                        href={p.href || `/market/product/${p.id}`}
                                        className="flex flex-col gap-1.5 p-2 rounded-md transition-colors hover:bg-bg-muted"
                                    >
                                        <div className="ph-image" style={{ aspectRatio: '1 / 1', fontSize: 9 }}>
                                            {p.brand}
                                        </div>
                                        <div
                                            className="text-xs font-medium leading-tight line-clamp-2"
                                            style={{ color: 'var(--fg)' }}
                                        >
                                            {p.name}
                                        </div>
                                        <div
                                            className="mono text-sm font-bold"
                                            style={{ color: toneFg }}
                                        >
                                            {p.lowest != null ? formatTL(p.lowest) : '---'}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

export default WeeklyHighlights;
