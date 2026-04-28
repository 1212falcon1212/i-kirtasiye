'use client';

import { useEffect, useState, useCallback, useRef, Suspense } from 'react';
import { useInfiniteScroll } from '@/hooks/use-infinite-scroll';
import { useParams, useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { productsApi, Product, Category, api } from '@/lib/api';
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
} from 'lucide-react';
import { cn } from '@/lib/utils';

// Slug'ı okunabilir isme dönüştür
const formatSlugToName = (slug: string): string => {
    const words = slug.replace(/-/g, ' ').split(' ');
    return words.map(word => {
        if (!word) return '';
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }).join(' ');
};

interface Filters {
    brand: string;
    minPrice: string;
    maxPrice: string;
    sortBy: string;
}

interface Subcategory {
    id: number;
    name: string;
    slug: string;
    full_slug?: string;
}

interface BreadcrumbItem {
    id: number;
    name: string;
    slug: string;
    full_slug?: string;
}

interface CategoryInfo extends Category {
    full_slug?: string;
    parent?: {
        id: number;
        name: string;
        slug: string;
        full_slug?: string;
    };
}

function MarketCategoryContent() {
    const params = useParams();
    const router = useRouter();
    const searchParams = useSearchParams();

    // Handle catch-all route - slug can be string or string[]
    const slugArray = Array.isArray(params.slug) ? params.slug : [params.slug];
    const fullSlug = slugArray.join('/');
    const lastSlug = slugArray[slugArray.length - 1];

    const [categoryInfo, setCategoryInfo] = useState<CategoryInfo | null>(null);
    const [breadcrumb, setBreadcrumb] = useState<BreadcrumbItem[]>([]);
    const [subcategories, setSubcategories] = useState<Subcategory[]>([]);
    const [availableBrands, setAvailableBrands] = useState<string[]>([]);
    const [products, setProducts] = useState<Product[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [totalProducts, setTotalProducts] = useState(0);
    const filtersRef = useRef(0);
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);

    // Initialize filters from URL params
    const [filters, setFilters] = useState<Filters>({
        brand: searchParams.get('brand') || '',
        minPrice: searchParams.get('min_price') || '',
        maxPrice: searchParams.get('max_price') || '',
        sortBy: searchParams.get('sort_by') || 'offers_count',
    });

    // Kategori adını belirle: API'den gelen bilgi veya slug'dan dönüştür
    const categoryName = categoryInfo?.name || formatSlugToName(lastSlug || '');

    // Load category info (breadcrumb, subcategories) - runs once per slug change
    const loadCategoryInfo = useCallback(async () => {
        try {
            const categoryResponse = await api.get<{ category?: CategoryInfo & { children?: Subcategory[] }; breadcrumb?: BreadcrumbItem[] }>(`/categories/slug/${fullSlug}`);
            if (categoryResponse.data) {
                const data = categoryResponse.data;
                if (data.category) {
                    setCategoryInfo(data.category);
                }
                if (data.breadcrumb) {
                    setBreadcrumb(data.breadcrumb);
                }
                if (data.category?.children) {
                    setSubcategories(data.category.children);
                }
            }
        } catch (error) {
            console.error('Failed to load category:', error);
        }
    }, [fullSlug]);

    // Load products with append support for infinite scroll
    const loadProducts = useCallback(async (page: number, append: boolean) => {
        const requestId = ++filtersRef.current;
        if (append) {
            setIsLoadingMore(true);
        } else {
            setIsLoading(true);
        }
        try {
            const response = await productsApi.getAll({
                category: lastSlug,
                page,
                per_page: 12,
                brand: filters.brand || undefined,
                min_price: filters.minPrice || undefined,
                max_price: filters.maxPrice || undefined,
                sort_by: filters.sortBy,
            });
            // Discard stale responses (filter changed while loading)
            if (filtersRef.current !== requestId) return;
            if (response.data) {
                const newProducts = response.data.products;
                setProducts(prev => append ? [...prev, ...newProducts] : newProducts);
                setTotalProducts(response.data.pagination?.total || 0);
                setHasMore(page < (response.data.pagination?.last_page || 1));

                // Set filter options from API response
                if (response.data.filters) {
                    if (response.data.filters.brands) {
                        setAvailableBrands(response.data.filters.brands);
                    }
                    if (response.data.filters.subcategories && subcategories.length === 0) {
                        setSubcategories(response.data.filters.subcategories);
                    }
                    if (response.data.filters.category && !categoryInfo) {
                        setCategoryInfo(response.data.filters.category);
                    }
                }
            }
        } catch (error) {
            console.error('Failed to load products:', error);
        } finally {
            if (filtersRef.current === requestId) {
                setIsLoading(false);
                setIsLoadingMore(false);
            }
        }
    }, [lastSlug, filters]);

    // Load category info when slug changes
    useEffect(() => {
        loadCategoryInfo();
    }, [loadCategoryInfo]);

    // Initial load & filter/slug changes - reset products
    useEffect(() => {
        setProducts([]);
        setCurrentPage(1);
        setHasMore(true);
        loadProducts(1, false);
    }, [loadProducts]);

    // Load more handler for infinite scroll
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

    // Update URL when filters change
    const applyFilters = (newFilters: Filters) => {
        setFilters(newFilters);

        // Update URL params
        const params = new URLSearchParams();
        if (newFilters.brand) params.set('brand', newFilters.brand);
        if (newFilters.minPrice) params.set('min_price', newFilters.minPrice);
        if (newFilters.maxPrice) params.set('max_price', newFilters.maxPrice);
        if (newFilters.sortBy && newFilters.sortBy !== 'offers_count') {
            params.set('sort_by', newFilters.sortBy);
        }

        const queryString = params.toString();
        router.push(`/market/category/${fullSlug}${queryString ? `?${queryString}` : ''}`, { scroll: false });
        setMobileFilterOpen(false);
    };

    const clearFilters = () => {
        const clearedFilters: Filters = {
            brand: '',
            minPrice: '',
            maxPrice: '',
            sortBy: 'offers_count',
        };
        applyFilters(clearedFilters);
    };

    const hasActiveFilters = filters.brand || filters.minPrice || filters.maxPrice || filters.sortBy !== 'offers_count';

    // Get subcategory link - use full_slug if available
    const getSubcategoryLink = (sub: Subcategory) => {
        if (sub.full_slug) {
            return `/market/category/${sub.full_slug}`;
        }
        // Fallback: append to current path
        return `/market/category/${fullSlug}/${sub.slug}`;
    };

    // Filter Sidebar Content - shared between desktop and mobile
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

            {/* Subcategories */}
            {subcategories.length > 0 && (
                <div className="pt-4 border-t border-black/5 dark:border-white/5">
                    <Label className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 block">
                        Alt Kategoriler
                    </Label>
                    <div className="space-y-0.5">
                        {subcategories.map((sub) => (
                            <Link
                                key={sub.id}
                                href={getSubcategoryLink(sub)}
                                className="flex items-center justify-between px-2.5 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-[#fbeede] rounded-md transition-colors group"
                            >
                                <span className="group-hover:text-[#b8651a]">
                                    {sub.name}
                                </span>
                                <ChevronRight className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-[#b8651a]" />
                            </Link>
                        ))}
                    </div>
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

            {/* Brand */}
            {availableBrands.length > 0 && (
                <div className="pt-4 border-t border-black/5 dark:border-white/5">
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
            {/* Dynamic Breadcrumb */}
            <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6 overflow-x-auto whitespace-nowrap pb-2">
                <Link href="/market" className="hover:text-blue-600 transition-colors flex-shrink-0">
                    Pazaryeri
                </Link>

                {/* Show breadcrumb from API if available */}
                {breadcrumb.length > 0 ? (
                    breadcrumb.map((item, index) => (
                        <span key={item.id} className="flex items-center gap-2 flex-shrink-0">
                            <ChevronRight className="w-4 h-4" />
                            {index === breadcrumb.length - 1 ? (
                                <span className="text-slate-900 dark:text-white font-medium">{item.name}</span>
                            ) : (
                                <Link
                                    href={`/market/category/${item.full_slug || item.slug}`}
                                    className="hover:text-blue-600 transition-colors"
                                >
                                    {item.name}
                                </Link>
                            )}
                        </span>
                    ))
                ) : (
                    /* Fallback: Build breadcrumb from URL slugs */
                    slugArray.map((slug, index) => (
                        <span key={index} className="flex items-center gap-2 flex-shrink-0">
                            <ChevronRight className="w-4 h-4" />
                            {index === slugArray.length - 1 ? (
                                <span className="text-slate-900 dark:text-white font-medium">
                                    {categoryInfo?.name || formatSlugToName(slug || '')}
                                </span>
                            ) : (
                                <Link
                                    href={`/market/category/${slugArray.slice(0, index + 1).join('/')}`}
                                    className="hover:text-blue-600 transition-colors"
                                >
                                    {formatSlugToName(slug || '')}
                                </Link>
                            )}
                        </span>
                    ))
                )}
            </div>

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                        {categoryName}
                    </h1>
                    <p className="text-slate-500 dark:text-slate-400 mt-1">
                        {totalProducts} ürün bulundu
                    </p>
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
                    {filters.brand && (
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full">
                            Marka: {filters.brand}
                            <button
                                onClick={() => applyFilters({ ...filters, brand: '' })}
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
                                    {hasActiveFilters ? 'Filtrelere uygun ürün bulunamadı' : 'Bu kategoride ürün yok'}
                                </h2>
                                <p className="text-slate-500 dark:text-slate-400 mb-4 text-center">
                                    {hasActiveFilters
                                        ? 'Filtreleri değiştirerek tekrar deneyebilirsiniz.'
                                        : 'Başka kategorilere göz atabilirsiniz.'}
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
                                {products.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>
                        )}

                        {/* Infinite Scroll Sentinel & Loading */}
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

export function CategoryClient() {
    return (
        <Suspense fallback={
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <Skeleton className="h-8 w-48 mb-6" />
                <div className="bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700 overflow-hidden">
                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-3 p-3">
                        {[...Array(8)].map((_, i) => (
                            <Skeleton key={i} className="h-[120px] rounded-2xl" />
                        ))}
                    </div>
                </div>
            </div>
        }>
            <MarketCategoryContent />
        </Suspense>
    );
}
