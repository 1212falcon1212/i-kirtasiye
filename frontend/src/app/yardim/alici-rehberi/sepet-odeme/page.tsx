import { Metadata } from 'next';
import { ShoppingCart, CreditCard, MapPin, CheckCircle, AlertCircle, ArrowRight, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Sepet ve Ödeme - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda sepet oluşturma ve ödeme işlemleri nasıl yapılır?',
};

export default function SepetOdemePage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Sepet ve Ödeme Adımları
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                Sepete eklediğiniz ürünleri güvenli bir şekilde satın alabilirsiniz.
                Birden fazla satıcıdan ürün ekleyebilir, tek seferde ödeme yapabilirsiniz.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Sepete Ürün Ekleme
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-[#ffedd5] rounded-xl flex items-center justify-center flex-shrink-0">
                        <ShoppingCart className="w-6 h-6 text-[#ea580c]" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Ürün Ekleme</h3>
                        <ol className="text-gray-600 text-sm space-y-2">
                            <li>1. Ürün sayfasından istediğiniz teklifi seçin</li>
                            <li>2. Almak istediğiniz miktarı girin</li>
                            <li>3. &quot;Sepete Ekle&quot; butonuna tıklayın</li>
                        </ol>
                        <div className="mt-4 bg-blue-50 rounded-lg p-3 text-sm text-blue-700">
                            <p><strong>İpucu:</strong> Farklı satıcılardan ürün ekleyebilirsiniz.
                                Her satıcı için ayrı kargo ücreti uygulanabilir.</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Sepet Görüntüleme
            </h2>

            <p className="text-gray-600 leading-relaxed mb-4">
                Sağ üstteki sepet ikonuna tıklayarak sepetinizi görüntüleyebilirsiniz.
                Sepet sayfasında:
            </p>
            <ul className="text-gray-600 space-y-2 mb-6">
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#ea580c] flex-shrink-0" />
                    Ürünlerin miktarını değiştirebilirsiniz
                </li>
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#ea580c] flex-shrink-0" />
                    Ürün çıkarabilirsiniz
                </li>
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#ea580c] flex-shrink-0" />
                    Kargo ve toplam tutarı görebilirsiniz
                </li>
            </ul>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Ödeme Adımları
            </h2>

            <div className="space-y-6 mb-8">
                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                            <MapPin className="w-6 h-6 text-accent" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">1. Teslimat Adresi</h3>
                            <p className="text-gray-600 text-sm">
                                Kayıtlı kırtasiye adresiniz varsayılan teslimat adresi olarak gelir.
                                Farklı bir adres ekleyebilir veya düzenleyebilirsiniz.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                            <CreditCard className="w-6 h-6 text-accent" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">2. Ödeme Yöntemi</h3>
                            <p className="text-gray-600 text-sm mb-4">
                                Tercih ettiğiniz ödeme yöntemini seçin:
                            </p>
                            <div className="grid sm:grid-cols-2 gap-3">
                                <div className="bg-gray-50 rounded-lg p-3 text-sm">
                                    <p className="font-medium text-gray-700">Kredi/Banka Kartı</p>
                                    <p className="text-gray-500">Anında ödeme, 3D Secure güvenliği</p>
                                </div>
                                <div className="bg-gray-50 rounded-lg p-3 text-sm">
                                    <p className="font-medium text-gray-700">Havale/EFT</p>
                                    <p className="text-gray-500">Banka transferi, doğrulama sonrası gönderim</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-[#ffedd5] border border-[#ffedd5] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#ea580c] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Güvenli Ödeme
                </h4>
                <ul className="text-[#ea580c] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Tüm ödemeler 256-bit SSL ile şifrelenir
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Kart bilgileriniz saklanmaz
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        3D Secure doğrulama ile ek güvenlik
                    </li>
                </ul>
            </div>

            <div className="bg-amber-50 border border-amber-200 rounded-xl p-6 my-6">
                <h4 className="font-semibold text-amber-800 mb-3 flex items-center gap-2">
                    <AlertCircle className="w-5 h-5" />
                    Önemli Bilgiler
                </h4>
                <ul className="text-amber-700 space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Sipariş onaylandıktan sonra iptal için satıcıyla iletişime geçin
                    </li>
                    <li className="flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Havale/EFT ödemelerinde 24 saat içinde ödeme yapılmalıdır
                    </li>
                </ul>
            </div>

            <div className="flex items-center justify-between mt-10">
                <Link
                    href="/yardim/alici-rehberi/fiyat-karsilastirma"
                    className="text-gray-600 hover:text-[#ea580c] font-medium flex items-center gap-2"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Önceki: Fiyat Karşılaştırma
                </Link>
                <Link
                    href="/yardim/alici-rehberi/siparis-takibi"
                    className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    Sonraki: Sipariş Takibi
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>
        </div>
    );
}
