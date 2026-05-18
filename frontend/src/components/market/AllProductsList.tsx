'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ArrowRight } from 'lucide-react';
import { cmsApi, type Product } from '@/lib/api';
import { ProductCard } from '@/components/market/ProductCard';
import { Skeleton } from '@/components/ui/skeleton';

export function AllProductsList() {
    const [products, setProducts] = useState<Product[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let active = true;
        cmsApi
            .getRandomProducts()
            .then((res) => {
                if (!active) return;
                setProducts(res.data?.products ?? []);
            })
            .catch(() => {
                if (!active) return;
                setProducts([]);
            })
            .finally(() => {
                if (active) setIsLoading(false);
            });
        return () => {
            active = false;
        };
    }, []);

    if (isLoading) {
        return (
            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-10">
                <div className="flex justify-between items-baseline mb-4">
                    <Skeleton className="h-7 w-40" />
                    <Skeleton className="h-5 w-24" />
                </div>
<div className="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                    {Array.from({ length: 16 }).map((_, i) => (
                        <Skeleton key={i} className="h-[290px] rounded-[10px]" />
                    ))}
                </div>
            </section>
        );
    }

    if (products.length === 0) return null;

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-10">
            <div className="flex justify-between items-baseline mb-4">
                <div className="flex items-center gap-3">
                    <div className="w-1.5 h-7 bg-gradient-to-b from-[#1e3a8a] to-[#1e40af] rounded-full" />
                    <h2 className="text-xl font-bold" style={{ color: 'var(--fg)' }}>
                        Tüm Ürünler
                    </h2>
                </div>
                <Link
                    href="/market/products"
                    className="inline-flex items-center gap-1.5 text-sm font-semibold transition-colors px-4 py-2 rounded-lg bg-[#1e3a8a] text-white hover:bg-[#1e40af] hover:shadow-md"
                >
                    Tümünü gör
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>

            <div className="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                {products.slice(0, 16).map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
        </section>
    );
}

export default AllProductsList;
