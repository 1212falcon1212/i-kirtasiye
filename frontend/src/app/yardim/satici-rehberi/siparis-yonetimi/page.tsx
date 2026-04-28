import { Metadata } from 'next';
import { Box, Truck, CheckCircle, AlertCircle, Clock, ArrowRight, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Sipariş Yönetimi ve Kargo - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda satıcı olarak siparişleri nasıl yönetir ve kargoya verirsiniz?',
};

export default function SiparisYonetimiPage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Sipariş Yönetimi ve Kargo
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                Tekliflerinize sipariş geldiğinde bildirim alacaksınız. Siparişleri zamanında
                hazırlayıp kargoya vermek, başarılı satıcı puanı için kritik öneme sahiptir.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Sipariş Bildirimleri
            </h2>

            <p className="text-gray-600 leading-relaxed mb-4">
                Yeni sipariş aldığınızda:
            </p>
            <ul className="text-gray-600 space-y-2 mb-6">
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#b8651a] flex-shrink-0" />
                    E-posta ile bildirim gönderilir
                </li>
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#b8651a] flex-shrink-0" />
                    Dashboard&apos;da &quot;Yeni Siparişler&quot; sayacı güncellenir
                </li>
                <li className="flex items-start gap-2">
                    <CheckCircle className="w-4 h-4 mt-1 text-[#b8651a] flex-shrink-0" />
                    PWA bildirimi gönderilir (izin verdiyseniz)
                </li>
            </ul>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Sipariş Durumları
            </h2>

            <div className="bg-gray-50 rounded-xl p-6 mb-8">
                <div className="space-y-4">
                    {[
                        { status: 'Beklemede', color: 'amber', desc: 'Yeni sipariş, hazırlanmayı bekliyor' },
                        { status: 'Hazırlanıyor', color: 'blue', desc: 'Sipariş hazırlık aşamasında' },
                        { status: 'Kargoda', color: 'purple', desc: 'Kargoya verildi, yolda' },
                        { status: 'Teslim Edildi', color: 'pink', desc: 'Alıcıya ulaştı' },
                        { status: 'İptal', color: 'red', desc: 'Sipariş iptal edildi' },
                    ].map((item) => (
                        <div key={item.status} className="flex items-center gap-4">
                            <span className={`w-3 h-3 rounded-full bg-${item.color}-500`}></span>
                            <div>
                                <p className="font-medium text-gray-900 text-sm">{item.status}</p>
                                <p className="text-gray-500 text-xs">{item.desc}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Kargoya Verme Süreci
            </h2>

            <div className="space-y-6 mb-8">
                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center flex-shrink-0">
                            <Box className="w-6 h-6 text-[#b8651a]" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">1. Siparişi Hazırlayın</h3>
                            <p className="text-gray-600 text-sm">
                                Ürünleri dikkatlice paketleyin. SKT ve parti numaralarının siparişteki ile
                                eşleştiğinden emin olun.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                            <Truck className="w-6 h-6 text-accent" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">2. Kargo Etiketi Oluşturun</h3>
                            <p className="text-gray-600 text-sm mb-4">
                                Sipariş detay sayfasında &quot;Kargoya Ver&quot; butonuna tıklayın.
                                Sistem otomatik olarak kargo etiketi oluşturur.
                            </p>
                            <div className="bg-blue-50 rounded-lg p-3 text-sm text-blue-700">
                                <p className="font-medium mb-1">Entegre Kargo Firmaları:</p>
                                <p>Aras, Yurtiçi, MNG, PTT, Sürat ve daha fazlası</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-start gap-4">
                        <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                            <CheckCircle className="w-6 h-6 text-accent" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-2">3. Takip Numarasını Girin</h3>
                            <p className="text-gray-600 text-sm">
                                Kargo firmasından aldığınız takip numarasını sisteme girin.
                                Alıcı otomatik olarak bilgilendirilir ve kargo takibi yapabilir.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-amber-50 border border-amber-200 rounded-xl p-6 my-8">
                <h4 className="font-semibold text-amber-800 mb-3 flex items-center gap-2">
                    <Clock className="w-5 h-5" />
                    Süre Limitleri
                </h4>
                <ul className="text-amber-700 space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Siparişler <strong>48 saat</strong> içinde kargoya verilmelidir
                    </li>
                    <li className="flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Gecikmeler satıcı puanınızı olumsuz etkiler
                    </li>
                    <li className="flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Kargolanamayacak siparişleri hemen iptal edin
                    </li>
                </ul>
            </div>

            <div className="flex items-center justify-between mt-10">
                <Link
                    href="/yardim/satici-rehberi/fiyat-stok"
                    className="text-gray-600 hover:text-[#b8651a] font-medium flex items-center gap-2"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Önceki: Fiyat ve Stok
                </Link>
                <Link
                    href="/yardim/satici-rehberi/hakedis"
                    className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    Sonraki: Hakedişler
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>
        </div>
    );
}
