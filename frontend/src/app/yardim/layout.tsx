'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
    BookOpen,
    ShoppingCart,
    Store,
    HelpCircle,
    ChevronRight,
    Home,
    ArrowLeft,
    Cross,
} from 'lucide-react';

const navigation = [
    {
        title: 'Başlarken',
        icon: BookOpen,
        items: [
            { title: 'Kayıt ve Vergi No Doğrulama', href: '/yardim/baslarken' },
        ],
    },
    {
        title: 'Satıcı Rehberi',
        icon: Store,
        items: [
            { title: 'Ürün Ekleme ve Teklif Oluşturma', href: '/yardim/satici-rehberi/urun-ekleme' },
            { title: 'Fiyat ve Stok Güncelleme', href: '/yardim/satici-rehberi/fiyat-stok' },
            { title: 'Sipariş Yönetimi ve Kargo', href: '/yardim/satici-rehberi/siparis-yonetimi' },
            { title: 'Ödeme Talebi ve Hakedişler', href: '/yardim/satici-rehberi/hakedis' },
        ],
    },
    {
        title: 'Alıcı Rehberi',
        icon: ShoppingCart,
        items: [
            { title: 'En Uygun Fiyatı Bulma', href: '/yardim/alici-rehberi/fiyat-karsilastirma' },
            { title: 'Sepet ve Ödeme Adımları', href: '/yardim/alici-rehberi/sepet-odeme' },
            { title: 'Sipariş Takibi', href: '/yardim/alici-rehberi/siparis-takibi' },
        ],
    },
];

export default function YardimLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const pathname = usePathname();

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <header className="bg-white border-b border-gray-200 sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        <div className="flex items-center gap-4">
                            <Link href="/" className="flex items-center gap-3">
                                <div className="w-9 h-9 bg-[#fbeede] rounded-lg flex items-center justify-center">
                                    <Cross className="w-5 h-5 text-white" />
                                </div>
                                <span className="font-bold text-lg text-gray-900">i-kirtasiye</span>
                            </Link>
                            <span className="text-gray-300">|</span>
                            <span className="text-gray-600 font-medium flex items-center gap-1.5">
                                <HelpCircle className="w-4 h-4" />
                                Yardım Merkezi
                            </span>
                        </div>
                        <div className="flex items-center gap-4">
                            <Link
                                href="/login"
                                className="text-gray-600 hover:text-gray-900 font-medium"
                            >
                                Giriş Yap
                            </Link>
                            <Link
                                href="/register"
                                className="bg-[#fbeede] text-white px-4 py-2 rounded-lg font-medium hover:bg-[#934f12] transition-colors"
                            >
                                Kayıt Ol
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex gap-8">
                    {/* Sidebar */}
                    <aside className="hidden lg:block w-72 flex-shrink-0">
                        <nav className="sticky top-24 space-y-6">
                            <Link
                                href="/yardim"
                                className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors ${pathname === '/yardim'
                                        ? 'bg-[#fbeede] text-[#b8651a]'
                                        : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                            >
                                <Home className="w-4 h-4" />
                                Ana Sayfa
                            </Link>

                            {navigation.map((section) => (
                                <div key={section.title}>
                                    <h3 className="flex items-center gap-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                        <section.icon className="w-4 h-4" />
                                        {section.title}
                                    </h3>
                                    <ul className="space-y-1">
                                        {section.items.map((item) => (
                                            <li key={item.href}>
                                                <Link
                                                    href={item.href}
                                                    className={`block px-4 py-2 rounded-lg text-sm transition-colors ${pathname === item.href
                                                            ? 'bg-[#fbeede] text-[#b8651a] font-medium'
                                                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                                        }`}
                                                >
                                                    {item.title}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </nav>
                    </aside>

                    {/* Main Content */}
                    <main className="flex-1 min-w-0">
                        {/* Breadcrumb */}
                        <nav className="flex items-center gap-2 text-sm text-gray-500 mb-6">
                            <Link href="/" className="hover:text-[#b8651a]">
                                Ana Sayfa
                            </Link>
                            <ChevronRight className="w-4 h-4" />
                            <Link href="/yardim" className="hover:text-[#b8651a]">
                                Yardım Merkezi
                            </Link>
                            {pathname !== '/yardim' && (
                                <>
                                    <ChevronRight className="w-4 h-4" />
                                    <span className="text-gray-900 font-medium">
                                        {navigation
                                            .flatMap((s) => s.items)
                                            .find((i) => i.href === pathname)?.title || 'Sayfa'}
                                    </span>
                                </>
                            )}
                        </nav>

                        {/* Content */}
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                            {children}
                        </div>

                        {/* Back Link */}
                        <div className="mt-6">
                            <Link
                                href="/yardim"
                                className="inline-flex items-center gap-2 text-gray-500 hover:text-[#b8651a] transition-colors text-sm"
                            >
                                <ArrowLeft className="w-4 h-4" />
                                Tüm Yardım Konuları
                            </Link>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
