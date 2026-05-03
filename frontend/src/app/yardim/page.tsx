import Link from 'next/link';
import {
    BookOpen,
    Store,
    ShoppingCart,
    ArrowRight,
    CheckCircle,
    Search
} from 'lucide-react';
import { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'Yardım Merkezi - i-kirtasiye',
    description: 'i-kirtasiye kullanım kılavuzları, satıcı ve alıcı rehberleri.',
};

const quickLinks = [
    {
        title: 'Başlarken',
        description: 'Kayıt, Vergi numarası doğrulama ve ilk adımlar',
        icon: BookOpen,
        href: '/yardim/baslarken',
        color: 'pink',
    },
    {
        title: 'Satıcı Rehberi',
        description: 'Ürün listeleme, sipariş yönetimi, hakedişler',
        icon: Store,
        href: '/yardim/satici-rehberi/urun-ekleme',
        color: 'teal',
    },
    {
        title: 'Alıcı Rehberi',
        description: 'Ürün arama, fiyat karşılaştırma, sipariş takibi',
        icon: ShoppingCart,
        href: '/yardim/alici-rehberi/fiyat-karsilastirma',
        color: 'cyan',
    },
];

const popularTopics = [
    { title: 'Vergi numaramı nereden bulabilirim?', href: '/yardim/baslarken#gln' },
    { title: 'Nasıl ürün eklerim?', href: '/yardim/satici-rehberi/urun-ekleme' },
    { title: 'Hakedişimi nasıl çekerim?', href: '/yardim/satici-rehberi/hakedis' },
    { title: 'Sipariş nasıl veririm?', href: '/yardim/alici-rehberi/sepet-odeme' },
    { title: 'Kargo takibi nasıl yapılır?', href: '/yardim/alici-rehberi/siparis-takibi' },
];

export default function YardimPage() {
    return (
        <div>
            {/* Hero */}
            <div className="text-center mb-12">
                <h1 className="text-3xl font-bold text-gray-900 mb-4">
                    Size Nasıl Yardımcı Olabiliriz?
                </h1>
                <p className="text-gray-600 max-w-xl mx-auto">
                    i-kirtasiye kullanımı hakkında aradığınız tüm bilgiler burada.
                    Aşağıdaki konulardan seçim yapabilir veya arama yapabilirsiniz.
                </p>

                {/* Search (decorative for now) */}
                <div className="mt-8 max-w-md mx-auto">
                    <div className="relative">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Yardım konularında ara..."
                            className="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 focus:border-[#ea580c] focus:ring-2 focus:ring-[#ea580c]/20 outline-none transition-all"
                        />
                    </div>
                </div>
            </div>

            {/* Quick Links */}
            <div className="grid sm:grid-cols-3 gap-6 mb-12">
                {quickLinks.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className="group bg-gray-50 hover:bg-white rounded-xl p-6 border border-gray-100 hover:border-[#ffedd5] hover:shadow-lg transition-all"
                    >
                        <div className={`w-12 h-12 bg-${item.color}-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform`}>
                            <item.icon className={`w-6 h-6 text-${item.color}-600`} />
                        </div>
                        <h3 className="font-semibold text-gray-900 mb-2 flex items-center gap-2">
                            {item.title}
                            <ArrowRight className="w-4 h-4 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all" />
                        </h3>
                        <p className="text-sm text-gray-600">{item.description}</p>
                    </Link>
                ))}
            </div>

            {/* Popular Topics */}
            <div>
                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                    Sık Sorulan Sorular
                </h2>
                <div className="space-y-2">
                    {popularTopics.map((topic) => (
                        <Link
                            key={topic.href}
                            href={topic.href}
                            className="flex items-center gap-3 p-4 rounded-lg hover:bg-gray-50 transition-colors group"
                        >
                            <CheckCircle className="w-5 h-5 text-[#ea580c]" />
                            <span className="text-gray-700 group-hover:text-[#ea580c] transition-colors">
                                {topic.title}
                            </span>
                            <ArrowRight className="w-4 h-4 ml-auto text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                        </Link>
                    ))}
                </div>
            </div>

            {/* Contact CTA */}
            <div className="mt-12 p-6 bg-gradient-to-r from-[#ffedd5] to-teal-50 rounded-xl border border-[#ffedd5]">
                <h3 className="font-semibold text-gray-900 mb-2">
                    Aradığınızı bulamadınız mı?
                </h3>
                <p className="text-sm text-gray-600 mb-4">
                    Destek ekibimiz size yardımcı olmak için hazır.
                </p>
                <a
                    href="mailto:destek@i-kirtasiye.com"
                    className="inline-flex items-center gap-2 bg-[#ffedd5] text-white px-4 py-2 rounded-lg font-medium hover:bg-[#1e40af] transition-colors text-sm"
                >
                    Bize Ulaşın
                    <ArrowRight className="w-4 h-4" />
                </a>
            </div>
        </div>
    );
}
