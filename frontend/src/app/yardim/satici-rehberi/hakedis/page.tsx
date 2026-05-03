import { Metadata } from 'next';
import { Wallet, CreditCard, Building, CheckCircle, AlertCircle, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Ödeme Talebi ve Hakedişler - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'nda satış hakedişlerinizi nasıl çekersiniz?',
};

export default function HakedisPage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Ödeme Talebi ve Hakedişler
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                Satislarinizdan elde ettiginiz gelir, sabit ₺50 hizmet bedeli dusuldukten sonra cuzdaniniza aktarilir.
                Cuzdan bakiyenizi istediginiz zaman banka hesabiniza cekebilirsiniz.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Hakediş Süreci
            </h2>

            <div className="bg-gray-50 rounded-xl p-6 mb-8">
                <ol className="space-y-4">
                    <li className="flex items-start gap-4">
                        <span className="w-8 h-8 bg-[#ffedd5] text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">1</span>
                        <div>
                            <p className="font-medium text-gray-900">Sipariş Tamamlanır</p>
                            <p className="text-gray-600 text-sm">Alıcı siparişi teslim alır veya 7 gün geçer</p>
                        </div>
                    </li>
                    <li className="flex items-start gap-4">
                        <span className="w-8 h-8 bg-[#ffedd5] text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">2</span>
                        <div>
                            <p className="font-medium text-gray-900">Hizmet Bedeli Kesintisi</p>
                            <p className="text-gray-600 text-sm">Sabit ₺50 hizmet bedeli dusulur</p>
                        </div>
                    </li>
                    <li className="flex items-start gap-4">
                        <span className="w-8 h-8 bg-[#ffedd5] text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">3</span>
                        <div>
                            <p className="font-medium text-gray-900">Cüzdana Aktarım</p>
                            <p className="text-gray-600 text-sm">Net tutar cüzdan bakiyenize eklenir</p>
                        </div>
                    </li>
                    <li className="flex items-start gap-4">
                        <span className="w-8 h-8 bg-[#ffedd5] text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">4</span>
                        <div>
                            <p className="font-medium text-gray-900">Ödeme Talebi</p>
                            <p className="text-gray-600 text-sm">İstediğiniz zaman banka hesabınıza çekin</p>
                        </div>
                    </li>
                </ol>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Cüzdan Yönetimi
            </h2>

            <div className="grid sm:grid-cols-2 gap-6 mb-8">
                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 bg-[#ffedd5] rounded-lg flex items-center justify-center">
                            <Wallet className="w-5 h-5 text-[#ea580c]" />
                        </div>
                        <h3 className="font-semibold text-gray-900">Bakiye Görüntüleme</h3>
                    </div>
                    <p className="text-gray-600 text-sm">
                        Dashboard &gt; Cüzdan menüsünden mevcut bakiyenizi, bekleyen hakedişlerinizi
                        ve geçmiş işlemlerinizi görüntüleyebilirsiniz.
                    </p>
                </div>

                <div className="bg-white border border-gray-200 rounded-xl p-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 bg-accent-soft rounded-lg flex items-center justify-center">
                            <Building className="w-5 h-5 text-accent" />
                        </div>
                        <h3 className="font-semibold text-gray-900">Banka Hesabı Ekleme</h3>
                    </div>
                    <p className="text-gray-600 text-sm">
                        Ödeme almak için en az bir banka hesabı tanımlamanız gerekir.
                        Birden fazla hesap ekleyebilir ve varsayılan hesap seçebilirsiniz.
                    </p>
                </div>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Ödeme Talebi Oluşturma
            </h2>

            <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 bg-accent-soft rounded-xl flex items-center justify-center flex-shrink-0">
                        <CreditCard className="w-6 h-6 text-accent" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 mb-2">Çekim İşlemi</h3>
                        <ol className="text-gray-600 text-sm space-y-2">
                            <li>1. Cüzdan sayfasında &quot;Ödeme Talebi Oluştur&quot; butonuna tıklayın</li>
                            <li>2. Çekmek istediğiniz tutarı girin</li>
                            <li>3. Banka hesabınızı seçin</li>
                            <li>4. Talebi onaylayın</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div className="bg-[#ffedd5] border border-[#ffedd5] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#ea580c] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Ödeme Koşulları
                </h4>
                <ul className="text-[#ea580c] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Minimum çekim tutarı: <strong>₺100</strong>
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Ödemeler <strong>1-3 iş günü</strong> içinde hesabınıza geçer
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Çekim işlemlerinden ek kesinti yapılmaz
                    </li>
                </ul>
            </div>

            <div className="bg-[#ffedd5] border border-[#ffedd5] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#ea580c] mb-3 flex items-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Hizmet Bedeli
                </h4>
                <div className="space-y-2 text-sm">
                    <div className="bg-white/50 rounded-lg px-3 py-2 flex justify-between">
                        <span className="text-[#ea580c]">Hizmet Bedeli:</span>
                        <strong>Sabit ₺50 / satici (siparis basina)</strong>
                    </div>
                    <div className="bg-white/50 rounded-lg px-3 py-2 flex justify-between">
                        <span className="text-[#ea580c]">Stopaj:</span>
                        <strong>%1</strong>
                    </div>
                    <div className="bg-white/50 rounded-lg px-3 py-2 flex justify-between">
                        <span className="text-[#ea580c]">Yuzdesel Komisyon:</span>
                        <strong className="text-[#ea580c]">YOK</strong>
                    </div>
                </div>
            </div>

            <div className="flex items-center mt-10">
                <Link
                    href="/yardim/satici-rehberi/siparis-yonetimi"
                    className="text-gray-600 hover:text-[#ea580c] font-medium flex items-center gap-2"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Önceki: Sipariş Yönetimi
                </Link>
            </div>
        </div>
    );
}
