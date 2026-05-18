'use client';

import { useEffect, useState, useCallback, useRef, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { productsApi, Product } from '@/lib/api';
import { ProductCard } from '@/components/market/ProductCard';
import { GridProductCard } from '@/components/market/GridProductCard';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import {
    ArrowLeft,
    ArrowRight,
    Search as SearchIcon,
    Sliders,
    SlidersHorizontal,
    X,
    Grid3X3,
    List,
} from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { addRecentSearch } from '@/lib/search-history';

interface Filters {
    brand: string;
    minPrice: string;
    maxPrice: string;
    sortBy: string;
}

const PER_PAGE = 24;
const RELATED_SEARCH_SUGGESTIONS = [
    "kurşun kalem 12'li",
    'Faber-Castell kurşun',
    'kurşun kalem HB',
    'kurşun kalem set',
    'tükenmez kalem',
    'mekanik kalem',
];

function MobileFilterContent({
    filters,
    setFilters,
    availableBrands,
    onApply,
}: {
    filters: Filters;
    setFilters: (f: Filters) => void;
    availableBrands: string[];
    onApply: () => void;
}) {
    return (
        <div className="px-4 py-3">
            {availableBrands.length > 0 && (
                <div className="py-3" style={{ borderTop: '1px solid var(--border)' }}>
                    <div className="text-[12px] font-semibold mb-2" style={{ color: 'var(--fg-muted)' }}>
                        Marka
                    </div>
                    <div className="flex flex-col gap-1.5">
                        {availableBrands.slice(0, 10).map((b) => (
                            <label key={b} className="flex items-center gap-2 text-[13px] cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={filters.brand === b}
                                    onChange={(e) =>
                                        setFilters({ ...filters, brand: e.target.checked ? b : '' })
                                    }
                                />
                                <span className="flex-1">{b}</span>
                            </label>
                        ))}
                    </div>
                </div>
            )}

            <div className="py-3" style={{ borderTop: '1px solid var(--border)' }}>
                <div className="text-[12px] font-semibold mb-2" style={{ color: 'var(--fg-muted)' }}>
                    Fiyat aralığı
                </div>
                <div className="flex gap-2">
                    <Input
                        type="number"
                        placeholder="Min"
                        value={filters.minPrice}
                        onChange={(e) => setFilters({ ...filters, minPrice: e.target.value })}
                        className="h-9 text-[13px]"
                    />
                    <Input
                        type="number"
                        placeholder="Max"
                        value={filters.maxPrice}
                        onChange={(e) => setFilters({ ...filters, maxPrice: e.target.value })}
                        className="h-9 text-[13px]"
                    />
                </div>
            </div>

            <div className="flex gap-2 mt-4">
                <Button
                    onClick={onApply}
                    className="flex-1"
                    style={{ background: 'var(--accent)', color: 'var(--accent-fg)' }}
                >
                    Uygula
                </Button>
                {(filters.brand || filters.minPrice || filters.maxPrice) && (
                    <Button
                        variant="outline"
                        onClick={() => {
                            setFilters({ brand: '', minPrice: '', maxPrice: '', sortBy: filters.sortBy });
                            onApply();
                        }}
                    >
                        Temizle
                    </Button>
                )}
            </div>
        </div>
    );
}

function SearchContent() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const initialQuery = searchParams.get('q') || '';

    const [query, setQuery] = useState(initialQuery);
    const [activeQuery, setActiveQuery] = useState(initialQuery);
    const [products, setProducts] = useState<Product[]>([]);
    const [availableBrands, setAvailableBrands] = useState<string[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [totalProducts, setTotalProducts] = useState(0);
    const [searchTime, setSearchTime] = useState(0.18);
    const filtersRef = useRef(0);
    const searchSavedRef = useRef(false);

    const [filters, setFilters] = useState<Filters>({
        brand: '',
        minPrice: '',
        maxPrice: '',
        sortBy: 'offers_count',
    });
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const [view, setView] = useState<'grid' | 'list'>('grid');

    useEffect(() => {
        if (initialQuery && !searchSavedRef.current) {
            addRecentSearch(initialQuery);
            searchSavedRef.current = true;
        }
        setQuery(initialQuery);
        setActiveQuery(initialQuery);
    }, [initialQuery]);

    const loadProducts = useCallback(
        async (nextPage: number) => {
            if (!activeQuery) {
                setProducts([]);
                setIsLoading(false);
                return;
            }
            const requestId = ++filtersRef.current;
            const start = performance.now();
            setIsLoading(true);
            try {
                const response = await productsApi.getAll({
                    page: nextPage,
                    per_page: PER_PAGE,
                    search: activeQuery,
                    brand: filters.brand || undefined,
                    min_price: filters.minPrice || undefined,
                    max_price: filters.maxPrice || undefined,
                    sort_by: filters.sortBy,
                });
                if (filtersRef.current !== requestId) return;
                if (response.data) {
                    setProducts(response.data.products);
                    setTotalProducts(response.data.pagination?.total || 0);
                    setLastPage(response.data.pagination?.last_page || 1);
                    if (response.data.filters?.brands) {
                        setAvailableBrands(response.data.filters.brands);
                    }
                }
                const elapsed = (performance.now() - start) / 1000;
                setSearchTime(Math.max(0.05, Math.min(2, elapsed)));
            } catch (e) {
                console.error('Search failed:', e);
            } finally {
                if (filtersRef.current === requestId) {
                    setIsLoading(false);
                }
            }
        },
        [activeQuery, filters],
    );

    useEffect(() => {
        setPage(1);
        loadProducts(1);
    }, [loadProducts]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = query.trim();
        if (!trimmed) return;
        setActiveQuery(trimmed);
        const params = new URLSearchParams();
        params.set('q', trimmed);
        router.push(`/market/search?${params.toString()}`, { scroll: false });
    };

    const applySuggestion = (s: string) => {
        setQuery(s);
        setActiveQuery(s);
        const params = new URLSearchParams();
        params.set('q', s);
        router.push(`/market/search?${params.toString()}`, { scroll: false });
    };

    const totalOffers = products.reduce(
        (acc, p) => acc + (typeof p.offers_count === 'number' ? p.offers_count : 0),
        0,
    );

    // Pivot categories — derived from results (top categories among products)
    const pivotCategories = (() => {
        const counts = new Map<string, number>();
        for (const p of products) {
            const name = p.category?.name;
            if (name) counts.set(name, (counts.get(name) || 0) + 1);
        }
        const arr = Array.from(counts.entries()).map(([name, count]) => ({
            name,
            count,
        }));
        arr.sort((a, b) => b.count - a.count);
        return arr.slice(0, 5);
    })();

    if (!activeQuery) {
        return (
            <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-8 sm:py-12">
                <div
                    className="rounded-[10px] py-16 text-center"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                    }}
                >
                    <SearchIcon
                        className="w-16 h-16 mx-auto mb-3"
                        style={{ color: 'var(--fg-soft)' }}
                    />
                    <h2 className="text-[18px] font-semibold mb-1">Arama yapın</h2>
                    <p className="text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                        Ürün aramak için yukarıdaki arama kutusunu kullanın.
                    </p>
                    <Link href="/market">
                        <Button className="mt-4" variant="outline">
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Pazaryerine Dön
                        </Button>
                    </Link>
                </div>
            </div>
        );
    }

    return (
            <div className="max-w-[1440px] mx-auto px-4 sm:px-6">
            {/* Search header bar */}
            <div
                className="border-b py-5"
                style={{ borderColor: 'var(--border)' }}
            >
                <form onSubmit={handleSearch} className="relative max-w-2xl">
                    <SearchIcon
                        className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                        style={{ color: 'var(--fg-soft)' }}
                    />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Ürün, marka veya SKU ile ara"
                        className="pl-10 pr-24 h-11"
                    />
                    <Button
                        type="submit"
                        className="absolute right-1 top-1 h-9"
                        style={{
                            background: 'var(--accent)',
                            color: 'var(--accent-fg)',
                        }}
                    >
                        Ara
                    </Button>
                </form>

                <div className="mt-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <div
                            className="text-[12px]"
                            style={{ color: 'var(--fg-muted)' }}
                        >
                            Arama sonuçları
                        </div>
                        <h1 className="text-[22px] sm:text-[24px] font-semibold leading-tight">
                            "
                            <span style={{ color: 'var(--accent)' }}>{activeQuery}</span>"
                            için sonuçlar
                        </h1>
                    </div>
                    <div
                        className="text-[13px]"
                        style={{ color: 'var(--fg-muted)' }}
                    >
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {totalProducts.toLocaleString('tr-TR')}
                        </span>{' '}
                        ürün ·{' '}
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {totalOffers.toLocaleString('tr-TR')}
                        </span>{' '}
                        teklif ·{' '}
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {searchTime.toFixed(2)}
                        </span>{' '}
                        sn
                    </div>
                </div>

                {/* Related searches */}
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <span
                        className="text-[12px] py-1.5"
                        style={{ color: 'var(--fg-soft)' }}
                    >
                        İlgili aramalar:
                    </span>
                    {RELATED_SEARCH_SUGGESTIONS.map((s) => (
                        <button
                            type="button"
                            key={s}
                            onClick={() => applySuggestion(s)}
                            className="chip"
                            style={{ padding: '6px 12px', fontSize: 12 }}
                        >
                            <SearchIcon className="w-3 h-3" /> {s}
                        </button>
                    ))}
                </div>
            </div>

            {/* Categories pivot */}
            {pivotCategories.length > 0 && (
                <div className="pt-5">
                    <div className="eyebrow mb-2.5">Kategorilere göre filtrele</div>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
                        {pivotCategories.map((c, i) => (
                            <a
                                key={c.name}
                                href={`/market/products?search=${encodeURIComponent(activeQuery)}`}
                                className="rounded-[6px] flex justify-between items-center"
                                style={{
                                    padding: '12px 14px',
                                    background:
                                        i === 0
                                            ? 'var(--accent-soft)'
                                            : 'var(--bg-elevated)',
                                    border:
                                        i === 0
                                            ? '1px solid var(--accent)'
                                            : '1px solid var(--border)',
                                }}
                            >
                                <span
                                    className="text-[13px] font-medium"
                                    style={{
                                        color:
                                            i === 0 ? 'var(--accent)' : 'var(--fg)',
                                    }}
                                >
                                    {c.name}
                                </span>
                                <span
                                    className="mono text-[11px]"
                                    style={{
                                        color:
                                            i === 0
                                                ? 'var(--accent)'
                                                : 'var(--fg-soft)',
                                    }}
                                >
                                    {c.count}
                                </span>
                            </a>
                        ))}
                    </div>
                </div>
            )}

            {/* Main grid w/ compact left filters + right results */}
            <div className="pt-5 pb-12 grid gap-6 lg:grid-cols-[240px_1fr]">
                {/* Mobile filter trigger */}
                <div className="lg:hidden mb-2">
                    <Sheet open={mobileFilterOpen} onOpenChange={setMobileFilterOpen}>
                        <SheetTrigger asChild>
                            <Button variant="outline" className="gap-2">
                                <SlidersHorizontal className="w-4 h-4" />
                                Filtreler
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-[85vw] max-w-[360px] p-0">
                            <SheetHeader className="px-4 py-3 border-b" style={{ borderColor: 'var(--border)' }}>
                                <SheetTitle className="text-[14px] inline-flex items-center gap-1.5">
                                    <Sliders className="w-3.5 h-3.5" /> Filtreler
                                </SheetTitle>
                            </SheetHeader>
                            <div className="overflow-y-auto pb-[env(safe-area-inset-bottom)]">
                                <MobileFilterContent
                                    filters={filters}
                                    setFilters={setFilters}
                                    availableBrands={availableBrands}
                                    onApply={() => setMobileFilterOpen(false)}
                                />
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                {/* Compact sidebar */}
                <aside
                    className="hidden lg:block self-start rounded-[10px] p-4"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                    }}
                >
                    <div
                        className="text-[14px] font-semibold mb-3 inline-flex items-center gap-1.5"
                        style={{ color: 'var(--fg)' }}
                    >
                        <Sliders className="w-3.5 h-3.5" /> Daralt
                    </div>

                    {/* Brand */}
                    {availableBrands.length > 0 && (
                        <div
                            className="py-3"
                            style={{ borderTop: '1px solid var(--border)' }}
                        >
                            <div
                                className="text-[12px] font-semibold mb-2"
                                style={{ color: 'var(--fg-muted)' }}
                            >
                                Marka
                            </div>
                            <div className="flex flex-col gap-1.5">
                                {availableBrands.slice(0, 10).map((b) => (
                                    <label
                                        key={b}
                                        className="flex items-center gap-2 text-[13px] cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={filters.brand === b}
                                            onChange={(e) =>
                                                setFilters({
                                                    ...filters,
                                                    brand: e.target.checked ? b : '',
                                                })
                                            }
                                        />
                                        <span className="flex-1">{b}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Price */}
                    <div
                        className="py-3"
                        style={{ borderTop: '1px solid var(--border)' }}
                    >
                        <div
                            className="text-[12px] font-semibold mb-2"
                            style={{ color: 'var(--fg-muted)' }}
                        >
                            Fiyat aralığı
                        </div>
                        <div className="flex gap-2">
                            <Input
                                type="number"
                                placeholder="Min"
                                value={filters.minPrice}
                                onChange={(e) =>
                                    setFilters({ ...filters, minPrice: e.target.value })
                                }
                                className="h-8 text-[12px]"
                            />
                            <Input
                                type="number"
                                placeholder="Max"
                                value={filters.maxPrice}
                                onChange={(e) =>
                                    setFilters({ ...filters, maxPrice: e.target.value })
                                }
                                className="h-8 text-[12px]"
                            />
                        </div>
                    </div>

                    {(filters.brand || filters.minPrice || filters.maxPrice) && (
                        <button
                            type="button"
                            onClick={() =>
                                setFilters({
                                    brand: '',
                                    minPrice: '',
                                    maxPrice: '',
                                    sortBy: filters.sortBy,
                                })
                            }
                            className="mt-3 text-[12px] font-medium"
                            style={{ color: 'var(--danger)' }}
                        >
                            <X className="w-3 h-3 inline mr-1" />
                            Filtreleri Temizle
                        </button>
                    )}
                </aside>

                {/* Results */}
                <div>
                    {/* Toolbar */}
                    <div
                        className="mb-4 flex flex-wrap items-center justify-between gap-2 sm:gap-3 rounded-[10px] px-4 py-2.5"
                        style={{
                            background: 'var(--bg-elevated)',
                            border: '1px solid var(--border)',
                        }}
                    >
                        <div className="text-sm sm:text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                            <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                                {totalProducts.toLocaleString('tr-TR')}
                            </span>{' '}
                            sonuç · sayfa{' '}
                            <span className="mono" style={{ color: 'var(--fg)' }}>
                                {page} / {lastPage}
                            </span>
                        </div>
                        <div className="flex items-center gap-2 w-full sm:w-auto">
                            <span className="text-[12px]" style={{ color: 'var(--fg-soft)' }}>
                                Sırala:
                            </span>
                            <select
                                value={filters.sortBy}
                                onChange={(e) =>
                                    setFilters({ ...filters, sortBy: e.target.value })
                                }
                                className="h-7 rounded-[6px] px-2 text-[12px] flex-1 sm:flex-initial"
                                style={{
                                    background: 'var(--bg-elevated)',
                                    border: '1px solid var(--border)',
                                    color: 'var(--fg)',
                                }}
                            >
                                <option value="offers_count">İlgililik</option>
                                <option value="price_asc">Fiyat (Artan)</option>
                                <option value="price_desc">Fiyat (Azalan)</option>
                                <option value="name">İsim (A-Z)</option>
                                <option value="newest">En Yeniler</option>
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

                    {/* Product list */}
                    {isLoading ? (
                        <div
                            className={
                                view === 'grid'
                                    ? 'grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5'
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
                            <h3 className="text-[16px] font-semibold">Sonuç bulunamadı</h3>
                            <p
                                className="mt-2 text-[13px]"
                                style={{ color: 'var(--fg-muted)' }}
                            >
                                "{activeQuery}" ile eşleşen ürün bulunamadı.
                            </p>
                            <Link href="/market">
                                <Button className="mt-4" variant="outline">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Pazaryerine Dön
                                </Button>
                            </Link>
                        </div>
                    ) : view === 'grid' ? (
                        <div className="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
                            {products.map((p) => (
                                <GridProductCard key={p.id} product={p} />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col gap-2.5">
                            {products.map((p) => (
                                <ProductCard key={p.id} product={p} layout="list" />
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {!isLoading && products.length > 0 && lastPage > 1 && (
                        <div className="mt-7 flex justify-center gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page <= 1}
                                onClick={() => {
                                    const next = page - 1;
                                    setPage(next);
                                    loadProducts(next);
                                }}
                            >
                                Önceki
                            </Button>
                            {Array.from({ length: Math.min(5, lastPage) }).map((_, idx) => {
                                const p = idx + 1;
                                return (
                                    <Button
                                        key={p}
                                        size="sm"
                                        variant={p === page ? 'default' : 'outline'}
                                        style={
                                            p === page
                                                ? {
                                                      background: 'var(--accent)',
                                                      color: 'var(--accent-fg)',
                                                      minWidth: 44,
                                                  }
                                                : { minWidth: 44 }
                                        }
                                        onClick={() => {
                                            setPage(p);
                                            loadProducts(p);
                                        }}
                                    >
                                        {p}
                                    </Button>
                                );
                            })}
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page >= lastPage}
                                onClick={() => {
                                    const next = page + 1;
                                    setPage(next);
                                    loadProducts(next);
                                }}
                            >
                                Sonraki <ArrowRight className="w-3.5 h-3.5 ml-1" />
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export function SearchClient() {
    return (
        <Suspense
            fallback={
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-4 sm:pt-5">
                    <Skeleton className="h-8 w-64 mb-4" />
                    <Skeleton className="h-32 w-full mb-4 rounded-[10px]" />
                    <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
                        <Skeleton className="h-72 hidden lg:block rounded-[10px]" />
                        <div className="flex flex-col gap-2.5">
                            {Array.from({ length: 6 }).map((_, i) => (
                                <Skeleton key={i} className="h-[140px] rounded-[10px]" />
                            ))}
                        </div>
                    </div>
                </div>
            }
        >
            <SearchContent />
        </Suspense>
    );
}
