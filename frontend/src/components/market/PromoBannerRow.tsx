import Link from 'next/link';
import { ArrowRight } from 'lucide-react';

const BANNERS = [
    {
        eyebrow: 'Yeni Tedarikçi',
        title: 'Bu hafta 38 toptancı katıldı',
        cta: 'Keşfet',
        href: '/market/markalar',
        bg: 'linear-gradient(135deg, oklch(0.92 0.04 250), oklch(0.86 0.06 240))',
        color: '#1a2456',
    },
    {
        eyebrow: '24 Saatte Teslim',
        title: 'Hızlı kargo ilanları',
        cta: 'İlanları gör',
        href: '/market/products?fast=true',
        bg: 'linear-gradient(135deg, oklch(0.93 0.05 150), oklch(0.86 0.08 145))',
        color: '#1a4a2e',
    },
    {
        eyebrow: 'Toplu Sipariş',
        title: 'Excel ile binlerce SKU',
        cta: 'Yüklemeye başla',
        href: '/market/hesabim?tab=bulk',
        bg: 'linear-gradient(135deg, oklch(0.93 0.05 60), oklch(0.86 0.09 55))',
        color: '#5a3812',
    },
];

export function PromoBannerRow() {
    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                {BANNERS.map((b) => (
                    <Link
                        key={b.title}
                        href={b.href}
                        className="relative overflow-hidden flex flex-col justify-between p-5"
                        style={{
                            background: b.bg,
                            borderRadius: 'var(--radius-lg)',
                            minHeight: 130,
                            color: b.color,
                        }}
                    >
                        <span
                            aria-hidden
                            className="absolute"
                            style={{
                                right: -20,
                                top: -20,
                                width: 100,
                                height: 100,
                                borderRadius: 999,
                                background: 'rgba(255,255,255,0.3)',
                            }}
                        />
                        <div className="relative">
                            <div
                                className="eyebrow"
                                style={{ color: b.color, opacity: 0.7 }}
                            >
                                {b.eyebrow}
                            </div>
                            <div
                                className="text-lg font-bold mt-1.5 leading-tight max-w-[220px]"
                                style={{ color: b.color }}
                            >
                                {b.title}
                            </div>
                        </div>
                        <div className="relative inline-flex items-center gap-1.5 text-[13px] font-semibold mt-4">
                            {b.cta} <ArrowRight className="w-3.5 h-3.5" />
                        </div>
                    </Link>
                ))}
            </div>
        </section>
    );
}

export default PromoBannerRow;
