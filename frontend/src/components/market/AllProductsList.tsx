'use client';

import React, { useEffect, useState, useCallback } from 'react';
import { ArrowRight, Loader2 } from 'lucide-react';
import Link from 'next/link';
import { productsApi, Product } from '@/lib/api';
import { ProductCard } from '@/components/market/ProductCard';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';

const INITIAL_LOAD = 15;
const LOAD_MORE_COUNT = 15;

export function AllProductsList() {
    const [products, setProducts] = useState<Product[]>([]);
    const [visibleCount, setVisibleCount] = useState(INITIAL_LOAD);
    const [isLoading, setIsLoading] = useState(true);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [hasMore, setHasMore] = useState(true);
    const [page, setPage] = useState(1);

    useEffect(() => {
        const loadProducts = async () => {
            try {
                const response = await productsApi.getAll({ page: 1, per_page: INITIAL_LOAD });
                const loaded = response.data?.products || [];
                setProducts(loaded);
                setHasMore(loaded.length >= INITIAL_LOAD);
            } catch (error) {
                console.error('Failed to load products', error);
            } finally {
                setIsLoading(false);
            }
        };
        loadProducts();
    }, []);

    const loadMore = useCallback(async () => {
        if (isLoadingMore) return;
        setIsLoadingMore(true);
        try {
            const nextPage = page + 1;
            const response = await productsApi.getAll({ page: nextPage, per_page: LOAD_MORE_COUNT });
            const newProducts = response.data?.products || [];
            if (newProducts.length > 0) {
                setProducts(prev => [...prev, ...newProducts]);
                setPage(nextPage);
                setVisibleCount(prev => prev + newProducts.length);
                setHasMore(newProducts.length >= LOAD_MORE_COUNT);
            } else {
                setHasMore(false);
            }
        } catch (error) {
            console.error('Failed to load more products', error);
        } finally {
            setIsLoadingMore(false);
        }
    }, [page, isLoadingMore]);

    if (isLoading) {
        return (
            <section className="py-8">
                <div className="flex items-center gap-3 mb-6">
                    <Skeleton className="w-10 h-10 rounded-lg" />
                    <Skeleton className="h-6 w-32" />
                </div>
                <div className="bg-white -mx-4 sm:-mx-7 px-4 sm:px-7 border-y border-[#f0eceb] py-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
                        {Array(INITIAL_LOAD).fill(0).map((_, i) => (
                            <Skeleton key={i} className="h-[120px] rounded-2xl" />
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    if (products.length === 0) return null;

    return (
        <section className="py-4">
            <div className="bg-white -mx-4 sm:-mx-7 px-4 sm:px-7 border-y border-[#f0eceb] py-12">
                <div className="flex items-center justify-between mb-6 pb-6 border-b border-[#f0eceb]">
                    <h2 className="text-2xl font-bold text-[#1a1a1a]">Tüm Ürünler</h2>
                    <Link href="/market/products" className="group flex items-center gap-1.5 text-sm font-semibold border-[1.5px] border-[#fbeede] text-[#b8651a] hover:bg-[#b8651a] hover:text-white rounded-[10px] px-4 py-1.5 transition-colors">
                        Tümünü Gör
                        <ArrowRight className="w-4 h-4" />
                    </Link>
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    {products.slice(0, visibleCount).map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>

                <div className="text-center pt-6 mt-4 border-t border-[#f0eceb]">
                    {hasMore ? (
                        <Button
                            className="bg-[#b8651a] text-white hover:bg-[#934f12] rounded-[14px] font-extrabold shadow-sm"
                            onClick={loadMore}
                            disabled={isLoadingMore}
                        >
                            {isLoadingMore ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    Yükleniyor...
                                </>
                            ) : (
                                <>
                                    Daha Fazla Göster
                                    <ArrowRight className="w-4 h-4 ml-2" />
                                </>
                            )}
                        </Button>
                    ) : (
                        <Link href="/market/products">
                            <Button className="bg-[#b8651a] text-white hover:bg-[#934f12] rounded-[14px] font-extrabold shadow-sm">
                                Tüm Ürünleri Gör
                                <ArrowRight className="w-4 h-4 ml-2" />
                            </Button>
                        </Link>
                    )}
                </div>
            </div>
        </section>
    );
}
