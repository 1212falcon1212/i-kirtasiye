'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';

interface Brand {
    id: number;
    slug: string;
    name: string;
    logo?: string | null;
}

const FALLBACK: Brand[] = [
    { id: 1, slug: 'faber-castell', name: 'Faber-Castell' },
    { id: 2, slug: 'pelikan', name: 'Pelikan' },
    { id: 3, slug: 'bic', name: 'BIC' },
    { id: 4, slug: 'staedtler', name: 'Staedtler' },
    { id: 5, slug: 'canson', name: 'Canson' },
    { id: 6, slug: 'mas', name: 'MAS' },
    { id: 7, slug: 'pilot', name: 'Pilot' },
    { id: 8, slug: 'stabilo', name: 'Stabilo' },
];

export function BrandStrip() {
    const [brands, setBrands] = useState<Brand[]>(FALLBACK);

    useEffect(() => {
        let active = true;
        api.get<{ brands: Brand[] }>('/brands')
            .then((res) => {
                if (!active) return;
                if (res.data?.brands && res.data.brands.length > 0) {
                    setBrands(res.data.brands.slice(0, 8));
                }
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, []);

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-10">
            <div className="flex justify-between items-baseline mb-4">
                <h2 className="text-xl font-semibold" style={{ color: 'var(--fg)' }}>
                    Markalar
                </h2>
                <Link
                    href="/market/markalar"
                    className="text-sm font-medium"
                    style={{ color: 'var(--accent)' }}
                >
                    Tüm markalar →
                </Link>
            </div>
            <div
                className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 p-2"
                style={{
                    background: 'var(--bg-elevated)',
                    border: '1px solid var(--border)',
                    borderRadius: 'var(--radius-lg)',
                }}
            >
                {brands.map((b) => (
                    <Link
                        key={b.id}
                        href={`/market/marka/${b.slug}`}
                        className="px-3 py-5 rounded-md flex items-center justify-center text-[13px] font-semibold transition-colors hover:bg-bg-muted"
                        style={{ color: 'var(--fg-muted)' }}
                    >
                        {b.name}
                    </Link>
                ))}
            </div>
        </section>
    );
}

export default BrandStrip;
