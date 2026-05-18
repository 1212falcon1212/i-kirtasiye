'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import {
    Search, User as UserIcon, ChevronDown, ChevronRight, Truck,
    ShieldCheck, HelpCircle, LogOut, Package, LayoutDashboard,
    Loader2, ArrowRight, X, Menu, Tag, ScanLine,
} from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { Logo } from '@/components/Logo';
import { MiniCart } from '@/components/cart/MiniCart';
import { NotificationDropdown } from '@/components/market/NotificationDropdown';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { BarcodeScanner } from '@/components/mobile/BarcodeScanner';
import { api, productsApi, type Product } from '@/lib/api';
import { useDebounce } from '@/hooks/use-debounce';

interface CategoryNode {
    id: number;
    name: string;
    slug: string;
    full_slug?: string;
    products_count?: number;
    children?: CategoryNode[];
}

interface CategoriesResponse {
    status?: string;
    data?: CategoryNode[];
}

interface MarketHeaderProps {
    activeNav?: string;
}

const formatTL = (n?: number | string | null): string => {
    if (n === null || n === undefined) return '';
    const num = typeof n === 'string' ? parseFloat(n) : n;
    if (!Number.isFinite(num)) return '';
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num) + ' ₺';
};

export function MarketHeader({ activeNav }: MarketHeaderProps) {
    const router = useRouter();
    const { user, logout } = useAuth();

    const [search, setSearch] = useState('');
    const [searchOpen, setSearchOpen] = useState(false);
    const [searchResults, setSearchResults] = useState<Product[]>([]);
    const [searchLoading, setSearchLoading] = useState(false);
    const [searchTotal, setSearchTotal] = useState(0);
    const [accountOpen, setAccountOpen] = useState(false);
    const [categories, setCategories] = useState<CategoryNode[]>([]);
    const [hoveredCat, setHoveredCat] = useState<number | null>(null);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [mobileCatExpanded, setMobileCatExpanded] = useState<number | null>(null);
    const [scannerOpen, setScannerOpen] = useState(false);
    const accountRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLDivElement>(null);
    const debouncedSearch = useDebounce(search.trim(), 250);
    const requestId = useRef(0);

    useEffect(() => {
        const onClick = (e: MouseEvent) => {
            const t = e.target as Node;
            if (accountRef.current && !accountRef.current.contains(t)) setAccountOpen(false);
            if (searchRef.current && !searchRef.current.contains(t)) setSearchOpen(false);
        };
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    useEffect(() => {
        let active = true;
        api.get<CategoriesResponse | { categories: CategoryNode[] } | CategoryNode[]>('/categories')
            .then((res) => {
                if (!active) return;
                const raw = res.data as unknown;
                let list: CategoryNode[] = [];
                if (Array.isArray(raw)) {
                    list = raw as CategoryNode[];
                } else if (raw && typeof raw === 'object') {
                    const obj = raw as Record<string, unknown>;
                    if (Array.isArray(obj.data)) list = obj.data as CategoryNode[];
                    else if (Array.isArray(obj.categories)) list = obj.categories as CategoryNode[];
                }
                setCategories(list);
            })
            .catch(() => { });
        return () => {
            active = false;
        };
    }, []);

    // Live search suggestions via Meilisearch
    useEffect(() => {
        if (debouncedSearch.length < 2) {
            setSearchResults([]);
            setSearchTotal(0);
            setSearchLoading(false);
            return;
        }
        const id = ++requestId.current;
        setSearchLoading(true);
        productsApi
            .search(debouncedSearch, 1)
            .then((res) => {
                if (requestId.current !== id) return;
                if (res.data?.products) {
                    setSearchResults(res.data.products.slice(0, 6));
                    setSearchTotal(res.data.pagination?.total ?? res.data.products.length);
                }
            })
            .catch(() => {
                if (requestId.current !== id) return;
                setSearchResults([]);
                setSearchTotal(0);
            })
            .finally(() => {
                if (requestId.current === id) setSearchLoading(false);
            });
    }, [debouncedSearch]);

    const onSearch = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            const q = search.trim();
            if (q) {
                setSearchOpen(false);
                router.push(`/market/search?q=${encodeURIComponent(q)}`);
            }
        },
        [router, search],
    );

    const goToProduct = useCallback(
        (p: Product) => {
            setSearchOpen(false);
            setSearch('');
            const slug = p.slug ?? p.id;
            router.push(`/market/product/${slug}`);
        },
        [router],
    );

    const handleBarcodeScan = useCallback(
        (barcode: string) => {
            const value = barcode.trim();
            setScannerOpen(false);
            if (!value) return;
            setSearch(value);
            setSearchOpen(false);
            setMobileMenuOpen(false);
            router.push(`/market/search?q=${encodeURIComponent(value)}`);
        },
        [router],
    );

    const navItems = categories.slice(0, 8);

    return (
        <header
            className="w-full sticky top-0 z-40"
            style={{ background: 'var(--bg-elevated)' }}
        >
            {/* Utility bar */}
            <div
                style={{
                    background: 'var(--accent-hover)',
                    color: 'var(--accent-on-muted)',
                    fontSize: 12,
                }}
            >
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-1.5 flex justify-between items-center gap-4">
                    <div className="hidden sm:flex gap-4 items-center">
                        <span className="inline-flex items-center gap-1.5">
                            <Truck className="w-3.5 h-3.5" /> 1.500₺ üzeri kargo bedava
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <ShieldCheck className="w-3.5 h-3.5" /> Güvenli ödeme altyapısı
                        </span>
                    </div>
                    <div className="flex gap-3 items-center ml-auto" style={{ color: 'var(--accent-on)' }}>
                        <Link href="/yardim" className="opacity-90 hover:opacity-100 inline-flex items-center gap-1 transition-opacity">
                            <HelpCircle className="w-3.5 h-3.5" /> Yardım
                        </Link>
                        <span className="opacity-50">·</span>
                        <Link href="/register" className="opacity-90 hover:opacity-100 transition-opacity">
                            Tedarikçi ol
                        </Link>
                    </div>
                </div>
            </div>

            {/* TopBar: logo, search, tools */}
            <div className="max-w-[1440px] mx-auto px-3 sm:px-6 py-2.5 sm:py-3.5">
                {/* Mobile: 3-column layout */}
                <div className="flex items-center justify-between lg:hidden">
                    {/* Hamburger */}
                    <button
                        type="button"
                        onClick={() => setMobileMenuOpen(true)}
                        aria-label="Menüyü aç"
                        className="inline-flex items-center justify-center rounded-lg transition-colors"
                        style={{
                            width: 40,
                            height: 40,
                            background: 'var(--bg-muted)',
                            color: 'var(--fg)',
                        }}
                    >
                        <Menu className="w-5 h-5" />
                    </button>

                    {/* Logo centered */}
                    <Link href="/market" className="flex items-center">
                        <Image
                            src="/logo.webp"
                            alt="i-kırtasiye logo"
                            width={200}
                            height={200}
                            className="h-24 w-auto"
                            priority
                        />
                    </Link>

                    {/* Avatar + Cart */}
                    <div className="flex items-center gap-2">
                        {user ? (
                            <button
                                type="button"
                                onClick={() => setAccountOpen((v) => !v)}
                                className="inline-flex items-center justify-center rounded-full"
                                style={{
                                    width: 36,
                                    height: 36,
                                    background: 'var(--accent)',
                                    color: 'white',
                                    fontWeight: 700,
                                    fontSize: 14,
                                }}
                            >
                                {(user.nickname || user.business_name || user.email || 'U').charAt(0).toUpperCase()}
                            </button>
                        ) : (
                            <Link
                                href="/login"
                                className="inline-flex items-center justify-center rounded-full"
                                style={{
                                    width: 36,
                                    height: 36,
                                    background: 'var(--bg-muted)',
                                    color: 'var(--fg)',
                                }}
                            >
                                <UserIcon className="w-4 h-4" />
                            </Link>
                        )}
                        <MiniCart />
                    </div>
                </div>

                {/* Desktop: original layout */}
                <div className="hidden lg:flex items-center gap-4">
                    <Logo size="md" href="/market" />

                    <div ref={searchRef} className="flex-1 relative min-w-0">
                        <form onSubmit={onSearch} className="flex items-stretch gap-2">
                            <div className="relative flex-1 min-w-0">
                                <Search
                                    className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                                    style={{ color: 'var(--fg-soft)' }}
                                />
                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => {
                                        setSearch(e.target.value);
                                        setSearchOpen(true);
                                    }}
                                    onFocus={() => search.length >= 2 && setSearchOpen(true)}
                                    placeholder="Ürün, marka veya barkod ara…"
                                    className="input w-full"
                                    style={{
                                        paddingLeft: 36,
                                        paddingRight: search ? 72 : 44,
                                        height: 40,
                                        fontSize: 14,
                                        background: 'var(--bg-elevated)',
                                        color: 'var(--fg)',
                                        border: '1px solid var(--border)',
                                    }}
                                    autoComplete="off"
                                />
                                {search && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('');
                                            setSearchOpen(false);
                                        }}
                                        className="absolute top-1/2 -translate-y-1/2 p-1"
                                        style={{ right: 40, color: 'var(--fg-soft)' }}
                                        aria-label="Aramayı temizle"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => setScannerOpen(true)}
                                    className="absolute top-1/2 -translate-y-1/2 inline-flex items-center justify-center transition-colors"
                                    style={{
                                        right: 6,
                                        height: 28,
                                        width: 28,
                                        color: 'var(--fg-muted)',
                                        borderRadius: 6,
                                    }}
                                    aria-label="Barkod tara"
                                    title="Barkod tara"
                                >
                                    <ScanLine className="w-4 h-4" />
                                </button>
                            </div>
                            <button
                                type="submit"
                                className="btn btn-primary flex-shrink-0"
                                style={{ height: 40, width: 40, padding: 0 }}
                                aria-label="Ara"
                                title="Ara"
                            >
                                <Search className="w-4 h-4" />
                            </button>
                        </form>

                        {/* Search dropdown */}
                        {searchOpen && search.trim().length >= 2 && (
                            <div
                                className="absolute left-0 right-0 mt-2 z-50 overflow-hidden"
                                style={{
                                    background: 'var(--bg-elevated)',
                                    border: '1px solid var(--border)',
                                    borderRadius: 'var(--radius-lg)',
                                    boxShadow: 'var(--shadow-lg)',
                                    maxHeight: '70vh',
                                    overflowY: 'auto',
                                }}
                            >
                                {searchLoading && searchResults.length === 0 ? (
                                    <div
                                        className="flex items-center justify-center gap-2 py-6 text-[13px]"
                                        style={{ color: 'var(--fg-muted)' }}
                                    >
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                        Aranıyor…
                                    </div>
                                ) : searchResults.length === 0 ? (
                                    <div className="py-6 text-center text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                                        Sonuç bulunamadı.
                                    </div>
                                ) : (
                                    <>
                                        <div
                                            className="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider"
                                            style={{
                                                color: 'var(--fg-soft)',
                                                borderBottom: '1px solid var(--border)',
                                            }}
                                        >
                                            Ürünler
                                        </div>
                                        <ul>
                                            {searchResults.map((p) => {
                                                const img = p.image_url || p.image;
                                                const brandName =
                                                    typeof p.brand === 'string'
                                                        ? p.brand
                                                        : (p.brand as { name?: string } | null | undefined)?.name;
                                                return (
                                                    <li key={p.id}>
                                                        <button
                                                            type="button"
                                                            onClick={() => goToProduct(p)}
                                                            className="w-full flex items-center gap-3 px-3 py-2.5 text-left transition-colors"
                                                            style={{ color: 'var(--fg)' }}
                                                            onMouseEnter={(e) => {
                                                                e.currentTarget.style.background = 'var(--bg-muted)';
                                                            }}
                                                            onMouseLeave={(e) => {
                                                                e.currentTarget.style.background = 'transparent';
                                                            }}
                                                        >
                                                            <div
                                                                className="relative flex-shrink-0 overflow-hidden rounded-md"
                                                                style={{
                                                                    width: 44,
                                                                    height: 44,
                                                                    background: 'var(--bg-muted)',
                                                                    border: '1px solid var(--border)',
                                                                }}
                                                            >
                                                                {img ? (
                                                                    <Image
                                                                        src={img}
                                                                        alt={p.name}
                                                                        fill
                                                                        sizes="44px"
                                                                        className="object-contain p-1"
                                                                    />
                                                                ) : null}
                                                            </div>
                                                            <div className="flex-1 min-w-0">
                                                                {brandName && (
                                                                    <div
                                                                        className="text-[10px] font-bold uppercase tracking-wider"
                                                                        style={{ color: 'var(--primary)' }}
                                                                    >
                                                                        {brandName}
                                                                    </div>
                                                                )}
                                                                <div className="text-[13px] font-medium leading-tight line-clamp-1">
                                                                    {p.name}
                                                                </div>
                                                                {(p.lowest_price ?? null) !== null && (
                                                                    <div
                                                                        className="mono text-[11px] mt-0.5"
                                                                        style={{ color: 'var(--success)' }}
                                                                    >
                                                                        {formatTL(p.lowest_price)}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </button>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                        <button
                                            type="button"
                                            onClick={onSearch}
                                            className="w-full flex items-center justify-center gap-2 px-3 py-3 text-[13px] font-semibold transition-colors"
                                            style={{
                                                color: 'var(--primary)',
                                                background: 'var(--primary-soft)',
                                                borderTop: '1px solid var(--border)',
                                            }}
                                        >
                                            &ldquo;{search.trim()}&rdquo; için tüm sonuçları gör
                                            {searchTotal > 0 && (
                                                <span className="mono opacity-70">({searchTotal.toLocaleString('tr-TR')})</span>
                                            )}
                                            <ArrowRight className="w-3.5 h-3.5" />
                                        </button>
                                    </>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="flex gap-3 items-center flex-shrink-0">
                        <NotificationDropdown />

                        <div ref={accountRef} className="relative">
                            <button
                                type="button"
                                className="btn btn-ghost"
                                onClick={() => setAccountOpen((v) => !v)}
                            >
                                <UserIcon className="w-4 h-4" />
                                <span>{user?.nickname || user?.business_name || 'Hesabım'}</span>
                                <ChevronDown className="w-3.5 h-3.5" />
                            </button>
                            {accountOpen && (
                                <div
                                    className="absolute right-0 mt-2 w-60 z-50 py-1"
                                    style={{
                                        background: 'var(--bg-elevated)',
                                        border: '1px solid var(--border)',
                                        borderRadius: 'var(--radius-lg)',
                                        boxShadow: 'var(--shadow-lg)',
                                    }}
                                >
                                    <div className="px-3 py-2 border-b" style={{ borderColor: 'var(--border)' }}>
                                        <div className="text-sm font-semibold" style={{ color: 'var(--fg)' }}>
                                            {user?.nickname || user?.business_name || 'Misafir'}
                                        </div>
                                        {user?.email && (
                                            <div className="text-xs truncate" style={{ color: 'var(--fg-soft)' }}>
                                                {user.email}
                                            </div>
                                        )}
                                    </div>
                                    <Link
                                        href="/market/hesabim"
                                        className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-bg-muted"
                                        style={{ color: 'var(--fg)' }}
                                    >
                                        <LayoutDashboard className="w-4 h-4" /> Hesabım
                                    </Link>
                                    <Link
                                        href="/market/hesabim?tab=orders"
                                        className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-bg-muted"
                                        style={{ color: 'var(--fg)' }}
                                    >
                                        <Package className="w-4 h-4" /> Siparişlerim
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => logout()}
                                        className="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-bg-muted text-left"
                                        style={{ color: 'var(--danger)' }}
                                    >
                                        <LogOut className="w-4 h-4" /> Çıkış yap
                                    </button>
                                </div>
                            )}
                        </div>

                        <MiniCart />
                    </div>
                </div>

                {/* Mobile: Search bar row */}
                <div className="mt-2.5 lg:hidden" ref={searchRef}>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            const q = search.trim();
                            if (q) {
                                setSearchOpen(false);
                                router.push(`/market/search?q=${encodeURIComponent(q)}`);
                            }
                        }}
                        className="flex items-center gap-2 relative"
                    >
                        <div className="relative flex-1">
                            <Search
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                type="search"
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setSearchOpen(true);
                                }}
                                onFocus={() => search.length >= 2 && setSearchOpen(true)}
                                placeholder="Ürün ara…"
                                className="input w-full"
                                style={{
                                    paddingLeft: 36,
                                    paddingRight: 44,
                                    height: 42,
                                    fontSize: 14,
                                    background: 'var(--bg)',
                                    color: 'var(--fg)',
                                    border: '1px solid var(--border)',
                                    borderRadius: 10,
                                }}
                                autoComplete="off"
                            />
                            <button
                                type="button"
                                onClick={() => setScannerOpen(true)}
                                className="absolute top-1/2 -translate-y-1/2 inline-flex items-center justify-center"
                                style={{
                                    right: 8,
                                    height: 30,
                                    width: 30,
                                    color: 'var(--fg-muted)',
                                    borderRadius: 6,
                                }}
                                aria-label="Barkod tara"
                            >
                                <ScanLine className="w-4 h-4" />
                            </button>

                            {/* Mobile search dropdown */}
                            {searchOpen && search.trim().length >= 2 && (
                                <div
                                    className="absolute left-0 right-0 mt-1 z-50 overflow-hidden"
                                    style={{
                                        background: 'var(--bg-elevated)',
                                        border: '1px solid var(--border)',
                                        borderRadius: 10,
                                        boxShadow: 'var(--shadow-lg)',
                                        maxHeight: '50vh',
                                        overflowY: 'auto',
                                    }}
                                >
                                    {searchLoading && searchResults.length === 0 ? (
                                        <div className="flex items-center justify-center gap-2 py-4 text-[12px]" style={{ color: 'var(--fg-muted)' }}>
                                            <Loader2 className="w-3.5 h-3.5 animate-spin" />
                                            Aranıyor…
                                        </div>
                                    ) : searchResults.length === 0 && !searchLoading ? (
                                        <div className="py-4 text-center text-[12px]" style={{ color: 'var(--fg-muted)' }}>
                                            Sonuç bulunamadı.
                                        </div>
                                    ) : (
                                        <>
                                            <ul>
                                                {searchResults.slice(0, 5).map((p) => {
                                                    const img = p.image_url || p.image;
                                                    const brandName =
                                                        typeof p.brand === 'string'
                                                            ? p.brand
                                                            : (p.brand as { name?: string } | null | undefined)?.name;
                                                    return (
                                                        <li key={p.id}>
                                                            <button
                                                                type="button"
                                                                onClick={() => goToProduct(p)}
                                                                className="w-full flex items-center gap-2.5 px-3 py-2.5 text-left transition-colors"
                                                                style={{ color: 'var(--fg)' }}
                                                            >
                                                                <div
                                                                    className="relative flex-shrink-0 overflow-hidden rounded-md"
                                                                    style={{
                                                                        width: 36,
                                                                        height: 36,
                                                                        background: 'var(--bg-muted)',
                                                                        border: '1px solid var(--border)',
                                                                    }}
                                                                >
                                                                    {img ? (
                                                                        <Image
                                                                            src={img}
                                                                            alt={p.name}
                                                                            fill
                                                                            sizes="36px"
                                                                            className="object-contain p-0.5"
                                                                        />
                                                                    ) : null}
                                                                </div>
                                                                <div className="flex-1 min-w-0">
                                                                    {brandName && (
                                                                        <div className="text-[9px] font-bold uppercase tracking-wider" style={{ color: 'var(--accent)' }}>
                                                                            {brandName}
                                                                        </div>
                                                                    )}
                                                                    <div className="text-[12px] font-medium leading-tight line-clamp-1">
                                                                        {p.name}
                                                                    </div>
                                                                    {(p.lowest_price ?? null) !== null && (
                                                                        <div className="mono text-[11px] mt-0.5" style={{ color: 'var(--success)' }}>
                                                                            {formatTL(p.lowest_price)}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </button>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setSearchOpen(false);
                                                    const q = search.trim();
                                                    if (q) router.push(`/market/search?q=${encodeURIComponent(q)}`);
                                                }}
                                                className="w-full flex items-center justify-center gap-2 px-3 py-2.5 text-[12px] font-semibold"
                                                style={{
                                                    color: 'var(--accent)',
                                                    background: 'var(--accent-soft)',
                                                    borderTop: '1px solid var(--border)',
                                                }}
                                            >
                                                Tüm sonuçları gör
                                                {searchTotal > 0 && (
                                                    <span className="mono opacity-70">({searchTotal.toLocaleString('tr-TR')})</span>
                                                )}
                                                <ArrowRight className="w-3 h-3" />
                                            </button>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                        <button
                            type="submit"
                            className="inline-flex items-center justify-center flex-shrink-0"
                            style={{
                                width: 42,
                                height: 42,
                                background: 'var(--accent)',
                                color: 'white',
                                borderRadius: 10,
                            }}
                            aria-label="Ara"
                        >
                            <Search className="w-5 h-5" />
                        </button>
                    </form>
                </div>
            </div>

            {/* Category nav — equally distributed top-level categories (desktop only) */}
            <nav className="hidden lg:block" style={{ background: 'var(--accent-hover)', borderTop: '1px solid rgba(255,255,255,0.12)' }}>
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 flex items-stretch overflow-x-auto lg:overflow-visible scrollbar-hide relative">
                    {navItems.map((cat) => {
                        const active = activeNav === cat.name;
                        const hasChildren = cat.children && cat.children.length > 0;
                        const isHovered = hoveredCat === cat.id;
                        return (
                            <div
                                key={cat.id}
                                className="relative flex-1 min-w-[120px] lg:min-w-0"
                                onMouseEnter={() => hasChildren && setHoveredCat(cat.id)}
                                onMouseLeave={() => setHoveredCat(null)}
                            >
                                <Link
                                    href={`/market/category/${cat.full_slug ?? cat.slug}`}
                                    className="px-3 py-4 text-[14px] font-semibold whitespace-nowrap inline-flex items-center justify-center gap-1.5 w-full relative transition-all"
                                    style={{
                                        color: 'var(--accent-on)',
                                        background: active || isHovered ? 'rgba(255,255,255,0.16)' : 'transparent',
                                    }}
                                >
                                    {cat.name}
                                    {hasChildren && (
                                        <ChevronDown
                                            className="w-3.5 h-3.5 transition-transform"
                                            style={{ transform: isHovered ? 'rotate(180deg)' : 'rotate(0deg)' }}
                                        />
                                    )}
                                    <span
                                        className="absolute left-4 right-4 bottom-0 h-[2px] transition-all"
                                        style={{
                                            background: '#ffffff',
                                            transform: active || isHovered ? 'scaleX(1)' : 'scaleX(0)',
                                            transformOrigin: 'center',
                                        }}
                                    />
                                </Link>
                                {hasChildren && isHovered && (
                                    <div
                                        className="absolute left-0 top-full z-50 py-2 animate-fade-up"
                                        style={{
                                            minWidth: 260,
                                            background: 'var(--bg-elevated)',
                                            border: '1px solid var(--border)',
                                            borderRadius: 'var(--radius-lg)',
                                            boxShadow: 'var(--shadow-lg)',
                                        }}
                                    >
                                        {cat.children!.map((ch) => (
                                            <Link
                                                key={ch.id}
                                                href={`/market/category/${ch.full_slug ?? ch.slug}`}
                                                className="flex items-center justify-between px-3.5 py-2 text-[13px] transition-colors"
                                                style={{ color: 'var(--fg)' }}
                                                onMouseEnter={(e) => {
                                                    e.currentTarget.style.background = 'var(--accent-soft)';
                                                    e.currentTarget.style.color = 'var(--accent-hover)';
                                                }}
                                                onMouseLeave={(e) => {
                                                    e.currentTarget.style.background = 'transparent';
                                                    e.currentTarget.style.color = 'var(--fg)';
                                                }}
                                            >
                                                <span className="font-medium">{ch.name}</span>
                                                {typeof ch.products_count === 'number' && ch.products_count > 0 && (
                                                    <span
                                                        className="mono text-[11px] ml-2"
                                                        style={{ color: 'var(--fg-soft)' }}
                                                    >
                                                        {ch.products_count.toLocaleString('tr-TR')}
                                                    </span>
                                                )}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </nav>

            {/* Mobile slide-out menu */}
            <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetContent side="left" className="w-[85vw] max-w-[360px] p-0 overflow-y-auto">
                    <SheetHeader className="px-4 py-4 border-b" style={{ borderColor: 'var(--border)', background: 'var(--accent)' }}>
                        <SheetTitle className="text-left text-white">
                            {user ? (
                                <div className="flex items-center gap-3">
                                    <div
                                        className="w-10 h-10 rounded-full flex items-center justify-center font-bold"
                                        style={{ background: 'rgba(255,255,255,0.22)', color: '#ffffff' }}
                                    >
                                        {(user.nickname || user.business_name || user.email || 'U')
                                            .charAt(0)
                                            .toUpperCase()}
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-[15px] font-semibold truncate">
                                            {user.nickname || user.business_name || 'Misafir'}
                                        </div>
                                        {user.email && (
                                            <div className="text-[11px] font-normal opacity-80 truncate">
                                                {user.email}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <span className="text-[16px] font-semibold">Menü</span>
                            )}
                        </SheetTitle>
                    </SheetHeader>

                    <div className="py-2">
                        {user ? (
                            <>
                                <MobileMenuItem
                                    href="/market/hesabim"
                                    icon={<LayoutDashboard className="w-4 h-4" />}
                                    label="Hesabım"
                                    onClose={() => setMobileMenuOpen(false)}
                                />
                                <MobileMenuItem
                                    href="/market/hesabim?tab=orders"
                                    icon={<Package className="w-4 h-4" />}
                                    label="Siparişlerim"
                                    onClose={() => setMobileMenuOpen(false)}
                                />
                            </>
                        ) : (
                            <MobileMenuItem
                                href="/login"
                                icon={<UserIcon className="w-4 h-4" />}
                                label="Giriş yap"
                                onClose={() => setMobileMenuOpen(false)}
                            />
                        )}
                        <MobileMenuItem
                            href="/market/kampanyalar"
                            icon={<Tag className="w-4 h-4" />}
                            label="Kampanyalar"
                            onClose={() => setMobileMenuOpen(false)}
                        />
                    </div>

                    <div className="border-t pt-2 pb-1" style={{ borderColor: 'var(--border)' }}>
                        <div
                            className="px-4 py-2 text-[11px] font-bold uppercase tracking-wider"
                            style={{ color: 'var(--fg-soft)' }}
                        >
                            Kategoriler
                        </div>
                        {categories.map((cat) => {
                            const expanded = mobileCatExpanded === cat.id;
                            const hasChildren = cat.children && cat.children.length > 0;
                            return (
                                <div key={cat.id}>
                                    <div className="flex items-stretch">
                                        <Link
                                            href={`/market/category/${cat.full_slug ?? cat.slug}`}
                                            onClick={() => setMobileMenuOpen(false)}
                                            className="flex-1 px-4 py-2.5 text-[14px] font-medium"
                                            style={{ color: 'var(--fg)' }}
                                        >
                                            {cat.name}
                                        </Link>
                                        {hasChildren && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setMobileCatExpanded(expanded ? null : cat.id)
                                                }
                                                aria-label={`${cat.name} alt kategorileri`}
                                                className="px-3 transition-colors"
                                                style={{ color: 'var(--fg-muted)' }}
                                            >
                                                <ChevronRight
                                                    className="w-4 h-4 transition-transform"
                                                    style={{
                                                        transform: expanded ? 'rotate(90deg)' : 'rotate(0deg)',
                                                    }}
                                                />
                                            </button>
                                        )}
                                    </div>
                                    {expanded && hasChildren && (
                                        <div style={{ background: 'var(--bg-muted)' }}>
                                            {cat.children!.map((ch) => (
                                                <Link
                                                    key={ch.id}
                                                    href={`/market/category/${ch.full_slug ?? ch.slug}`}
                                                    onClick={() => setMobileMenuOpen(false)}
                                                    className="block pl-8 pr-4 py-2 text-[13px]"
                                                    style={{ color: 'var(--fg-muted)' }}
                                                >
                                                    {ch.name}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    <div className="border-t mt-2 pt-2 pb-4" style={{ borderColor: 'var(--border)' }}>
                        <MobileMenuItem
                            href="/yardim"
                            icon={<HelpCircle className="w-4 h-4" />}
                            label="Yardım merkezi"
                            onClose={() => setMobileMenuOpen(false)}
                        />
                        <MobileMenuItem
                            href="/register"
                            icon={<Truck className="w-4 h-4" />}
                            label="Tedarikçi ol"
                            onClose={() => setMobileMenuOpen(false)}
                        />
                        {user && (
                            <button
                                type="button"
                                onClick={() => {
                                    setMobileMenuOpen(false);
                                    logout();
                                }}
                                className="w-full flex items-center gap-3 px-4 py-3 text-[14px] font-medium text-left"
                                style={{ color: 'var(--danger)' }}
                            >
                                <LogOut className="w-4 h-4" /> Çıkış yap
                            </button>
                        )}
                    </div>
                </SheetContent>
            </Sheet>

            {scannerOpen && (
                <BarcodeScanner
                    onScan={handleBarcodeScan}
                    onClose={() => setScannerOpen(false)}
                />
            )}
        </header>
    );
}

function MobileMenuItem({
    href,
    icon,
    label,
    onClose,
}: {
    href: string;
    icon: React.ReactNode;
    label: string;
    onClose: () => void;
}) {
    return (
        <Link
            href={href}
            onClick={onClose}
            className="flex items-center gap-3 px-4 py-3 text-[14px] font-medium transition-colors"
            style={{ color: 'var(--fg)' }}
        >
            {icon}
            {label}
        </Link>
    );
}

export default MarketHeader;
