import { Metadata } from 'next';
import { Box, Truck, MapPin, CheckCircle, Clock, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Sipariş Takibi - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda siparişlerinizi nasıl takip edersiniz?',
};

export default function SiparisTakibiPage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Sipariş Takibi
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                Siparişlerinizi &quot;Siparişlerim&quot; sayfasından anlık olarak takip edebilirsiniz.
                Kargo durumu değişikliklerinde bildirim alırsınız.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Siparişlerinize Erişim
            </h2>

            <p className="text-gray-600 leading-relaxed mb-4">
                <strong className="text-gray-900">Dashboard &gt; Siparişlerim</strong> yolunu izleyerek
                tüm siparişlerinizi görüntüleyebilirsiniz.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Sipariş Durumları
            </h2>

            <div className="bg-gray-50 rounded-xl p-6 mb-8">
                <div className="space-y-6">
                    <div className="flex items-start gap-4">
                        <div className="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <Clock className="w-5 h-5 text-amber-600" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-900">Ödeme Bekleniyor</p>
                            <p className="text-gray-500 text-sm">Havale/EFT ödemesi bekleniyor</p>
                        </div>
                    </div>

                    <div className="flex items-start gap-4">
                        <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <Box className="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-900">Hazırlanıyor</p>
                            <p className="text-gray-500 text-sm">Satıcı siparişi hazırlıyor</p>
                        </div>
                    </div>

                    <div className="flex items-start gap-4">
                        <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <Truck className="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-900">Kargoda</p>
                            <p className="text-gray-500 text-sm">Kargo firması tarafından taşınıyor</p>
                        </div>
                    </div>

                    <div className="flex items-start gap-4">
                        <div className="w-10 h-10 bg-accent-soft rounded-lg flex items-center justify-center flex-shrink-0">
                            <MapPin className="w-5 h-5 text-accent" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-900">Dağıtımda</p>
                            <p className="text-gray-500 text-sm">Teslimat için yola çıktı</p>
                        </div>
                    </div>

                    <div className="flex items-start gap-4">
                        <div className="w-10 h-10 bg-[#ffedd5] rounded-lg flex items-center justify-center flex-shrink-0">
                            <CheckCircle className="w-5 h-5 text-[#ea580c]" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-900">Teslim Edildi</p>
                            <p className="text-gray-500 text-sm">Sipariş başarıyla teslim alındı</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Kargo Takibi
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <Truck className="w-6 h-6 text-purple-600" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Kargo Bilgileri</h3>
                        <p className="text-gray-600 text-sm mb-4">
                            Sipariş kargoya verildiğinde takip numarası siparişinize eklenir.
                            Bu numara ile kargo firmasının sitesinden de takip yapabilirsiniz.
                        </p>
                        <ul className="text-gray-600 text-sm space-y-2">
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#ea580c] flex-shrink-0" />
                                Sipariş detay sayfasında &quot;Kargo Takibi&quot; butonuna tıklayın
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#ea580c] flex-shrink-0" />
                                Anlık konum ve tahmini teslimat tarihi gösterilir
                            </li>
                            <li className="flex items-start gap-2">
                                <CheckCircle className="w-4 h-4 mt-0.5 text-[#ea580c] flex-shrink-0" />
                                Tüm kargo hareketleri kronolojik olarak listelenir
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="bg-[#ffedd5] border border-[#ffedd5] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#ea580c] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Bildirimler
                </h4>
                <ul className="text-[#ea580c] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Sipariş durumu değiştiğinde e-posta alırsınız
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Kargo yola çıktığında SMS bildirimi (opsiyonel)
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        PWA push bildirimleri (izin verdiyseniz)
                    </li>
                </ul>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Teslimat Sonrası
            </h2>

            <p className="text-gray-600 leading-relaxed mb-4">
                Siparişiniz teslim edildikten sonra:
            </p>
            <ul className="text-gray-600 space-y-2 mb-6">
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#ea580c] flex-shrink-0" />
                    Teslimat onayı yapmanız istenir (7 gün otomatik onay)
                </li>
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#ea580c] flex-shrink-0" />
                    Satıcıyı puanlayabilirsiniz
                </li>
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#ea580c] flex-shrink-0" />
                    Sorun varsa destek talebi oluşturabilirsiniz
                </li>
            </ul>

            <div className="flex items-center mt-10">
                <Link
                    href="/yardim/alici-rehberi/sepet-odeme"
                    className="text-gray-600 hover:text-[#ea580c] font-medium flex items-center gap-2"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Önceki: Sepet ve Ödeme
                </Link>
            </div>
        </div>
    );
}
