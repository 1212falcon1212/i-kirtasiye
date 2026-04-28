import { Metadata } from 'next';
import { Search, TrendingDown, Filter, CheckCircle, ArrowRight } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'En Uygun Fiyatı Bulma - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda en uygun fiyatı nasıl bulursunuz? Fiyat karşılaştırma rehberi.',
};

export default function FiyatKarsilastirmaPage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                En Uygun Fiyatı Bulma
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                i-kirtasiye, aynı ürün için birden fazla satıcının tekliflerini görmenizi sağlar.
                Cimri benzeri karşılaştırma yaparak en uygun fiyatı kolayca bulabilirsiniz.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Ürün Arama
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center flex-shrink-0">
                        <Search className="w-6 h-6 text-[#b8651a]" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Arama Yöntemleri</h3>
                        <ul className="text-gray-600 text-sm space-y-2">
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>Ürün adı:</strong> En az 3 karakter yazarak arama yapın</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>Barkod:</strong> 13 haneli barkod numarası ile doğrudan arama</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>Kategori:</strong> İlaç, kozmetik, medikal gibi kategorilerden göz atın</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Fiyat Karşılaştırma
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                        <TrendingDown className="w-6 h-6 text-accent" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Teklifleri Görüntüleme</h3>
                        <p className="text-gray-600 text-sm mb-4">
                            Ürün sayfasına gittiğinizde, o ürün için mevcut tüm teklifleri görebilirsiniz.
                            Her teklif şu bilgileri içerir:
                        </p>
                        <div className="grid sm:grid-cols-2 gap-3">
                            <div className="bg-gray-50 rounded-lg p-3 text-sm">
                                <p className="font-medium text-gray-700">Satıcı Bilgisi</p>
                                <p className="text-gray-500">Satıcı adı ve puanı</p>
                            </div>
                            <div className="bg-gray-50 rounded-lg p-3 text-sm">
                                <p className="font-medium text-gray-700">Birim Fiyat</p>
                                <p className="text-gray-500">KDV dahil satış fiyatı</p>
                            </div>
                            <div className="bg-gray-50 rounded-lg p-3 text-sm">
                                <p className="font-medium text-gray-700">Stok Durumu</p>
                                <p className="text-gray-500">Mevcut miktar</p>
                            </div>
                            <div className="bg-gray-50 rounded-lg p-3 text-sm">
                                <p className="font-medium text-gray-700">SKT</p>
                                <p className="text-gray-500">Son kullanma tarihi</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Filtreleme ve Sıralama
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                        <Filter className="w-6 h-6 text-accent" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Sonuçları Daraltın</h3>
                        <ul className="text-gray-600 text-sm space-y-2">
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>Fiyata göre sıralama:</strong> En düşükten en yükseğe veya tam tersi</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>SKT filtresi:</strong> Uzun vadeli ürünleri filtreleyin</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>Minimum stok:</strong> Belirli miktarın üzerindeki teklifleri gösterin</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#b8651a] flex-shrink-0" />
                                <span><strong>Satıcı puanı:</strong> Yüksek puanlı satıcıları tercih edin</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="bg-[#fbeede] border border-[#fbeede] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#b8651a] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Alışveriş İpuçları
                </h4>
                <ul className="text-[#b8651a] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        En düşük fiyat her zaman en iyi seçenek olmayabilir - satıcı puanını kontrol edin
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Aynı satıcıdan birden fazla ürün alarak kargo tasarrufu yapabilirsiniz
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        SKT&apos;si kısa ürünler genellikle daha ucuzdur
                    </li>
                </ul>
            </div>

            <div className="flex items-center gap-4 mt-10">
                <Link
                    href="/yardim/alici-rehberi/sepet-odeme"
                    className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    Sonraki: Sepet ve Ödeme
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>
        </div>
    );
}
