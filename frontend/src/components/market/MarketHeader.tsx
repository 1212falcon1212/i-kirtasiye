'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import {
    Search, User as UserIcon, ChevronDown, Truck,
    ShieldCheck, HelpCircle, LogOut, Package, LayoutDashboard, Zap, Grid3x3,
} from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { Logo } from '@/components/Logo';
import { ThemeToggle } from '@/components/ThemeToggle';
import { MiniCart } from '@/components/cart/MiniCart';
import { NotificationDropdown } from '@/components/market/NotificationDropdown';
import { api } from '@/lib/api';

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

export function MarketHeader({ activeNav }: MarketHeaderProps) {
    const router = useRouter();
    const { user, logout } = useAuth();

    const [search, setSearch] = useState('');
    const [accountOpen, setAccountOpen] = useState(false);
    const [categories, setCategories] = useState<CategoryNode[]>([]);
    const [hoveredCat, setHoveredCat] = useState<number | null>(null);
    const [allCatsOpen, setAllCatsOpen] = useState(false);
    const accountRef = useRef<HTMLDivElement>(null);
    const allCatsRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const onClick = (e: MouseEvent) => {
            const t = e.target as Node;
            if (accountRef.current && !accountRef.current.contains(t)) setAccountOpen(false);
            if (allCatsRef.current && !allCatsRef.current.contains(t)) setAllCatsOpen(false);
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

    const onSearch = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            const q = search.trim();
            if (q) router.push(`/market/search?q=${encodeURIComponent(q)}`);
        },
        [router, search],
    );

    const navItems = categories.slice(0, 8);

    return (
        <header
            className="w-full sticky top-0 z-40"
            style={{ background: 'var(--bg-elevated)', borderBottom: '1px solid var(--border)' }}
        >
            {/* Utility bar */}
            <div style={{ background: 'var(--bg-muted)', borderBottom: '1px solid var(--border)', fontSize: 12, color: 'var(--fg-muted)' }}>
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-1.5 flex justify-between items-center gap-4">
                    <div className="hidden sm:flex gap-4 items-center">
                        <span className="inline-flex items-center gap-1.5">
                            <Truck className="w-3.5 h-3.5" /> 1.500₺ üzeri kargo bedava
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <ShieldCheck className="w-3.5 h-3.5" /> Güvenli ödeme & vadeli alışveriş
                        </span>
                    </div>
                    <div className="flex gap-3 items-center ml-auto">
                        <Link href="/yardim" className="hover:underline inline-flex items-center gap-1">
                            <HelpCircle className="w-3.5 h-3.5" /> Yardım
                        </Link>
                        <Link href="/register" className="hover:underline">
                            Tedarikçi ol
                        </Link>
                        <ThemeToggle className="!h-7 !w-7 ml-1" />
                    </div>
                </div>
            </div>

            {/* TopBar: logo, search, tools */}
            <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-3.5 flex items-center gap-4">
                <Logo size="md" href="/market" />

                {/* All categories mega-menu trigger */}
                <div ref={allCatsRef} className="relative hidden md:block">
                    <button
                        type="button"
                        className="btn btn-ghost"
                        onClick={() => setAllCatsOpen((v) => !v)}
                    >
                        <Grid3x3 className="w-3.5 h-3.5" /> Kategoriler{' '}
                        <ChevronDown className="w-3.5 h-3.5" />
                    </button>
                    {allCatsOpen && categories.length > 0 && (
                        <div
                            className="absolute left-0 mt-2 z-50 grid grid-cols-3 gap-x-6 gap-y-1 p-4"
                            style={{
                                width: 720,
                                background: 'var(--bg-elevated)',
                                border: '1px solid var(--border)',
                                borderRadius: 'var(--radius-lg)',
                                boxShadow: 'var(--shadow-lg)',
                                maxHeight: '70vh',
                                overflowY: 'auto',
                            }}
                        >
                            {categories.map((c) => (
                                <div key={c.id} className="py-2">
                                    <Link
                                        href={`/market/category/${c.full_slug ?? c.slug}`}
                                        onClick={() => setAllCatsOpen(false)}
                                        className="block text-[13px] font-semibold mb-1 hover:underline"
                                        style={{ color: 'var(--fg)' }}
                                    >
                                        {c.name}
                                        {typeof c.products_count === 'number' && (
                                            <span
                                                className="mono ml-1.5 text-[11px] font-normal"
                                                style={{ color: 'var(--fg-soft)' }}
                                            >
                                                ({c.products_count.toLocaleString('tr-TR')})
                                            </span>
                                        )}
                                    </Link>
                                    {c.children && c.children.length > 0 && (
                                        <ul className="space-y-0.5">
                                            {c.children.slice(0, 6).map((ch) => (
                                                <li key={ch.id}>
                                                    <Link
                                                        href={`/market/category/${ch.full_slug ?? ch.slug}`}
                                                        onClick={() => setAllCatsOpen(false)}
                                                        className="block text-[12px] hover:underline"
                                                        style={{ color: 'var(--fg-muted)' }}
                                                    >
                                                        {ch.name}
                                                    </Link>
                                                </li>
                                            ))}
                                            {c.children.length > 6 && (
                                                <li>
                                                    <Link
                                                        href={`/market/category/${c.full_slug ?? c.slug}`}
                                                        onClick={() => setAllCatsOpen(false)}
                                                        className="text-[11px] font-medium"
                                                        style={{ color: 'var(--accent)' }}
                                                    >
                                                        +{c.children.length - 6} daha →
                                                    </Link>
                                                </li>
                                            )}
                                        </ul>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <form onSubmit={onSearch} className="flex-1 relative">
                    <Search
                        className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                        style={{ color: 'var(--fg-soft)' }}
                    />
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Ürün, marka veya SKU ile ara — örn. Faber-Castell 9000"
                        className="input"
                        style={{ paddingLeft: 36, paddingRight: 84, height: 44, fontSize: 14 }}
                    />
                    <button
                        type="submit"
                        className="btn btn-primary absolute"
                        style={{ right: 4, top: 4, height: 36, padding: '0 16px' }}
                    >
                        Ara
                    </button>
                </form>

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

            {/* MainNav with hover dropdowns */}
            <nav style={{ borderTop: '1px solid var(--border)' }}>
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 flex items-center gap-1 overflow-x-auto lg:overflow-visible scrollbar-hide relative">
                    <Link
                        href="/market/products"
                        className="px-3.5 py-3 text-[13px] font-medium whitespace-nowrap transition-colors border-b-2"
                        style={{
                            color: activeNav === 'Tüm kategoriler' ? 'var(--accent)' : 'var(--fg-muted)',
                            borderBottomColor: activeNav === 'Tüm kategoriler' ? 'var(--accent)' : 'transparent',
                        }}
                    >
                        Tüm kategoriler
                    </Link>

                    {navItems.map((cat) => {
                        const active = activeNav === cat.name;
                        const hasChildren = cat.children && cat.children.length > 0;
                        return (
                            <div
                                key={cat.id}
                                className="relative"
                                onMouseEnter={() => hasChildren && setHoveredCat(cat.id)}
                                onMouseLeave={() => setHoveredCat(null)}
                            >
                                <Link
                                    href={`/market/category/${cat.full_slug ?? cat.slug}`}
                                    className="px-3.5 py-3 text-[13px] font-medium whitespace-nowrap transition-colors border-b-2 inline-flex items-center gap-1"
                                    style={{
                                        color: active ? 'var(--accent)' : 'var(--fg-muted)',
                                        borderBottomColor: active ? 'var(--accent)' : 'transparent',
                                    }}
                                >
                                    {cat.name}
                                    {hasChildren && <ChevronDown className="w-3 h-3" />}
                                </Link>
                                {hasChildren && hoveredCat === cat.id && (
                                    <div
                                        className="absolute left-0 top-full z-50 py-2"
                                        style={{
                                            minWidth: 220,
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
                                                className="flex items-center justify-between px-3.5 py-2 text-[13px] hover:bg-bg-muted"
                                                style={{ color: 'var(--fg)' }}
                                            >
                                                <span>{ch.name}</span>
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

                    <Link
                        href="/market/kampanyalar"
                        className="px-3.5 py-3 text-[13px] font-medium whitespace-nowrap transition-colors border-b-2"
                        style={{
                            color: activeNav === 'Kampanyalar' ? 'var(--accent)' : 'var(--fg-muted)',
                            borderBottomColor: activeNav === 'Kampanyalar' ? 'var(--accent)' : 'transparent',
                        }}
                    >
                        Kampanyalar
                    </Link>

                    <span className="flex-1" />
                    <span
                        className="px-3.5 py-3 text-[13px] font-medium inline-flex items-center gap-1.5 whitespace-nowrap"
                        style={{ color: 'var(--success)' }}
                    >
                        <Zap className="w-3.5 h-3.5" /> 24 saatte teslim
                    </span>
                </div>
            </nav>
        </header>
    );
}

export default MarketHeader;
