'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { categoriesApi, Category } from '@/lib/api';

interface UICategory {
    id: number | string;
    slug: string;
    name: string;
    icon: string;
    count?: number;
}

const FALLBACK: UICategory[] = [
    { id: 1, slug: 'defter-bloknot', name: 'Defter & Bloknot', icon: '📓', count: 1250 },
    { id: 2, slug: 'kalem-yazi', name: 'Kalem & Yazı', icon: '✏️', count: 2341 },
    { id: 3, slug: 'ofis', name: 'Ofis', icon: '🗂️', count: 980 },
    { id: 4, slug: 'okul', name: 'Okul', icon: '🎒', count: 1604 },
    { id: 5, slug: 'sanat-hobi', name: 'Sanat & Hobi', icon: '🎨', count: 720 },
    { id: 6, slug: 'kagit', name: 'Kağıt', icon: '📄', count: 540 },
    { id: 7, slug: 'cizim', name: 'Çizim & Mimari', icon: '📐', count: 412 },
    { id: 8, slug: 'boya', name: 'Boya & Renk', icon: '🖍️', count: 386 },
    { id: 9, slug: 'klasor', name: 'Klasör & Dosya', icon: '📁', count: 472 },
    { id: 10, slug: 'hesap', name: 'Hesap Makinesi', icon: '🧮', count: 124 },
    { id: 11, slug: 'sinif', name: 'Sınıf & Eğitim', icon: '📚', count: 298 },
    { id: 12, slug: 'aksesuar', name: 'Aksesuar', icon: '📎', count: 612 },
];

export function CategoryGrid() {
    const [items, setItems] = useState<UICategory[]>(FALLBACK);

    useEffect(() => {
        let active = true;
        categoriesApi
            .getAll()
            .then((res) => {
                if (!active) return;
                const data = res.data?.categories;
                if (data && Array.isArray(data) && data.length > 0) {
                    const mapped: UICategory[] = data.slice(0, 12).map((c: Category, i: number) => ({
                        id: c.id,
                        slug: c.slug,
                        name: c.name,
                        icon: FALLBACK[i % FALLBACK.length].icon,
                        count: c.products_count,
                    }));
                    setItems(mapped);
                }
            })
            .catch(() => {
                /* keep fallback */
            });
        return () => {
            active = false;
        };
    }, []);

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
            <div className="flex justify-between items-baseline mb-4">
                <h2 className="text-xl font-semibold" style={{ color: 'var(--fg)' }}>
                    Kategoriler
                </h2>
                <Link
                    href="/market/products"
                    className="text-sm font-medium"
                    style={{ color: 'var(--accent)' }}
                >
                    Tümünü gör →
                </Link>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                {items.map((c) => (
                    <Link
                        key={c.id}
                        href={`/market/category/${c.slug}`}
                        className="card flex flex-col gap-2 p-4 transition-colors"
                        style={{ borderColor: 'var(--border)' }}
                    >
                        <div
                            className="w-10 h-10 rounded-md flex items-center justify-center text-lg"
                            style={{ background: 'var(--accent-soft)', color: 'var(--accent)' }}
                        >
                            {c.icon}
                        </div>
                        <div className="text-[13px] font-medium leading-tight" style={{ color: 'var(--fg)' }}>
                            {c.name}
                        </div>
                        {c.count != null && (
                            <div className="mono text-[11px]" style={{ color: 'var(--fg-soft)' }}>
                                {c.count.toLocaleString('tr-TR')} ürün
                            </div>
                        )}
                    </Link>
                ))}
            </div>
        </section>
    );
}

export default CategoryGrid;
