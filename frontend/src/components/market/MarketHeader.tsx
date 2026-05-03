'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import {
    Search, User as UserIcon, ChevronDown, Truck,
    ShieldCheck, HelpCircle, LogOut, Package, LayoutDashboard,
    Loader2, ArrowRight, X,
} from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { Logo } from '@/components/Logo';
import { MiniCart } from '@/components/cart/MiniCart';
import { NotificationDropdown } from '@/components/market/NotificationDropdown';
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
            .catch(() => {});
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
                            <ShieldCheck className="w-3.5 h-3.5" /> Güvenli ödeme & vadeli alışveriş
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
            <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-3.5 flex items-center gap-4">
                <Logo size="md" href="/market" />

                <div ref={searchRef} className="flex-1 relative">
                    <form onSubmit={onSearch} className="relative">
                        <Search
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
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
                            placeholder="Ürün, marka veya barkod ile ara — örn. Faber-Castell 9000"
                            className="input"
                            style={{
                                paddingLeft: 36,
                                paddingRight: search ? 110 : 84,
                                height: 44,
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
                                className="absolute"
                                style={{
                                    right: 96,
                                    top: 14,
                                    color: 'var(--fg-soft)',
                                }}
                                aria-label="Aramayı temizle"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        )}
                        <button
                            type="submit"
                            className="btn btn-primary absolute"
                            style={{ right: 4, top: 4, height: 36, padding: '0 16px' }}
                        >
                            Ara
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

                <div className="flex gap-1.5 items-center flex-shrink-0">
                    <NotificationDropdown />

                    <div ref={accountRef} className="relative">
                        <button
                            type="button"
                            className="btn btn-ghost"
                            onClick={() => setAccountOpen((v) => !v)}
                        >
                            <UserIcon className="w-4 h-4" />
                            <span className="hidden md:inline">{user?.nickname || user?.business_name || 'Hesabım'}</span>
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

            {/* Category nav — equally distributed top-level categories */}
            <nav style={{ background: 'var(--accent-hover)', borderTop: '1px solid rgba(255,255,255,0.12)' }}>
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
        </header>
    );
}

export default MarketHeader;
