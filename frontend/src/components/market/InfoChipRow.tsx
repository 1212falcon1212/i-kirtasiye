import { Zap, Truck, ShieldCheck, Box } from 'lucide-react';

const ITEMS = [
    {
        Icon: Zap,
        title: 'Komisyonsuz Satış',
        desc: 'Sadece sabit aylık üyelik bedeli',
        tone: 'accent' as const,
    },
    {
        Icon: Truck,
        title: 'Ücretsiz Kargo',
        desc: '1.500₺ üzeri tüm siparişlerde',
        tone: 'success' as const,
    },
    {
        Icon: ShieldCheck,
        title: 'Güvenli Tedarik',
        desc: 'Onaylı bayi ve tedarikçilerden',
        tone: 'warning' as const,
    },
    {
        Icon: Box,
        title: 'Vadeli Alışveriş',
        desc: '30-60-90 gün vadeli ödeme',
        tone: 'accent' as const,
    },
];

const tone = (t: 'accent' | 'success' | 'warning') => {
    if (t === 'success') return { bg: 'var(--success-soft)', fg: 'var(--success)' };
    if (t === 'warning') return { bg: 'var(--warning-soft)', fg: 'var(--warning)' };
    return { bg: 'var(--accent-soft)', fg: 'var(--accent)' };
};

export function InfoChipRow() {
    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-4">
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                {ITEMS.map(({ Icon, title, desc, tone: t }) => {
                    const c = tone(t);
                    return (
                        <div key={title} className="card flex items-center gap-3 px-4 py-3.5">
                            <div
                                className="w-10 h-10 rounded-[10px] flex items-center justify-center flex-shrink-0"
                                style={{ background: c.bg, color: c.fg }}
                            >
                                <Icon className="w-[18px] h-[18px]" />
                            </div>
                            <div className="min-w-0">
                                <div className="text-sm font-semibold" style={{ color: 'var(--fg)' }}>
                                    {title}
                                </div>
                                <div
                                    className="text-xs leading-tight mt-0.5"
                                    style={{ color: 'var(--fg-muted)' }}
                                >
                                    {desc}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

export default InfoChipRow;
