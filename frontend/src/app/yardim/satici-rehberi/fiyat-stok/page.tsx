import { Metadata } from 'next';
import { RefreshCw, TrendingUp, AlertCircle, CheckCircle, ArrowRight, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Fiyat ve Stok Güncelleme - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda tekliflerinizin fiyat ve stok bilgilerini nasıl güncellersiniz?',
};

export default function FiyatStokPage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Fiyat ve Stok Güncelleme
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                Aktif tekliflerinizin fiyat ve stok bilgilerini istediğiniz zaman güncelleyebilirsiniz.
                Rekabetçi kalmak için piyasa fiyatlarını takip etmenizi öneririz.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Tekliflerinize Erişim
            </h2>

            <p className="text-gray-600 leading-relaxed mb-4">
                <strong className="text-gray-900">Dashboard &gt; Tekliflerim</strong> menüsünden tüm aktif ve pasif tekliflerinizi görebilirsiniz.
            </p>

            <div className="bg-gray-50 rounded-xl p-6 mb-8">
                <h4 className="font-semibold text-gray-800 mb-4">Teklif Durumları</h4>
                <div className="grid sm:grid-cols-3 gap-4">
                    <div className="flex items-center gap-3">
                        <span className="w-3 h-3 rounded-full bg-[#fbeede]"></span>
                        <div>
                            <p className="font-medium text-gray-900 text-sm">Aktif</p>
                            <p className="text-gray-500 text-xs">Satışta, alıcılara görünür</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="w-3 h-3 rounded-full bg-amber-500"></span>
                        <div>
                            <p className="font-medium text-gray-900 text-sm">Stok Bitti</p>
                            <p className="text-gray-500 text-xs">Stok eklenince aktifleşir</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="w-3 h-3 rounded-full bg-gray-400"></span>
                        <div>
                            <p className="font-medium text-gray-900 text-sm">Pasif</p>
                            <p className="text-gray-500 text-xs">Elle durdurulmuş</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Fiyat Güncelleme
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center flex-shrink-0">
                        <TrendingUp className="w-6 h-6 text-[#b8651a]" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Fiyat Değiştirme</h3>
                        <ol className="text-gray-600 text-sm space-y-2">
                            <li>1. Tekliflerim sayfasından ilgili teklifi bulun</li>
                            <li>2. &quot;Düzenle&quot; butonuna tıklayın</li>
                            <li>3. Yeni fiyatı girin ve kaydedin</li>
                        </ol>
                        <p className="text-gray-500 text-sm mt-4">
                            Fiyat değişikliği anında uygulanır ve alıcılara yeni fiyat gösterilir.
                        </p>
                    </div>
                </div>
            </div>

            <div className="flex items-start gap-2 text-sm text-blue-700 bg-blue-50 rounded-lg p-4 mb-8">
                <TrendingUp className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <div>
                    <p className="font-medium mb-1">Piyasa Fiyatı Takibi</p>
                    <p>Ürün sayfalarında piyasa ortalaması ve en düşük fiyat bilgisi gösterilir.
                        Bu bilgiyi kullanarak rekabetçi fiyatlandırma yapabilirsiniz.</p>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Stok Güncelleme
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                        <RefreshCw className="w-6 h-6 text-accent" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Stok Miktarını Değiştirme</h3>
                        <p className="text-gray-600 text-sm mb-4">
                            Stok miktarını artırabilir veya azaltabilirsiniz. Stok 0&apos;a düştüğünde
                            teklif otomatik olarak &quot;Stok Bitti&quot; durumuna geçer.
                        </p>
                        <div className="flex items-start gap-2 text-sm text-amber-700 bg-amber-50 rounded-lg p-3">
                            <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                            <span>Sipariş alındığında stok otomatik olarak düşer. Manuel güncelleme yapmanıza gerek yoktur.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-[#fbeede] border border-[#fbeede] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#b8651a] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Stok Yönetimi İpuçları
                </h4>
                <ul className="text-[#b8651a] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Günlük olarak stoklarınızı kontrol edin
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Satamayacağınız ürünleri hemen pasife alın
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Yanlış stok bilgisi olumsuz puanlamaya yol açar
                    </li>
                </ul>
            </div>

            <div className="flex items-center justify-between mt-10">
                <Link
                    href="/yardim/satici-rehberi/urun-ekleme"
                    className="text-gray-600 hover:text-[#b8651a] font-medium flex items-center gap-2"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Önceki: Ürün Ekleme
                </Link>
                <Link
                    href="/yardim/satici-rehberi/siparis-yonetimi"
                    className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    Sonraki: Sipariş Yönetimi
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>
        </div>
    );
}
