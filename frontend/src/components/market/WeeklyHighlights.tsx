import Link from 'next/link';
import Image from 'next/image';
import { Flame, Zap } from 'lucide-react';

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        n,
    ) + ' ₺';

interface MiniProduct {
    id: number | string;
    name: string;
    brand?: string;
    image?: string;
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
                                background: '#ffffff',
                                border: '1px solid var(--border)',
                                borderRadius: 'var(--radius-lg)',
                            }}
                        >
                            <div
                                className="px-5 py-3.5 flex justify-between items-center"
                                style={{
                                    background: sec.tone === 'warning' ? 'linear-gradient(90deg, #fbf1e2 0%, #ffffff 100%)' : 'linear-gradient(90deg, #ffedd5 0%, #ffffff 100%)',
                                    borderBottom: '1px solid var(--border)',
                                }}
                            >
                                <div className="flex items-center gap-2.5">
                                    <div
                                        className="w-7 h-7 rounded-md flex items-center justify-center shadow-sm"
                                        style={{
                                            background: sec.tone === 'warning' ? 'linear-gradient(135deg, var(--warning) 0%, #d97706 100%)' : 'linear-gradient(135deg, var(--accent) 0%, #c2410c 100%)',
                                            color: 'white',
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
                                    className="text-xs font-semibold px-3 py-1.5 rounded-md transition-all hover:shadow-sm"
                                    style={{ background: toneBg, color: toneFg }}
                                >
                                    Tümü →
                                </Link>
                            </div>
                                <div className="p-3 flex overflow-x-auto gap-2.5 snap-x snap-mandatory pb-3 -mx-3 px-3 md:grid md:grid-cols-3 md:gap-2.5 md:snap-none md:pb-0 md:-mx-0 md:px-0">
                                {sec.items.map((p) => (
                                    <Link
                                        key={p.id}
                                        href={p.href || `/market/product/${p.id}`}
                                        className="flex flex-col gap-1.5 p-2 rounded-md transition-all hover:shadow-md snap-start flex-shrink-0 w-[120px] md:w-auto"
                                        style={{
                                            background: sec.tone === 'warning' ? 'var(--warning-soft)' : 'var(--accent-soft)',
                                        }}
                                    >
                                        <div
                                            className="relative overflow-hidden"
                                            style={{
                                                aspectRatio: '1 / 1',
                                                background: '#ffffff',
                                                border: '1px solid var(--border)',
                                                borderRadius: 6,
                                            }}
                                        >
                                            {p.image ? (
                                                <Image
                                                    src={p.image}
                                                    alt={p.name}
                                                    fill
                                                    sizes="(max-width:1024px) 30vw, 200px"
                                                    className="object-contain p-2"
                                                />
                                            ) : (
                                                <div
                                                    className="ph-image w-full h-full flex items-center justify-center"
                                                    style={{ fontSize: 9 }}
                                                >
                                                    {p.brand}
                                                </div>
                                            )}
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
