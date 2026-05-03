'use client';

import { useEffect, useState, useCallback, useRef, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { productsApi, Product } from '@/lib/api';
import { ProductCard } from '@/components/market/ProductCard';
import { Button } from '@/components/ui/button';

import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    ArrowLeft,
    Box,
    Filter,
    X,
    ChevronRight,
    SlidersHorizontal,
    Award,
    Flame,
} from 'lucide-react';
import { useInfiniteScroll } from '@/hooks/use-infinite-scroll';

interface Filters {
    brand: string;
    minPrice: string;
    maxPrice: string;
}

function BestSellersContent() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const [products, setProducts] = useState<Product[]>([]);
    const [availableBrands, setAvailableBrands] = useState<string[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [totalProducts, setTotalProducts] = useState(0);
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const filtersRef = useRef(0);

    const [filters, setFilters] = useState<Filters>({
        brand: searchParams.get('brand') || '',
        minPrice: searchParams.get('min_price') || '',
        maxPrice: searchParams.get('max_price') || '',
    });

    const loadProducts = useCallback(async (page: number, append: boolean) => {
        const currentFilterVersion = ++filtersRef.current;
        if (append) {
            setIsLoadingMore(true);
        } else {
            setIsLoading(true);
        }
        try {
            const response = await productsApi.getAll({
                page,
                per_page: 24,
                brand: filters.brand || undefined,
                min_price: filters.minPrice || undefined,
                max_price: filters.maxPrice || undefined,
                sort_by: 'offers_count',
            });
            if (filtersRef.current !== currentFilterVersion) return;
            if (response.data) {
                const newProducts = response.data.products;
                if (append) {
                    setProducts(prev => [...prev, ...newProducts]);
                } else {
                    setProducts(newProducts);
                }
                const lastPage = response.data.pagination?.last_page || 1;
                setHasMore(page < lastPage);
                setTotalProducts(response.data.pagination?.total || 0);
                if (response.data.filters?.brands) {
                    setAvailableBrands(response.data.filters.brands);
                }
            }
        } catch (error) {
            console.error('Failed to load products:', error);
        } finally {
            if (filtersRef.current === currentFilterVersion) {
                setIsLoading(false);
                setIsLoadingMore(false);
            }
        }
    }, [filters]);

    useEffect(() => {
        setCurrentPage(1);
        setHasMore(true);
        loadProducts(1, false);
    }, [loadProducts]);

    const handleLoadMore = useCallback(() => {
        const nextPage = currentPage + 1;
        setCurrentPage(nextPage);
        loadProducts(nextPage, true);
    }, [currentPage, loadProducts]);

    const { sentinelRef } = useInfiniteScroll({
        hasMore,
        isLoading: isLoading || isLoadingMore,
        onLoadMore: handleLoadMore,
    });

    const applyFilters = (newFilters: Filters) => {
        setFilters(newFilters);

        const params = new URLSearchParams();
        if (newFilters.brand) params.set('brand', newFilters.brand);
        if (newFilters.minPrice) params.set('min_price', newFilters.minPrice);
        if (newFilters.maxPrice) params.set('max_price', newFilters.maxPrice);

        const queryString = params.toString();
        router.push(`/market/cok-satanlar${queryString ? `?${queryString}` : ''}`, { scroll: false });
        setMobileFilterOpen(false);
    };

    const clearFilters = () => {
        applyFilters({ brand: '', minPrice: '', maxPrice: '' });
    };

    const hasActiveFilters = filters.brand || filters.minPrice || filters.maxPrice;

    const FilterContent = () => (
        <div className="space-y-5">
            {/* Brand */}
            {availableBrands.length > 0 && (
                <div>
                    <Label className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 block">
                        Marka
                    </Label>
                    <Select
                        value={filters.brand || 'all'}
                        onValueChange={(value) => applyFilters({ ...filters, brand: value === 'all' ? '' : value })}
                    >
                        <SelectTrigger className="w-full h-9">
                            <SelectValue placeholder="Marka seçin" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tüm Markalar</SelectItem>
                            {availableBrands.map((brand) => (
                                <SelectItem key={brand} value={brand}>
                                    {brand}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}

            {/* Price Range */}
            <div className="pt-4 border-t border-black/5 dark:border-white/5">
                <Label className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 block">
                    Fiyat Aralığı
                </Label>
                <div className="flex items-center gap-2">
                    <Input
                        type="number"
                        placeholder="Min"
                        value={filters.minPrice}
                        onChange={(e) => setFilters({ ...filters, minPrice: e.target.value })}
                        className="w-full h-9"
                        min="0"
                    />
                    <span className="text-slate-300 text-xs">&mdash;</span>
                    <Input
                        type="number"
                        placeholder="Max"
                        value={filters.maxPrice}
                        onChange={(e) => setFilters({ ...filters, maxPrice: e.target.value })}
                        className="w-full h-9"
                        min="0"
                    />
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    className="w-full mt-2 h-8 text-xs bg-[#ffedd5] text-[#ea580c] border-[#ffedd5] hover:bg-[#1e40af] dark:bg-[#1e40af]/30 dark:text-[#ffedd5] dark:border-[#c2410c] dark:hover:bg-[#1e40af]/50"
                    onClick={() => applyFilters(filters)}
                >
                    Fiyat Uygula
                </Button>
            </div>

            {hasActiveFilters && (
                <div className="pt-4 border-t border-black/5 dark:border-white/5">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="w-full h-8 text-xs text-red-500 hover:text-red-600 hover:bg-red-50/80 dark:hover:bg-red-900/20"
                        onClick={clearFilters}
                    >
                        <X className="w-3.5 h-3.5 mr-1.5" />
                        Filtreleri Temizle
                    </Button>
                </div>
            )}
        </div>
    );

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            {/* Breadcrumb */}
            <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                <Link href="/market" className="hover:text-blue-600 transition-colors">
                    Pazaryeri
                </Link>
                <ChevronRight className="w-4 h-4" />
                <span className="text-slate-900 dark:text-white font-medium">Çok Satanlar</span>
            </div>

            {/* Header */}
            <div className="bg-gradient-to-r from-amber-500 to-[#c2410c] rounded-lg p-6 mb-6">
                <div className="flex items-center gap-4">
                    <div className="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <Award className="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-white">
                            Çok Satanlar
                        </h1>
                        <p className="text-white/80 mt-1">
                            En popüler ve çok tercih edilen ürünler - {totalProducts} ürün
                        </p>
                    </div>
                </div>
            </div>

            {/* Filter Button */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-2">
                    <Flame className="w-5 h-5 text-[#ea580c]" />
                    <span className="text-slate-600 dark:text-slate-400">Teklif sayısına göre sıralanmış</span>
                </div>
                <Sheet open={mobileFilterOpen} onOpenChange={setMobileFilterOpen}>
                    <SheetTrigger asChild>
                        <Button variant="outline" className="lg:hidden gap-2">
                            <Filter className="w-4 h-4" />
                            Filtrele
                            {hasActiveFilters && <span className="w-2 h-2 rounded-full bg-blue-600" />}
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" className="w-80">
                        <SheetHeader>
                            <SheetTitle>Filtreler</SheetTitle>
                        </SheetHeader>
                        <div className="mt-6">
                            <FilterContent />
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            {/* Active Filters */}
            {hasActiveFilters && (
                <div className="flex flex-wrap items-center gap-2 mb-6">
                    <span className="text-sm text-slate-500">Aktif filtreler:</span>
                    {filters.brand && (
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full">
                            Marka: {filters.brand}
                            <button onClick={() => applyFilters({ ...filters, brand: '' })}>
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {(filters.minPrice || filters.maxPrice) && (
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full">
                            Fiyat: {filters.minPrice || '0'} - {filters.maxPrice || '\u221e'} TL
                            <button onClick={() => applyFilters({ ...filters, minPrice: '', maxPrice: '' })}>
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                </div>
            )}

            {/* Main Content */}
            <div className="flex gap-6">
                {/* Desktop Sidebar - Sticky */}
                <aside className="hidden lg:block w-60 flex-shrink-0">
                    <div className="sticky top-20 bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700">
                        <div className="px-5 py-3 border-b border-black/5 dark:border-white/5 bg-gradient-to-r from-[#ffedd5]/80 to-teal-50/50 dark:from-[#c2410c]/20 dark:to-teal-950/10 rounded-t-xl">
                            <h2 className="text-xs font-semibold text-[#ea580c] dark:text-[#ffedd5] uppercase tracking-widest flex items-center gap-2">
                                <SlidersHorizontal className="w-3.5 h-3.5" />
                                Filtreler
                            </h2>
                        </div>
                        <div className="px-5 py-4">
                            <FilterContent />
                        </div>
                    </div>
                </aside>

                {/* Products Grid */}
                <div className="flex-1 min-w-0">
                    <div className="bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700 overflow-clip">
                    {isLoading ? (
                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-3 p-3">
                            {[...Array(12)].map((_, i) => (
                                <Skeleton key={i} className="h-[120px] rounded-2xl" />
                            ))}
                        </div>
                    ) : products.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 px-4">
                            <Box className="h-16 w-16 text-slate-300 dark:text-slate-600 mb-4" />
                            <h2 className="text-xl font-semibold mb-2 text-slate-900 dark:text-white">
                                Ürün bulunamadı
                            </h2>
                            <p className="text-slate-500 dark:text-slate-400 mb-4 text-center">
                                {hasActiveFilters
                                    ? 'Filtreleri değiştirerek tekrar deneyebilirsiniz.'
                                    : 'Henüz ürün eklenmemiş.'}
                            </p>
                            {hasActiveFilters ? (
                                <Button onClick={clearFilters}>
                                    <X className="w-4 h-4 mr-2" />
                                    Filtreleri Temizle
                                </Button>
                            ) : (
                                <Link href="/market">
                                    <Button>
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Pazaryerine Dön
                                    </Button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-3 p-3">
                            {products.map((product, index) => (
                                <div key={product.id} className="relative">
                                    {index < 3 && (
                                        <div className="absolute top-2 left-2 z-10 w-7 h-7 bg-gradient-to-br from-amber-500 to-[#c2410c] rounded-full flex items-center justify-center text-white text-xs font-bold shadow-md">
                                            {index + 1}
                                        </div>
                                    )}
                                    <ProductCard product={product} />
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Infinite Scroll */}
                    {!isLoading && products.length > 0 && (
                        <div className="py-6">
                            {isLoadingMore && (
                                <div className="flex justify-center items-center gap-3 py-4">
                                    <div className="h-5 w-5 animate-spin rounded-full border-2 border-blue-600 border-t-transparent" />
                                    <span className="text-sm text-slate-500">Daha fazla ürün yükleniyor...</span>
                                </div>
                            )}
                            {hasMore && <div ref={sentinelRef} className="h-1" />}
                            {!hasMore && products.length > 0 && (
                                <p className="text-center text-sm text-slate-400 py-2">
                                    Tüm ürünler gösteriliyor ({products.length} / {totalProducts})
                                </p>
                            )}
                        </div>
                    )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function BestSellersPage() {
    return (
        <Suspense fallback={
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <Skeleton className="h-8 w-48 mb-6" />
                <Skeleton className="h-32 w-full rounded-lg mb-6" />
                <div className="bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700 overflow-hidden">
                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-3 p-3">
                        {[...Array(8)].map((_, i) => (
                            <Skeleton key={i} className="h-[120px] rounded-2xl" />
                        ))}
                    </div>
                </div>
            </div>
        }>
            <BestSellersContent />
        </Suspense>
    );
}
