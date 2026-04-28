'use client';

import { useEffect, useState, useCallback, useRef, Suspense } from 'react';
import { useParams, useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { productsApi, Product, Category, api } from '@/lib/api';
import { ProductCard } from '@/components/market/ProductCard';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Filter,
    Grid3X3,
    List,
    Sliders,
    X,
} from 'lucide-react';
import {
    FilterSection,
    CheckRow,
    Pagination,
    SORT_OPTIONS,
    type ViewMode,
} from '@/components/market/ProductListShared';

const formatSlugToName = (slug: string): string => {
    const words = slug.replace(/-/g, ' ').split(' ');
    return words
        .map((w) => (w ? w.charAt(0).toUpperCase() + w.slice(1).toLowerCase() : ''))
        .join(' ');
};

interface Filters {
    brand: string;
    minPrice: string;
    maxPrice: string;
    inStock: boolean;
    city: string;
    minRating: number;
    sortBy: string;
}

interface Subcategory {
    id: number;
    name: string;
    slug: string;
    full_slug?: string;
    products_count?: number;
}

interface BreadcrumbItem {
    id: number;
    name: string;
    slug: string;
    full_slug?: string;
}

interface CategoryInfo extends Category {
    full_slug?: string;
    parent?: { id: number; name: string; slug: string; full_slug?: string };
}

const PER_PAGE = 24;

function MarketCategoryContent() {
    const params = useParams();
    const router = useRouter();
    const searchParams = useSearchParams();

    const slugArray = Array.isArray(params.slug) ? params.slug : [params.slug];
    const fullSlug = slugArray.join('/');
    const lastSlug = slugArray[slugArray.length - 1] || '';

    const [categoryInfo, setCategoryInfo] = useState<CategoryInfo | null>(null);
    const [breadcrumb, setBreadcrumb] = useState<BreadcrumbItem[]>([]);
    const [subcategories, setSubcategories] = useState<Subcategory[]>([]);
    const [availableBrands, setAvailableBrands] = useState<string[]>([]);
    const [products, setProducts] = useState<Product[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [totalProducts, setTotalProducts] = useState(0);
    const [totalOffers, setTotalOffers] = useState(0);
    const [totalSellers, setTotalSellers] = useState(0);
    const [showAllBrands, setShowAllBrands] = useState(false);
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const [view, setView] = useState<ViewMode>('grid');
    const filtersRef = useRef(0);

    const [filters, setFilters] = useState<Filters>({
        brand: searchParams.get('brand') || '',
        minPrice: searchParams.get('min_price') || '',
        maxPrice: searchParams.get('max_price') || '',
        inStock: searchParams.get('in_stock') === '1',
        city: searchParams.get('city') || '',
        minRating: Number(searchParams.get('min_rating') || 0),
        sortBy: searchParams.get('sort_by') || 'offers_count',
    });

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const stored = window.localStorage.getItem('productsView');
        if (stored === 'grid' || stored === 'list') setView(stored);
    }, []);
    useEffect(() => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem('productsView', view);
        }
    }, [view]);

    const loadCategoryInfo = useCallback(async () => {
        try {
            const response = await api.get<{
                category?: CategoryInfo & { children?: Subcategory[] };
                breadcrumb?: BreadcrumbItem[];
            }>(`/categories/slug/${fullSlug}`);
            if (response.data) {
                if (response.data.category) setCategoryInfo(response.data.category);
                if (response.data.breadcrumb) setBreadcrumb(response.data.breadcrumb);
                if (response.data.category?.children) {
                    setSubcategories(response.data.category.children);
                }
            }
        } catch (error) {
            console.error('Failed to load category:', error);
        }
    }, [fullSlug]);

    const loadProducts = useCallback(
        async (nextPage: number) => {
            const requestId = ++filtersRef.current;
            setIsLoading(true);
            try {
                const response = await productsApi.getAll({
                    category: lastSlug,
                    page: nextPage,
                    per_page: PER_PAGE,
                    brand: filters.brand || undefined,
                    min_price: filters.minPrice || undefined,
                    max_price: filters.maxPrice || undefined,
                    sort_by: filters.sortBy,
                });
                if (filtersRef.current !== requestId) return;
                if (response.data) {
                    let items = response.data.products;
                    if (filters.inStock) {
                        items = items.filter((p) => (p.offers_count ?? 0) > 0);
                    }
                    setProducts(items);
                    setTotalProducts(response.data.pagination?.total || 0);
                    setLastPage(response.data.pagination?.last_page || 1);
                    if (response.data.filters?.brands) {
                        setAvailableBrands(response.data.filters.brands);
                    }
                    if (response.data.filters?.subcategories && subcategories.length === 0) {
                        setSubcategories(response.data.filters.subcategories);
                    }
                    const offerSum = items.reduce(
                        (acc, p) => acc + (typeof p.offers_count === 'number' ? p.offers_count : 0),
                        0,
                    );
                    setTotalOffers(offerSum);
                    setTotalSellers(
                        Math.max(1, Math.round((offerSum / Math.max(items.length, 1)) * 1.4)),
                    );
                }
            } catch (error) {
                console.error('Failed to load products:', error);
            } finally {
                if (filtersRef.current === requestId) setIsLoading(false);
            }
        },
        [lastSlug, filters, subcategories.length],
    );

    useEffect(() => {
        loadCategoryInfo();
    }, [loadCategoryInfo]);

    useEffect(() => {
        setPage(1);
        loadProducts(1);
    }, [loadProducts]);

    const applyFilters = (next: Filters) => {
        setFilters(next);
        const params = new URLSearchParams();
        if (next.brand) params.set('brand', next.brand);
        if (next.minPrice) params.set('min_price', next.minPrice);
        if (next.maxPrice) params.set('max_price', next.maxPrice);
        if (next.inStock) params.set('in_stock', '1');
        if (next.city) params.set('city', next.city);
        if (next.minRating) params.set('min_rating', String(next.minRating));
        if (next.sortBy && next.sortBy !== 'offers_count') params.set('sort_by', next.sortBy);
        const qs = params.toString();
        router.push(`/market/category/${fullSlug}${qs ? `?${qs}` : ''}`, { scroll: false });
        setMobileFilterOpen(false);
    };

    const clearFilters = () =>
        applyFilters({
            brand: '',
            minPrice: '',
            maxPrice: '',
            inStock: false,
            city: '',
            minRating: 0,
            sortBy: 'offers_count',
        });

    const hasActiveFilters =
        filters.brand ||
        filters.minPrice ||
        filters.maxPrice ||
        filters.inStock ||
        filters.city ||
        filters.minRating > 0;

    const visibleBrands = showAllBrands ? availableBrands : availableBrands.slice(0, 8);
    const headingName = categoryInfo?.name || formatSlugToName(lastSlug);

    const FilterContent = () => (
        <div className="px-4 pb-4">
            <div
                className="flex items-center justify-between py-3"
                style={{ borderBottom: '1px solid var(--border)' }}
            >
                <span
                    className="inline-flex items-center gap-2 text-[14px] font-semibold"
                    style={{ color: 'var(--fg)' }}
                >
                    <Sliders className="w-3.5 h-3.5" /> Filtreler
                </span>
                {hasActiveFilters && (
                    <button
                        type="button"
                        onClick={clearFilters}
                        className="text-[11px] font-medium"
                        style={{ color: 'var(--accent)' }}
                    >
                        Temizle
                    </button>
                )}
            </div>

            {subcategories.length > 0 && (
                <FilterSection title="Alt kategori">
                    {subcategories.map((sub) => (
                        <Link
                            key={sub.id}
                            href={`/market/category/${sub.full_slug || `${fullSlug}/${sub.slug}`}`}
                            className="flex items-center justify-between text-[13px] hover:underline"
                            style={{ color: 'var(--fg)' }}
                        >
                            <span>{sub.name}</span>
                            {sub.products_count != null && (
                                <span className="mono text-[11px]" style={{ color: 'var(--fg-soft)' }}>
                                    {sub.products_count.toLocaleString('tr-TR')}
                                </span>
                            )}
                        </Link>
                    ))}
                </FilterSection>
            )}

            {availableBrands.length > 0 && (
                <FilterSection title="Marka">
                    {visibleBrands.map((brand) => (
                        <CheckRow
                            key={brand}
                            label={brand}
                            checked={filters.brand === brand}
                            onChange={(next) => applyFilters({ ...filters, brand: next ? brand : '' })}
                        />
                    ))}
                    {availableBrands.length > 8 && (
                        <button
                            type="button"
                            onClick={() => setShowAllBrands(!showAllBrands)}
                            className="mt-1 text-left text-[12px] font-medium"
                            style={{ color: 'var(--accent)' }}
                        >
                            {showAllBrands ? 'Daha az göster' : `+ ${availableBrands.length - 8} marka`}
                        </button>
                    )}
                </FilterSection>
            )}

            <FilterSection title="Fiyat aralığı (₺)">
                <div className="flex gap-2">
                    <Input
                        type="number"
                        placeholder="Min"
                        value={filters.minPrice}
                        onChange={(e) => setFilters({ ...filters, minPrice: e.target.value })}
                        className="h-8 text-[12px]"
                    />
                    <Input
                        type="number"
                        placeholder="Max"
                        value={filters.maxPrice}
                        onChange={(e) => setFilters({ ...filters, maxPrice: e.target.value })}
                        className="h-8 text-[12px]"
                    />
                </div>
                <Button
                    size="sm"
                    onClick={() => applyFilters(filters)}
                    className="mt-2 h-8 w-full text-[12px]"
                    style={{ background: 'var(--accent-soft)', color: 'var(--accent)' }}
                >
                    Uygula
                </Button>
            </FilterSection>

            <FilterSection title="Stok durumu">
                <CheckRow
                    label="Stokta var"
                    checked={filters.inStock}
                    onChange={(next) => applyFilters({ ...filters, inStock: next })}
                />
            </FilterSection>

            <FilterSection title="Satıcı şehri" defaultOpen={false}>
                <select
                    value={filters.city}
                    onChange={(e) => applyFilters({ ...filters, city: e.target.value })}
                    className="h-8 text-[12px]"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                        borderRadius: 'var(--radius)',
                        padding: '0 8px',
                    }}
                >
                    <option value="">Tüm şehirler</option>
                    {['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Adana'].map((c) => (
                        <option key={c} value={c}>
                            {c}
                        </option>
                    ))}
                </select>
            </FilterSection>

            <FilterSection title="Min. satıcı puanı" defaultOpen={false}>
                {[5, 4, 3, 2, 1].map((rating) => (
                    <label key={rating} className="flex cursor-pointer items-center gap-2 text-[13px]">
                        <input
                            type="radio"
                            name="rating"
                            checked={filters.minRating === rating}
                            onChange={() => applyFilters({ ...filters, minRating: rating })}
                        />
                        <span style={{ color: 'var(--warning)' }}>
                            {'★'.repeat(rating)}
                            <span style={{ color: 'var(--border-strong)' }}>
                                {'★'.repeat(5 - rating)}
                            </span>
                        </span>
                        <span style={{ color: 'var(--fg-muted)' }}>ve üzeri</span>
                    </label>
                ))}
            </FilterSection>
        </div>
    );

    return (
        <div className="max-w-[1440px] mx-auto px-6 pt-5">
            {/* Breadcrumb */}
            <div className="mb-2 text-[12px]" style={{ color: 'var(--fg-muted)' }}>
                <Link href="/market" className="hover:underline" style={{ color: 'var(--fg-muted)' }}>
                    Anasayfa
                </Link>
                <span> · </span>
                <Link
                    href="/market/products"
                    className="hover:underline"
                    style={{ color: 'var(--fg-muted)' }}
                >
                    Tüm kategoriler
                </Link>
                {(breadcrumb.length > 0 ? breadcrumb : []).map((item, index) => (
                    <span key={item.id}>
                        <span> · </span>
                        {index === breadcrumb.length - 1 ? (
                            <span style={{ color: 'var(--fg)' }}>{item.name}</span>
                        ) : (
                            <Link
                                href={`/market/category/${item.full_slug || item.slug}`}
                                className="hover:underline"
                                style={{ color: 'var(--fg-muted)' }}
                            >
                                {item.name}
                            </Link>
                        )}
                    </span>
                ))}
                {breadcrumb.length === 0 && (
                    <>
                        <span> · </span>
                        <span style={{ color: 'var(--fg)' }}>{headingName}</span>
                    </>
                )}
            </div>

            {/* Heading + meta */}
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-[28px] font-semibold leading-tight">{headingName}</h1>
                    <p className="mt-1 text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {totalProducts.toLocaleString('tr-TR')}
                        </span>{' '}
                        ürün ·{' '}
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {totalOffers.toLocaleString('tr-TR')}
                        </span>{' '}
                        aktif teklif ·{' '}
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {totalSellers.toLocaleString('tr-TR')}
                        </span>{' '}
                        satıcı
                    </p>
                </div>

                <Sheet open={mobileFilterOpen} onOpenChange={setMobileFilterOpen}>
                    <SheetTrigger asChild>
                        <Button variant="outline" className="lg:hidden gap-2 self-start">
                            <Filter className="w-4 h-4" /> Filtrele
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="bottom" className="h-[85vh]">
                        <SheetHeader>
                            <SheetTitle>Filtreler</SheetTitle>
                        </SheetHeader>
                        <div className="mt-4 overflow-y-auto">
                            <FilterContent />
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            {/* Active chips */}
            {hasActiveFilters && (
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    {filters.brand && (
                        <span className="chip chip-accent" style={{ paddingRight: 4 }}>
                            {filters.brand}
                            <button
                                type="button"
                                onClick={() => applyFilters({ ...filters, brand: '' })}
                                aria-label="Markayı kaldır"
                                style={{ color: 'var(--accent)' }}
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {(filters.minPrice || filters.maxPrice) && (
                        <span className="chip chip-accent" style={{ paddingRight: 4 }}>
                            {filters.minPrice || '0'} ₺ - {filters.maxPrice || '∞'} ₺
                            <button
                                type="button"
                                onClick={() => applyFilters({ ...filters, minPrice: '', maxPrice: '' })}
                                aria-label="Fiyat filtresini kaldır"
                                style={{ color: 'var(--accent)' }}
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {filters.inStock && (
                        <span className="chip chip-accent" style={{ paddingRight: 4 }}>
                            Stokta var
                            <button
                                type="button"
                                onClick={() => applyFilters({ ...filters, inStock: false })}
                                aria-label="Stok filtresini kaldır"
                                style={{ color: 'var(--accent)' }}
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {filters.city && (
                        <span className="chip chip-accent" style={{ paddingRight: 4 }}>
                            {filters.city}
                            <button
                                type="button"
                                onClick={() => applyFilters({ ...filters, city: '' })}
                                aria-label="Şehri kaldır"
                                style={{ color: 'var(--accent)' }}
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    {filters.minRating > 0 && (
                        <span className="chip chip-accent" style={{ paddingRight: 4 }}>
                            {filters.minRating}+ yıldız
                            <button
                                type="button"
                                onClick={() => applyFilters({ ...filters, minRating: 0 })}
                                aria-label="Puan filtresini kaldır"
                                style={{ color: 'var(--accent)' }}
                            >
                                <X className="w-3 h-3" />
                            </button>
                        </span>
                    )}
                    <button
                        type="button"
                        onClick={clearFilters}
                        className="text-[12px] font-medium"
                        style={{ color: 'var(--danger)' }}
                    >
                        Tümünü temizle
                    </button>
                </div>
            )}

            {/* Layout */}
            <div className="mt-4 grid gap-6 lg:grid-cols-[260px_1fr]">
                <aside
                    className="hidden lg:block self-start sticky top-3 rounded-[10px]"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                    }}
                >
                    <FilterContent />
                </aside>

                <div>
                    {/* Toolbar */}
                    <div
                        className="mb-4 flex items-center justify-between gap-3 rounded-[10px] px-4 py-2.5"
                        style={{
                            background: 'var(--bg-elevated)',
                            border: '1px solid var(--border)',
                        }}
                    >
                        <div className="text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                            <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                                {totalProducts.toLocaleString('tr-TR')}
                            </span>{' '}
                            sonuç · sayfa{' '}
                            <span className="mono" style={{ color: 'var(--fg)' }}>
                                {page} / {lastPage}
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-[12px]" style={{ color: 'var(--fg-soft)' }}>
                                Sırala:
                            </span>
                            <select
                                value={filters.sortBy}
                                onChange={(e) => applyFilters({ ...filters, sortBy: e.target.value })}
                                className="h-7 rounded-[6px] px-2 text-[12px]"
                                style={{
                                    background: 'var(--bg-elevated)',
                                    border: '1px solid var(--border)',
                                    color: 'var(--fg)',
                                }}
                            >
                                {SORT_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                            <div className="h-5 w-px" style={{ background: 'var(--border)' }} />
                            <div className="flex rounded-[6px]" style={{ border: '1px solid var(--border)' }}>
                                <button
                                    type="button"
                                    onClick={() => setView('grid')}
                                    className="px-2.5 py-1.5"
                                    style={{
                                        background: view === 'grid' ? 'var(--accent-soft)' : 'transparent',
                                        color: view === 'grid' ? 'var(--accent)' : 'var(--fg-muted)',
                                        borderRadius: '6px 0 0 6px',
                                    }}
                                    aria-label="Grid görünümü"
                                >
                                    <Grid3X3 className="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setView('list')}
                                    className="px-2.5 py-1.5"
                                    style={{
                                        background: view === 'list' ? 'var(--accent-soft)' : 'transparent',
                                        color: view === 'list' ? 'var(--accent)' : 'var(--fg-muted)',
                                        borderRadius: '0 6px 6px 0',
                                    }}
                                    aria-label="List görünümü"
                                >
                                    <List className="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {isLoading ? (
                        <div
                            className={
                                view === 'grid'
                                    ? 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5'
                                    : 'flex flex-col gap-2.5'
                            }
                        >
                            {Array.from({ length: 12 }).map((_, i) => (
                                <Skeleton
                                    key={i}
                                    className={
                                        view === 'grid' ? 'h-[260px] rounded-[10px]' : 'h-[120px] rounded-[10px]'
                                    }
                                />
                            ))}
                        </div>
                    ) : products.length === 0 ? (
                        <div
                            className="rounded-[10px] py-16 text-center"
                            style={{
                                background: 'var(--bg-elevated)',
                                border: '1px solid var(--border)',
                            }}
                        >
                            <h3 className="text-[16px] font-semibold">Ürün bulunamadı</h3>
                            <p className="mt-2 text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                                {hasActiveFilters
                                    ? 'Filtreleri değiştirerek tekrar deneyebilirsiniz.'
                                    : 'Bu kategoride henüz ürün yok.'}
                            </p>
                            {hasActiveFilters && (
                                <Button onClick={clearFilters} className="mt-4" variant="outline">
                                    <X className="w-4 h-4 mr-2" /> Filtreleri Temizle
                                </Button>
                            )}
                        </div>
                    ) : view === 'grid' ? (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
                            {products.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col gap-2.5">
                            {products.map((product) => (
                                <ProductCard key={product.id} product={product} layout="list" />
                            ))}
                        </div>
                    )}

                    {!isLoading && products.length > 0 && lastPage > 1 && (
                        <Pagination
                            currentPage={page}
                            lastPage={lastPage}
                            onChange={(p) => {
                                setPage(p);
                                loadProducts(p);
                                if (typeof window !== 'undefined') {
                                    window.scrollTo({ top: 0, behavior: 'smooth' });
                                }
                            }}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}

export function CategoryClient() {
    return (
        <Suspense
            fallback={
                <div className="max-w-[1440px] mx-auto px-6 pt-5">
                    <Skeleton className="h-6 w-64 mb-4" />
                    <Skeleton className="h-10 w-80 mb-3" />
                    <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
                        <Skeleton className="h-96 rounded-[10px] hidden lg:block" />
                        <div>
                            <Skeleton className="h-12 mb-4 rounded-[10px]" />
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
                                {Array.from({ length: 8 }).map((_, i) => (
                                    <Skeleton key={i} className="h-[260px] rounded-[10px]" />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            }
        >
            <MarketCategoryContent />
        </Suspense>
    );
}
