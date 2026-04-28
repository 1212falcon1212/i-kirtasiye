'use client';

import { useEffect, useState, useCallback, useRef, Suspense } from 'react';
import { useParams, useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { api, productsApi, Product } from '@/lib/api';
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
    Building2,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useInfiniteScroll } from '@/hooks/use-infinite-scroll';

interface Brand {
    id: number;
    name: string;
    slug: string;
    description?: string;
    logo?: string;
    products_count?: number;
}

interface Filters {
    category: string;
    minPrice: string;
    maxPrice: string;
    sortBy: string;
}

function MarketBrandContent() {
    const params = useParams();
    const router = useRouter();
    const searchParams = useSearchParams();
    const slug = params.slug as string;

    const [brandInfo, setBrandInfo] = useState<Brand | null>(null);
    const [products, setProducts] = useState<Product[]>([]);
    const [availableCategories, setAvailableCategories] = useState<{ id: number; name: string; slug: string }[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [hasMore, setHasMore] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalProducts, setTotalProducts] = useState(0);
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const filtersRef = useRef(0);

    // Initialize filters from URL params
    const [filters, setFilters] = useState<Filters>({
        category: searchParams.get('category') || '',
        minPrice: searchParams.get('min_price') || '',
        maxPrice: searchParams.get('max_price') || '',
        sortBy: searchParams.get('sort_by') || 'offers_count',
    });

    // Convert slug to brand name for display
    const formatSlugToName = (slug: string): string => {
        return slug.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
    };

    const brandName = brandInfo?.name || formatSlugToName(slug);

    const loadProducts = useCallback(async (page: number, append: boolean) => {
        const currentVersion = filtersRef.current;

        if (append) {
            setIsLoadingMore(true);
        } else {
            setIsLoading(true);
        }

        try {
            // Load brand info on first page
            if (!append) {
                try {
                    const brandResponse = await api.get<{ status: string; data: Brand }>(`/brands/${slug}`);
                    if (brandResponse.data?.data) {
                        setBrandInfo(brandResponse.data.data);
                    }
                } catch (error) {
                    console.error('Failed to load brand info:', error);
                }
            }

            // Load products by brand name (extracted from slug)
            const brandNameForSearch = formatSlugToName(slug);
            const response = await productsApi.getAll({
                brand: brandNameForSearch,
                page,
                per_page: 12,
                category: filters.category || undefined,
                min_price: filters.minPrice || undefined,
                max_price: filters.maxPrice || undefined,
                sort_by: filters.sortBy,
            });

            // Stale response guard
            if (filtersRef.current !== currentVersion) return;

            if (response.data) {
                const newProducts = response.data.products;
                const lastPage = response.data.pagination?.last_page || 1;

                if (append) {
                    setProducts((prev) => [...prev, ...newProducts]);
                } else {
                    setProducts(newProducts);
                }

                setTotalProducts(response.data.pagination?.total || 0);
                setHasMore(page < lastPage);
                setCurrentPage(page);

                // Extract unique categories from products for filter
                if (newProducts.length > 0) {
                    const categories = new Map<number, { id: number; name: string; slug: string }>();
                    newProducts.forEach((product: Product) => {
                        if (product.category && !categories.has(product.category.id)) {
                            categories.set(product.category.id, product.category);
                        }
                    });
                    setAvailableCategories((prev) => {
                        const merged = new Map(prev.map((c) => [c.id, c]));
                        categories.forEach((v, k) => merged.set(k, v));
                        return Array.from(merged.values());
                    });
                }
            }
        } catch (error) {
            console.error('Failed to load brand products:', error);
        } finally {
            if (append) {
                setIsLoadingMore(false);
            } else {
                setIsLoading(false);
            }
        }
    }, [slug, filters]);

    // Reset & load when filters or slug change
    useEffect(() => {
        filtersRef.current += 1;
        setProducts([]);
        setHasMore(true);
        setCurrentPage(1);
        loadProducts(1, false);
    }, [loadProducts]);

    const handleLoadMore = useCallback(() => {
        const nextPage = currentPage + 1;
        loadProducts(nextPage, true);
    }, [currentPage, loadProducts]);

    const { sentinelRef } = useInfiniteScroll({
        hasMore,
        isLoading: isLoading || isLoadingMore,
        onLoadMore: handleLoadMore,
    });

    // Update URL when filters change
    const applyFilters = (newFilters: Filters) => {
        setFilters(newFilters);

        // Update URL params
        const params = new URLSearchParams();
        if (newFilters.category) params.set('category', newFilters.category);
        if (newFilters.minPrice) params.set('min_price', newFilters.minPrice);
        if (newFilters.maxPrice) params.set('max_price', newFilters.maxPrice);
        if (newFilters.sortBy && newFilters.sortBy !== 'offers_count') {
            params.set('sort_by', newFilters.sortBy);
        }

        const queryString = params.toString();
        router.push(`/market/marka/${slug}${queryString ? `?${queryString}` : ''}`, { scroll: false });
        setMobileFilterOpen(false);
    };

    const clearFilters = () => {
        const clearedFilters: Filters = {
            category: '',
            minPrice: '',
            maxPrice: '',
            sortBy: 'offers_count',
        };
        applyFilters(clearedFilters);
    };

    const hasActiveFilters = filters.category || filters.minPrice || filters.maxPrice || filters.sortBy !== 'offers_count';

    // Filter Sidebar Content
    const FilterContent = () => (
        <div className="space-y-5">
            {/* Sort */}
            <div>
                <Label className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 block">
                    Sıralama
                </Label>
                <Select
                    value={filters.sortBy}
                    onValueChange={(value) => applyFilters({ ...filters, sortBy: value })}
                >
                    <SelectTrigger className="w-full h-9">
                        <SelectValue placeholder="Sıralama seçin" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="offers_count">Teklif Sayısı</SelectItem>
                        <SelectItem value="price_asc">Fiyat (Artan)</SelectItem>
                        <SelectItem value="price_desc">Fiyat (Azalan)</SelectItem>
                        <SelectItem value="name">İsim (A-Z)</SelectItem>
                        <SelectItem value="newest">En Yeniler</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Categories */}
            {availableCategories.length > 0 && (
                <div className="pt-4 border-t border-black/5 dark:border-white/5">
                    <Label className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 block">
                        Kategori
                    </Label>
                    <Select
                        value={filters.category || 'all'}
                        onValueChange={(value) => applyFilters({ ...filters, category: value === 'all' ? '' : value })}
                    >
                        <SelectTrigger className="w-full h-9">
                            <SelectValue placeholder="Kategori seçin" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tüm Kategoriler</SelectItem>
                            {availableCategories.map((cat) => (
                                <SelectItem key={cat.id} value={cat.slug}>
                                    {cat.name}
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
                    className="w-full mt-2 h-8 text-xs bg-[#fbeede] text-[#b8651a] border-[#fbeede] hover:bg-[#934f12] dark:bg-[#934f12]/30 dark:text-[#fbeede] dark:border-[#934f12] dark:hover:bg-[#934f12]/50"
                    onClick={() => applyFilters(filters)}
                >
                    Fiyat Uygula
                </Button>
            </div>

            {/* Clear Filters */}
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
                <Link href="/market/markalar" className="hover:text-blue-600 transition-colors">
                    Markalar
                </Link>
                <ChevronRight className="w-4 h-4" />
                <span className="text-slate-900 dark:text-white font-medium">{brandName}</span>
            </div>

            {/* Brand Header */}
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <div className="flex items-center gap-6">
                    {/* Brand Logo */}
                    <div className="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center flex-shrink-0">
                        {brandInfo?.logo ? (
                            <img
                                src={brandInfo.logo}
                                alt={brandName}
                                className="max-w-full max-h-full object-contain p-2"
                            />
                        ) : (
                            <Building2 className="w-10 h-10 text-slate-400" />
                        )}
                    </div>

                    {/* Brand Info */}
                    <div className="flex-1">
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-1">
                            {brandName}
                        </h1>
                        {brandInfo?.description && (
                            <p className="text-slate-500 dark:text-slate-400 text-sm mb-2">
                                {brandInfo.description}
                            </p>
                        )}
                        <p className="text-slate-600 dark:text-slate-300">
                            <span className="font-semibold">{totalProducts}</span> ürün bulundu
                        </p>
                    </div>
                </div>
            </div>

            {/* Header with Filter Button */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                        {brandName} Ürünleri
                    </h2>
                </div>

                {/* Mobile Filter Button */}
                <Sheet open={mobileFilterOpen} onOpenChange={setMobileFilterOpen}>
                    <SheetTrigger asChild>
                        <Button variant="outline" className="lg:hidden gap-2">
                            <Filter className="w-4 h-4" />
                            Filtrele
                            {hasActiveFilters && (
                                <span className="w-2 h-2 rounded-full bg-blue-600" />
                            )}
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

            {/* Active Filters Tags */}
            {hasActiveFilters && (
                <div className="flex flex-wrap items-center gap-2 mb-6">
                    <span className="text-sm text-slate-500">Aktif filtreler:</span>
                    {filters.category && (
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full">
                            Kategori: {filters.category}
                            <button
                                onClick={() => applyFilters({ ...filters, category: '' })}
                                className="hover:text-blue-900 dark:hover:text-blue-100"
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {(filters.minPrice || filters.maxPrice) && (
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full">
                            Fiyat: {filters.minPrice || '0'} - {filters.maxPrice || '\u221e'} TL
                            <button
                                onClick={() => applyFilters({ ...filters, minPrice: '', maxPrice: '' })}
                                className="hover:text-blue-900 dark:hover:text-blue-100"
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {filters.sortBy !== 'offers_count' && (
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium rounded-full">
                            <SlidersHorizontal className="w-3 h-3" />
                            {filters.sortBy === 'price_asc' && 'Fiyat (Artan)'}
                            {filters.sortBy === 'price_desc' && 'Fiyat (Azalan)'}
                            {filters.sortBy === 'name' && 'İsim (A-Z)'}
                            {filters.sortBy === 'newest' && 'En Yeniler'}
                        </span>
                    )}
                </div>
            )}

            {/* Main Content */}
            <div className="flex gap-6">
                {/* Desktop Sidebar - Sticky */}
                <aside className="hidden lg:block w-60 flex-shrink-0">
                    <div className="sticky top-20 bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700">
                        <div className="px-5 py-3 border-b border-black/5 dark:border-white/5 bg-gradient-to-r from-[#fbeede]/80 to-teal-50/50 dark:from-[#934f12]/20 dark:to-teal-950/10 rounded-t-xl">
                            <h2 className="text-xs font-semibold text-[#b8651a] dark:text-[#fbeede] uppercase tracking-widest flex items-center gap-2">
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
                                {hasActiveFilters ? 'Filtrelere uygun ürün bulunamadı' : 'Bu markada ürün yok'}
                            </h2>
                            <p className="text-slate-500 dark:text-slate-400 mb-4 text-center">
                                {hasActiveFilters
                                    ? 'Filtreleri değiştirerek tekrar deneyebilirsiniz.'
                                    : 'Başka markalara göz atabilirsiniz.'}
                            </p>
                            {hasActiveFilters ? (
                                <Button onClick={clearFilters}>
                                    <X className="w-4 h-4 mr-2" />
                                    Filtreleri Temizle
                                </Button>
                            ) : (
                                <Link href="/market/markalar">
                                    <Button>
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Markalara Dön
                                    </Button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-3 p-3">
                            {products.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    )}

                    {/* Infinite Scroll Sentinel */}
                    {!isLoading && products.length > 0 && (
                        <div className="py-6">
                            <div ref={sentinelRef} />
                            {isLoadingMore && (
                                <div className="flex justify-center items-center gap-2 py-4">
                                    <div className="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600 dark:border-slate-600 dark:border-t-slate-300" />
                                    <span className="text-sm text-slate-500 dark:text-slate-400">
                                        Daha fazla ürün yükleniyor...
                                    </span>
                                </div>
                            )}
                            {!hasMore && !isLoadingMore && (
                                <p className="text-center text-sm text-slate-400 dark:text-slate-500 py-2">
                                    Tüm ürünler gösteriliyor
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

export function BrandClient() {
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
            <MarketBrandContent />
        </Suspense>
    );
}
