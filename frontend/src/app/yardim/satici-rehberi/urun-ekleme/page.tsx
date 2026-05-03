import { Metadata } from 'next';
import { Box, Plus, ImageIcon, CheckCircle, AlertCircle, ArrowRight } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Ürün Ekleme - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda nasıl ürün eklenir ve teklif oluşturulur?',
};

export default function UrunEklemePage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Ürün Ekleme ve Teklif Oluşturma
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                i-kirtasiye&apos;nda ürün satışı yapmak için önce teklif oluşturmanız gerekmektedir.
                Her teklif bir ürün, stok miktarı ve birim fiyat içerir.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Ürün Ekleme Adımları
            </h2>

            <div className="space-y-8">
                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-[#ffedd5] rounded-xl flex items-center justify-center flex-shrink-0">
                            <Box className="w-6 h-6 text-[#ea580c]" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">1. Ürün Seçimi</h3>
                            <p className="text-gray-600 text-sm mb-4">
                                Dashboard &gt; Tekliflerim &gt; Yeni Teklif Oluştur yolunu izleyin.
                                Açılan formda ürün adı veya barkod ile arama yapın.
                            </p>
                            <div className="bg-gray-50 rounded-lg p-4 text-sm">
                                <p className="font-medium text-gray-700 mb-2">Ürün Arama Yöntemleri:</p>
                                <ul className="space-y-1 text-gray-600">
                                    <li>• Ürün adının ilk 3+ harfini yazarak arama</li>
                                    <li>• Barkod numarası ile doğrudan arama</li>
                                    <li>• Kategori filtresi ile daraltma</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                            <Plus className="w-6 h-6 text-accent" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">2. Stok ve Fiyat Bilgisi</h3>
                            <p className="text-gray-600 text-sm mb-4">
                                Ürünü seçtikten sonra stok miktarını ve birim satış fiyatını girin.
                            </p>
                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="bg-gray-50 rounded-lg p-4">
                                    <p className="font-medium text-gray-700 text-sm mb-1">Stok Miktarı</p>
                                    <p className="text-gray-600 text-sm">Satışa sunmak istediğiniz adet sayısı</p>
                                </div>
                                <div className="bg-gray-50 rounded-lg p-4">
                                    <p className="font-medium text-gray-700 text-sm mb-1">Birim Fiyat</p>
                                    <p className="text-gray-600 text-sm">KDV dahil birim satış fiyatı (₺)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                            <ImageIcon className="w-6 h-6 text-accent" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">3. Son Kullanma Tarihi ve Parti No</h3>
                            <p className="text-gray-600 text-sm mb-4">
                                İlaç güvenliği için SKT ve parti numarası bilgilerini girin.
                                Bu bilgiler alıcılara gösterilir.
                            </p>
                            <div className="flex items-start gap-2 text-sm text-amber-700 bg-amber-50 rounded-lg p-3">
                                <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                                <span>SKT&apos;si 3 aydan kısa kalan ürünler &quot;Kısa Vadeli&quot; olarak işaretlenir.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Teklif Yayınlama
            </h2>

            <p className="text-gray-600 leading-relaxed mb-4">
                Tüm bilgileri girdikten sonra &quot;Teklifi Yayınla&quot; butonuna tıklayın.
                Teklifiniz anında diğer eczacılara görünür hale gelecektir.
            </p>

            <div className="bg-[#ffedd5] border border-[#ffedd5] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#ea580c] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Başarılı Satış İçin İpuçları
                </h4>
                <ul className="text-[#ea580c] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Rekabetçi fiyat belirleyin - sistem size piyasa ortalamalarını gösterir
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Stok bilgisini güncel tutun - yanlış stok bilgisi olumsuz değerlendirmeye yol açar
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Uzun SKT&apos;li ürünler daha hızlı satılır
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Açıklama alanını kullanarak ekstra bilgi verin
                    </li>
                </ul>
            </div>

            <div className="flex items-center gap-4 mt-10">
                <Link
                    href="/yardim/satici-rehberi/fiyat-stok"
                    className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    Sonraki: Fiyat ve Stok Güncelleme
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>
        </div>
    );
}
