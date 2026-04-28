import Link from 'next/link';
import { Logo } from '@/components/Logo';

const COLUMNS: { h: string; links: { label: string; href: string }[] }[] = [
    {
        h: 'Pazaryeri',
        links: [
            { label: 'Tüm kategoriler', href: '/market/products' },
            { label: 'Markalar', href: '/market/markalar' },
            { label: 'Kampanyalar', href: '/market/kampanyalar' },
            { label: 'Yeni ilanlar', href: '/market/yeni-urunler' },
        ],
    },
    {
        h: 'Satıcılar',
        links: [
            { label: 'Satıcı paneli', href: '/seller' },
            { label: 'Satıcı olmak', href: '/register' },
            { label: 'Komisyon oranları', href: '/yardim/satici-rehberi/hakedis' },
            { label: 'Satıcı eğitimi', href: '/yardim/satici-rehberi/urun-ekleme' },
        ],
    },
    {
        h: 'Bayiler',
        links: [
            { label: 'Vadeli alışveriş', href: '/yardim/alici-rehberi/sepet-odeme' },
            { label: 'Toplu sipariş', href: '/yardim/alici-rehberi/sepet-odeme' },
            { label: 'Kurumsal hesap', href: '/iletisim' },
            { label: 'Faturalar', href: '/market/hesabim?tab=invoices' },
        ],
    },
    {
        h: 'Destek',
        links: [
            { label: 'Yardım merkezi', href: '/yardim' },
            { label: 'İletişim', href: '/iletisim' },
            { label: 'KVKK', href: '/legal/kvkk' },
            { label: 'Sözleşmeler', href: '/legal' },
        ],
    },
];

export function MarketFooter() {
    return (
        <footer
            style={{
                background: 'var(--bg-muted)',
                borderTop: '1px solid var(--border)',
                marginTop: 48,
            }}
        >
            <div className="max-w-[1440px] mx-auto px-6 pt-10 pb-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr_1fr] gap-8">
                    <div>
                        <Logo size="md" />
                        <p className="mt-3 text-sm max-w-[280px]" style={{ color: 'var(--fg-muted)' }}>
                            Türkiye&apos;nin B2B kırtasiye ve ofis malzemeleri toptan pazaryeri. Bayiler ve toptancılar için.
                        </p>
                    </div>
                    {COLUMNS.map((col) => (
                        <div key={col.h}>
                            <div className="eyebrow mb-3">{col.h}</div>
                            <ul className="grid gap-2">
                                {col.links.map((l) => (
                                    <li key={l.href}>
                                        <Link
                                            href={l.href}
                                            className="text-[13px] hover:underline"
                                            style={{ color: 'var(--fg-muted)' }}
                                        >
                                            {l.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div
                    className="mt-8 pt-5 flex flex-col sm:flex-row gap-2 justify-between text-xs"
                    style={{ borderTop: '1px solid var(--border)', color: 'var(--fg-soft)' }}
                >
                    <span>© {new Date().getFullYear()} i-kirtasiye B2B</span>
                    <span className="mono">Tüm fiyatlar KDV dahil</span>
                </div>
            </div>
        </footer>
    );
}

export default MarketFooter;
