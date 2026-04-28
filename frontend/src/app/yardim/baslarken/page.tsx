import { Metadata } from 'next';
import { CheckCircle, AlertCircle, ArrowRight, BadgeCheck } from 'lucide-react';
import Link from 'next/link';

export const metadata: Metadata = {
    title: 'Kayıt ve Vergi No Doğrulama - i-kirtasiye Yardım',
    description: 'i-kirtasiye\'na nasıl kayıt olunur ve Vergi numarası doğrulaması nasıl yapılır?',
};

export default function BaslarkenPage() {
    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                Kayıt ve Vergi No Doğrulama
            </h1>

            <p className="text-gray-600 leading-relaxed mb-8">
                i-kirtasiye, sadece onaylı eczacıların erişebildiği kapalı devre bir B2B platformudur.
                Platforma kayıt olmak için geçerli bir <strong className="text-gray-900">Vergi numarası</strong> numarasına
                sahip olmanız gerekmektedir.
            </p>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4" id="gln">
                Vergi numarası nedir?
            </h2>
            <p className="text-gray-600 leading-relaxed mb-4">
                Vergi numarası, küresel tedarik zincirinde işletmeleri ve lokasyonları tanımlamak için
                kullanılan 13 haneli benzersiz bir numaradır. Türkiye&apos;de işletmeler için vergi numaraları
                <strong className="text-gray-900"> Tİ-TÜS (Türkiye İlaç ve Tıbbi Cihaz Ulusal Bilgi Bankası)</strong> tarafından verilmektedir.
            </p>

            <div className="bg-[#fbeede] border border-[#fbeede] rounded-xl p-6 my-6">
                <h4 className="font-semibold text-[#b8651a] mb-2 flex items-center gap-2">
                    <BadgeCheck className="w-5 h-5" />
                    Vergi Numaranızı Nereden Bulabilirsiniz?
                </h4>
                <ul className="text-[#b8651a] space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Vergi dairesi kaydınızda
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        İlaç Takip Sistemi (İTS) giriş bilgilerinizde
                    </li>
                    <li className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Resmi vergi belgelerinizde
                    </li>
                </ul>
            </div>

            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                Kayıt Adımları
            </h2>

            <div className="space-y-6">
                {[
                    {
                        step: 1,
                        title: 'Vergi Numaranızı Girin',
                        description: 'Kayıt sayfasına gidin ve 10 haneli vergi numaranızı girin. Sistem, numaranızın geçerliliğini ve daha önce kayıt olup olmadığını kontrol edecektir.',
                    },
                    {
                        step: 2,
                        title: 'Firma Bilgilerinizi Onaylayın',
                        description: 'Vergi numaranız doğrulandığında, sisteme kayıtlı firma adı, il ve adres bilgileriniz otomatik olarak görünecektir. Bu bilgileri kontrol edin.',
                    },
                    {
                        step: 3,
                        title: 'Hesap Bilgilerinizi Oluşturun',
                        description: 'E-posta adresinizi ve güvenli bir şifre belirleyin. Şifreniz en az 8 karakter olmalıdır.',
                    },
                    {
                        step: 4,
                        title: 'Gerekli Evrakları Yükleyin',
                        description: 'GLİS evrakı, vergi levhası ve diğer gerekli belgeleri yükleyin. Bu belgeler admin ekibimiz tarafından incelenecektir.',
                    },
                    {
                        step: 5,
                        title: 'Onay Bekleyin',
                        description: 'Belgeleriniz onaylandıktan sonra platforma tam erişim sağlayabilirsiniz. Onay süreci genellikle 1 iş günü içinde tamamlanır.',
                    },
                ].map((item) => (
                    <div key={item.step} className="flex gap-4">
                        <div className="w-10 h-10 bg-gradient-to-br from-[#fbeede] to-teal-600 rounded-xl flex items-center justify-center text-white font-bold flex-shrink-0">
                            {item.step}
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 mb-1">{item.title}</h3>
                            <p className="text-gray-600 text-sm">{item.description}</p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="bg-amber-50 border border-amber-200 rounded-xl p-6 my-8">
                <h4 className="font-semibold text-amber-800 mb-2 flex items-center gap-2">
                    <AlertCircle className="w-5 h-5" />
                    Önemli Not
                </h4>
                <p className="text-amber-700 text-sm">
                    Bir vergi numarası ile yalnızca bir hesap oluşturulabilir. Eğer firmanız için daha önce
                    kayıt yapıldıysa, mevcut hesap yöneticisiyle iletişime geçmeniz veya
                    destek ekibimize ulaşmanız gerekmektedir.
                </p>
            </div>

            <div className="flex items-center gap-4 mt-10">
                <Link
                    href="/register"
                    className="bg-gradient-to-r from-[#fbeede] to-teal-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-[#fbeede] hover:to-teal-700 transition-all shadow-lg shadow-[#b8651a]/20 flex items-center gap-2"
                >
                    Şimdi Kayıt Ol
                    <ArrowRight className="w-4 h-4" />
                </Link>
                <Link
                    href="/login"
                    className="text-gray-600 hover:text-[#b8651a] font-medium"
                >
                    Zaten hesabım var
                </Link>
            </div>
        </div>
    );
}
