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

const NAV_ITEMS: { label: string; href: string }[] = [
    { label: 'Tüm kategoriler', href: '/market/products' },
    { label: 'Defter & Bloknot', href: '/market/category/defter-bloknot' },
    { label: 'Kalem & Yazı', href: '/market/category/kalem-yazi' },
    { label: 'Ofis', href: '/market/category/ofis' },
    { label: 'Okul', href: '/market/category/okul' },
    { label: 'Sanat & Hobi', href: '/market/category/sanat-hobi' },
    { label: 'Kağıt', href: '/market/category/kagit' },
    { label: 'Çizim', href: '/market/category/cizim' },
    { label: 'Kampanyalar', href: '/market/kampanyalar' },
];

interface MarketHeaderProps {
    activeNav?: string;
}

export function MarketHeader({ activeNav }: MarketHeaderProps) {
    const router = useRouter();
    const { user, logout } = useAuth();

    const [search, setSearch] = useState('');
    const [accountOpen, setAccountOpen] = useState(false);
    const accountRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const onClick = (e: MouseEvent) => {
            const t = e.target as Node;
            if (accountRef.current && !accountRef.current.contains(t)) setAccountOpen(false);
        };
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    const onSearch = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            const q = search.trim();
            if (q) router.push(`/market/search?q=${encodeURIComponent(q)}`);
        },
        [router, search],
    );

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

                <button
                    type="button"
                    onClick={() => router.push('/market/products')}
                    className="btn btn-ghost hidden md:inline-flex"
                >
                    <Grid3x3 className="w-3.5 h-3.5" /> Kategoriler <ChevronDown className="w-3.5 h-3.5" />
                </button>

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
                    {/* Notifications */}
                    <NotificationDropdown />

                    {/* Account */}
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

                    {/* Cart */}
                    <MiniCart />
                </div>
            </div>

            {/* MainNav */}
            <nav style={{ borderTop: '1px solid var(--border)' }}>
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 flex items-center gap-1 overflow-x-auto scrollbar-hide">
                    {NAV_ITEMS.map((it) => {
                        const active = activeNav === it.label;
                        return (
                            <Link
                                key={it.href}
                                href={it.href}
                                className="px-3.5 py-3 text-[13px] font-medium whitespace-nowrap transition-colors border-b-2"
                                style={{
                                    color: active ? 'var(--accent)' : 'var(--fg-muted)',
                                    borderBottomColor: active ? 'var(--accent)' : 'transparent',
                                }}
                            >
                                {it.label}
                            </Link>
                        );
                    })}
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
