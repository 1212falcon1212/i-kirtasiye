import Link from 'next/link';
import { Flame, ArrowRight } from 'lucide-react';

interface DailyDealCardProps {
    title?: string;
    brandLabel?: string;
    psf?: number;
    lowest?: number;
    sellersCount?: number;
    stockRemaining?: number;
    stockTotal?: number;
    href?: string;
}

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        n,
    ) + ' ₺';

export function DailyDealCard({
    title = "Faber-Castell 9000 Kurşun Kalem 12'li Set",
    brandLabel = 'FABER-CASTELL',
    psf = 248,
    lowest = 184.5,
    sellersCount = 14,
    stockRemaining = 184,
    stockTotal = 600,
    href = '/market/products',
}: DailyDealCardProps) {
    const savings = psf > lowest ? Math.round(((psf - lowest) / psf) * 100) : 0;
    const pct = Math.min(100, Math.max(0, (stockRemaining / stockTotal) * 100));

    return (
        <div
            className="relative grid items-center gap-5 p-5 overflow-hidden"
            style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border)',
                borderRadius: 'var(--radius-lg)',
                gridTemplateColumns: '200px 1fr auto',
            }}
        >
            <div
                className="ph-image"
                style={{ aspectRatio: '1 / 1', width: 200, borderRadius: 10 }}
            >
                {brandLabel}
            </div>

            <div>
                <span className="chip chip-warning" style={{ fontWeight: 700 }}>
                    <Flame className="w-3 h-3" /> Günün Fırsatı
                </span>
                <h3 className="text-xl mt-2 leading-tight" style={{ color: 'var(--fg)' }}>
                    {title}
                </h3>
                <div className="text-[13px] mt-1.5" style={{ color: 'var(--fg-muted)' }}>
                    <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                        {sellersCount}
                    </span>{' '}
                    satıcı ilanı ·{' '}
                    <span style={{ color: 'var(--success)', fontWeight: 600 }}>Stokta</span>
                </div>
                <div className="flex items-baseline gap-3 mt-3">
                    <span
                        className="mono text-[13px] line-through"
                        style={{ color: 'var(--fg-soft)' }}
                    >
                        {formatTL(psf)}
                    </span>
                    <span
                        className="mono text-[28px] font-bold"
                        style={{ color: 'var(--accent)' }}
                    >
                        {formatTL(lowest)}
                    </span>
                    {savings > 0 && <span className="chip chip-success">%{savings} indirim</span>}
                </div>
                <div className="mt-2.5 text-[11px]" style={{ color: 'var(--fg-muted)' }}>
                    <div className="flex justify-between mb-1">
                        <span>Bu fırsat için kalan stok</span>
                        <span className="mono font-semibold">
                            {stockRemaining} / {stockTotal}
                        </span>
                    </div>
                    <div
                        className="h-1.5 rounded-full overflow-hidden"
                        style={{ background: 'var(--bg-muted)' }}
                    >
                        <div
                            style={{
                                width: `${pct}%`,
                                height: '100%',
                                background:
                                    'linear-gradient(90deg, var(--warning), var(--accent))',
                            }}
                        />
                    </div>
                </div>
            </div>

            <Link
                href={href}
                className="btn btn-primary btn-lg self-stretch"
                style={{ paddingLeft: 24, paddingRight: 24 }}
            >
                Fırsatı Yakala <ArrowRight className="w-3.5 h-3.5" />
            </Link>
        </div>
    );
}

export default DailyDealCard;
