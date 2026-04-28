<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'hakkimizda'],
            [
                'title' => 'Hakkımızda',
                'slug' => 'hakkimizda',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 1,
                'meta_title' => 'Hakkımızda - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com hakkında bilgi edinin. Türkiye\'nin güvenilir B2B kırtasiye tedarik platformu.',
                'excerpt' => 'Türkiye\'nin güvenilir B2B kırtasiye tedarik platformu i-kirtasiye.com hakkında.',
                'content' => $this->getAboutContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'slug' => 'iletisim',
                'template' => 'contact',
                'status' => 'published',
                'sort_order' => 2,
                'meta_title' => 'İletişim - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com ile iletişime geçin. Telefon, e-posta ve adres bilgilerimiz.',
                'excerpt' => 'Bize ulaşın. Sorularınız ve önerileriniz için iletişim bilgilerimiz.',
                'content' => $this->getContactContent(),
            ]
        );

        // Legal Pages
        Page::updateOrCreate(
            ['slug' => 'kvkk'],
            [
                'title' => 'KVKK Aydınlatma Metni',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 10,
                'meta_title' => 'KVKK Aydınlatma Metni - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com KVKK aydınlatma metni. Kişisel verilerinizin korunması hakkında bilgilendirme.',
                'excerpt' => '6698 sayılı KVKK kapsamında kişisel verilerinizin işlenmesine ilişkin aydınlatma metni.',
                'content' => $this->getKvkkContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Kullanım Koşulları',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 11,
                'meta_title' => 'Kullanım Koşulları - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com platform kullanım koşulları ve üyelik şartları.',
                'excerpt' => 'Platform kullanım koşulları, üyelik şartları ve sorumluluklar.',
                'content' => $this->getTermsContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Gizlilik Politikası',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 12,
                'meta_title' => 'Gizlilik Politikası - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com gizlilik politikası. Verilerinizin nasıl toplandığı ve korunduğu hakkında bilgi.',
                'excerpt' => 'Kişisel verilerinizin nasıl toplandığı, kullanıldığı ve korunduğu hakkında bilgilendirme.',
                'content' => $this->getPrivacyContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'cookies'],
            [
                'title' => 'Çerez Politikası',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 13,
                'meta_title' => 'Çerez Politikası - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com çerez politikası. Web sitemizde kullanılan çerezler hakkında bilgi.',
                'excerpt' => 'Web sitemizde kullanılan çerez türleri ve yönetimi hakkında bilgilendirme.',
                'content' => $this->getCookiesContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'mesafeli-satis-sozlesmesi'],
            [
                'title' => 'Mesafeli Satış Sözleşmesi',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 14,
                'meta_title' => 'Mesafeli Satış Sözleşmesi - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com mesafeli satış sözleşmesi. B2B kırtasiye pazaryeri üzerinden gerçekleştirilen satışlara ilişkin yasal sözleşme.',
                'excerpt' => '6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği kapsamında mesafeli satış sözleşmesi.',
                'content' => $this->getDistanceSalesContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'iptal-iade'],
            [
                'title' => 'İptal ve İade Koşulları',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 15,
                'meta_title' => 'İptal ve İade Koşulları - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com iptal ve iade koşulları. Sipariş iptali, ürün iadesi ve cayma hakkı hakkında bilgilendirme.',
                'excerpt' => 'Sipariş iptali, ürün iadesi, cayma hakkı ve geri ödeme süreçlerine ilişkin koşullar.',
                'content' => $this->getCancellationContent(),
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'uyelik-sozlesmesi'],
            [
                'title' => 'Üyelik Sözleşmesi',
                'template' => 'legal',
                'status' => 'published',
                'sort_order' => 16,
                'meta_title' => 'Üyelik Sözleşmesi - i-kirtasiye.com',
                'meta_description' => 'i-kirtasiye.com üyelik sözleşmesi. B2B kırtasiye pazaryeri üyelik koşulları ve tarafların hak ve yükümlülükleri.',
                'excerpt' => 'Platform üyelik koşulları, tarafların hak ve yükümlülükleri, komisyon ve ödeme şartları.',
                'content' => $this->getMembershipContent(),
            ]
        );
    }

    private function getAboutContent(): string
    {
        return <<<'HTML'
<h2>Türkiye'nin Güvenilir B2B Kırtasiye Tedarik Platformu</h2>
<p>i-kirtasiye.com olarak kırtasiyeciler arasında güvenli, hızlı ve şeffaf bir ticaret ortamı sunuyoruz. Platformumuz, kırtasiyecilerin ihtiyaç duydukları ürünlere en uygun fiyatlarla ulaşmasını sağlar.</p>

<h3>Misyonumuz</h3>
<p>Kırtasiyeler arasındaki B2B ticareti dijitalleştirerek, sektörde şeffaflık ve verimlilik sağlamak. Her kırtasiyenin ihtiyacı olan ürünlere hızlı ve güvenli bir şekilde ulaşabilmesini mümkün kılmak.</p>

<h3>Vizyonumuz</h3>
<p>Türkiye'nin en büyük ve en güvenilir B2B kırtasiye tedarik platformu olmak. Teknoloji ile sektörü dönüştürerek kırtasiyecilerin iş süreçlerini kolaylaştırmak.</p>

<h3>Neden i-kirtasiye.com?</h3>
<ul>
<li><strong>Güvenli Ticaret:</strong> Tüm işlemler platform güvencesi altında gerçekleşir.</li>
<li><strong>Rekabetçi Fiyatlar:</strong> Birden fazla satıcıdan teklif alarak en uygun fiyatı bulun.</li>
<li><strong>Hızlı Teslimat:</strong> Anlaşmalı kargo firmaları ile hızlı ve güvenli teslimat.</li>
<li><strong>7/24 Destek:</strong> Profesyonel destek ekibimiz her zaman yanınızda.</li>
<li><strong>Kolay Kullanım:</strong> Kullanıcı dostu arayüz ile saniyeler içinde sipariş verin.</li>
<li><strong>Şeffaf Süreç:</strong> Sipariş takibi, fatura yönetimi ve detaylı raporlama.</li>
</ul>

<h3>Rakamlarla i-kirtasiye.com</h3>
<p>Platformumuz her geçen gün büyümeye ve kırtasiyelere daha iyi hizmet vermeye devam ediyor. Binlerce kırtasiyenin güvenle tercih ettiği i-kirtasiye.com ile siz de yerinizi alın.</p>

<h3>Değerlerimiz</h3>
<ul>
<li><strong>Güvenilirlik:</strong> Tüm işlemlerde şeffaflık ve güven esastır.</li>
<li><strong>Yenilikçilik:</strong> Teknolojiyi kullanarak sektöre yeni çözümler sunuyoruz.</li>
<li><strong>Müşteri Odaklılık:</strong> Kırtasiyelerimizin ihtiyaçları her zaman önceliğimizdir.</li>
<li><strong>İşbirliği:</strong> Sektör paydaşları ile birlikte büyümeyi hedefliyoruz.</li>
</ul>
HTML;
    }

    private function getContactContent(): string
    {
        return <<<'HTML'
<h2>Bize Ulaşın</h2>
<p>Sorularınız, önerileriniz veya destek talepleriniz için aşağıdaki kanallardan bize ulaşabilirsiniz.</p>

<h3>İletişim Bilgileri</h3>
<ul>
<li><strong>Telefon:</strong> 0850 123 45 67</li>
<li><strong>E-posta:</strong> info@i-kirtasiye.com</li>
<li><strong>Adres:</strong> İstanbul, Türkiye</li>
</ul>

<h3>Çalışma Saatleri</h3>
<ul>
<li><strong>Pazartesi - Cuma:</strong> 09:00 - 18:00</li>
<li><strong>Cumartesi:</strong> 10:00 - 14:00</li>
<li><strong>Pazar:</strong> Kapalı</li>
</ul>

<h3>Destek</h3>
<p>Teknik destek ve sipariş ile ilgili sorularınız için hesabınızdan <strong>Destek Talebi</strong> oluşturabilirsiniz. Destek ekibimiz en kısa sürede size dönüş yapacaktır.</p>

<h3>Satıcı Olmak İstiyorum</h3>
<p>Platformumuzda satıcı olarak yer almak istiyorsanız, kayıt işleminizi tamamlayarak hemen satışa başlayabilirsiniz. Detaylı bilgi için bize ulaşın.</p>
HTML;
    }

    private function getKvkkContent(): string
    {
        return <<<'HTML'
<h2>KVKK Aydınlatma Metni</h2>
<p>i-kirtasiye B2B Kırtasiye Pazaryeri ("Platform") olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında veri sorumlusu sıfatıyla, kişisel verilerinizin işlenmesine ilişkin sizi bilgilendirmek istiyoruz.</p>

<h3>1. Veri Sorumlusu</h3>
<p>Kişisel verileriniz, veri sorumlusu olarak i-kirtasiye B2B Kırtasiye Pazaryeri tarafından aşağıda açıklanan kapsamda işlenmektedir.</p>

<h3>2. Kişisel Verilerin İşlenme Amaçları</h3>
<p>Kişisel verileriniz aşağıdaki amaçlarla işlenmektedir:</p>
<ul>
<li>Üyelik işlemlerinin gerçekleştirilmesi ve hesap yönetimi</li>
<li>Sipariş ve ödeme süreçlerinin yönetimi</li>
<li>Yasal yükümlülüklerin yerine getirilmesi (vergi, fatura vb.)</li>
<li>Platform güvenliğinin sağlanması</li>
<li>Müşteri hizmetleri ve destek süreçlerinin yürütülmesi</li>
<li>İstatistiksel analizler ve platform iyileştirmeleri</li>
</ul>

<h3>3. İşlenen Kişisel Veriler</h3>
<ul>
<li><strong>Kimlik Bilgileri:</strong> Ad, soyad, TC kimlik numarası</li>
<li><strong>İletişim Bilgileri:</strong> E-posta adresi, telefon numarası, adres</li>
<li><strong>Mesleki Bilgiler:</strong> Kırtasiye adı, Vergi numarası, vergi numarası, kırtasiye işletme bilgileri</li>
<li><strong>Finansal Bilgiler:</strong> Banka hesap bilgileri, ödeme bilgileri</li>
<li><strong>İşlem Bilgileri:</strong> Sipariş geçmişi, ödeme kayıtları</li>
<li><strong>Teknik Veriler:</strong> IP adresi, tarayıcı bilgisi, çerez verileri</li>
</ul>

<h3>4. Kişisel Verilerin Aktarımı</h3>
<p>Kişisel verileriniz, yukarıda belirtilen amaçlar doğrultusunda;</p>
<ul>
<li>Yasal düzenlemeler gereği yetkili kamu kurum ve kuruluşlarına</li>
<li>Ödeme hizmet sağlayıcılarına (güvenli ödeme altyapısı kapsamında)</li>
<li>Kargo ve lojistik firmalarına (teslimat süreçleri için)</li>
<li>Platform üzerinde işlem yaptığınız karşı tarafa (sipariş bilgileri kapsamında)</li>
</ul>
<p>aktarılabilmektedir.</p>

<h3>5. Kişisel Veri Toplama Yöntemleri ve Hukuki Sebepleri</h3>
<p>Kişisel verileriniz; platform üyelik formu, sipariş süreçleri, iletişim formu ve çerezler aracılığıyla otomatik ve otomatik olmayan yöntemlerle toplanmaktadır.</p>
<p>Hukuki sebepler: Sözleşmenin ifası, kanuni yükümlülük, meşru menfaat.</p>

<h3>6. Haklarınız</h3>
<p>KVKK'nın 11. maddesi kapsamında aşağıdaki haklara sahipsiniz:</p>
<ul>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
<li>İşlenmişse buna ilişkin bilgi talep etme</li>
<li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme</li>
<li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme</li>
<li>Eksik veya yanlış işlenmişse düzeltilmesini isteme</li>
<li>KVKK'nın 7. maddesinde öngörülen şartlar çerçevesinde silinmesini isteme</li>
<li>Aktarıldığı üçüncü kişilere bildirilmesini isteme</li>
<li>İşlenen verilerin aleyhine bir sonuç çıkmasına itiraz etme</li>
<li>Kanuna aykırı işleme sebebiyle zarara uğramanız halinde zararın giderilmesini talep etme</li>
</ul>

<h3>7. Başvuru</h3>
<p>Haklarınıza ilişkin taleplerinizi <strong>destek@i-kirtasiye.com</strong> adresine iletebilirsiniz. Başvurularınız en geç 30 gün içinde sonuçlandırılacaktır.</p>

<p><strong>Son güncelleme:</strong> Mart 2025</p>
HTML;
    }

    private function getTermsContent(): string
    {
        return <<<'HTML'
<h2>Kullanım Koşulları</h2>

<h3>1. Genel Hükümler</h3>
<p>i-kirtasiye B2B Kırtasiye Pazaryeri platformunu ("Platform") kullanarak bu kullanım koşullarını kabul etmiş sayılırsınız. Platform, yalnızca Vergi Numarası doğrulaması yapılmış kırtasiyeler tarafından kullanılabilir.</p>

<h3>2. Üyelik ve Hesap Güvenliği</h3>
<p>Üyelik işlemi sırasında verilen bilgilerin doğruluğundan kullanıcı sorumludur. Hesap bilgilerinizin güvenliğinden siz sorumlusunuz. Şüpheli aktivite tespit etmeniz durumunda derhal bizimle iletişime geçmelisiniz.</p>

<h3>3. Platform Kullanımı</h3>
<p>Platform üzerinden sadece yasal ürünlerin ticareti yapılabilir. Platform, kullanıcılar arasındaki işlemlerde aracı konumundadır.</p>

<h3>4. Ödeme ve Komisyon</h3>
<p>Platform üzerinden gerçekleştirilen satışlardan belirli oranda komisyon alınır. Komisyon oranları güncel tarifeye göre belirlenir. Ödemeler güvenli ödeme altyapısı üzerinden işlenir.</p>

<h3>5. İptal ve İade</h3>
<p>Sipariş iptali ve iade işlemleri ilgili mevzuat ve platform politikalarına tabidir. Detaylı bilgi için İptal ve İade Koşulları sayfasını inceleyiniz.</p>

<h3>6. Fikri Mülkiyet</h3>
<p>Platform üzerindeki tüm içerik, tasarım, logo ve yazılım i-kirtasiye'ya aittir. İzinsiz kopyalama, dağıtım veya değiştirme yasaktır.</p>

<h3>7. Sorumluluk Sınırları</h3>
<p>Platform, kullanıcılar arasındaki işlemlerin tarafı değildir. Ürün kalitesi ve uygunluğundan satıcı sorumludur. Platform, mücbir sebepler nedeniyle oluşabilecek aksaklıklardan sorumlu tutulamaz.</p>

<h3>8. Değişiklikler</h3>
<p>Bu kullanım koşulları önceden haber verilmeksizin güncellenebilir. Güncel versiyonu takip etmek kullanıcının sorumluluğundadır.</p>

<p><strong>Son güncelleme:</strong> Mart 2025</p>
HTML;
    }

    private function getPrivacyContent(): string
    {
        return <<<'HTML'
<h2>Gizlilik Politikası</h2>

<h3>1. Giriş</h3>
<p>i-kirtasiye B2B Kırtasiye Pazaryeri olarak kişisel verilerinizin güvenliği bizim için önemlidir. Bu gizlilik politikası, hangi verileri topladığımızı, nasıl kullandığımızı ve koruduğumuzu açıklar.</p>

<h3>2. Toplanan Veriler</h3>
<p>Platform üzerinden şu veriler toplanmaktadır:</p>
<ul>
<li>Kimlik bilgileri (ad, soyad, TC kimlik no)</li>
<li>İletişim bilgileri (e-posta, telefon, adres)</li>
<li>Kırtasiye bilgileri (Vergi numarası, kırtasiye adı, vergi numarası)</li>
<li>İşlem bilgileri (siparişler, ödemeler)</li>
<li>Teknik veriler (IP adresi, tarayıcı bilgisi, çerezler)</li>
</ul>

<h3>3. Verilerin Kullanımı</h3>
<p>Toplanan veriler şu amaçlarla kullanılmaktadır:</p>
<ul>
<li>Hizmetlerin sunulması ve iyileştirilmesi</li>
<li>Kimlik doğrulama ve güvenlik</li>
<li>Yasal yükümlülüklerin yerine getirilmesi</li>
<li>Müşteri desteği sağlanması</li>
<li>İstatistiksel analizler</li>
</ul>

<h3>4. Verilerin Paylaşımı</h3>
<p>Kişisel verileriniz yasal zorunluluklar dışında üçüncü taraflarla paylaşılmaz. İşlem gerçekleştirdiğiniz karşı tarafla gerekli bilgiler paylaşılır.</p>

<h3>5. Veri Güvenliği</h3>
<p>Verileriniz endüstri standardı güvenlik önlemleriyle korunmaktadır. SSL şifreleme, güvenli sunucular ve erişim kontrolü uygulanmaktadır.</p>

<h3>6. Haklarınız</h3>
<p>KVKK kapsamında verilerinize erişim, düzeltme, silme ve taşınabilirlik haklarına sahipsiniz. Başvurularınızı <strong>destek@i-kirtasiye.com</strong> adresine iletebilirsiniz.</p>

<p><strong>Son güncelleme:</strong> Mart 2025</p>
HTML;
    }

    private function getCookiesContent(): string
    {
        return <<<'HTML'
<h2>Çerez Politikası</h2>

<h3>1. Çerez Nedir?</h3>
<p>Çerezler, web sitemizi ziyaret ettiğinizde cihazınıza kaydedilen küçük metin dosyalarıdır. Bu dosyalar, siteyi daha verimli kullanmanızı sağlar ve tercihlerinizi hatırlamamıza yardımcı olur.</p>

<h3>2. Kullandığımız Çerez Türleri</h3>

<h4>Zorunlu Çerezler</h4>
<p>Sitenin düzgün çalışması için gerekli çerezlerdir. Oturum yönetimi, güvenlik ve temel işlevler için kullanılır.</p>

<h4>Performans Çerezleri</h4>
<p>Site performansını ölçmek ve iyileştirmek için kullanılır. Ziyaretçi istatistikleri ve hata raporları toplar.</p>

<h4>İşlevsellik Çerezleri</h4>
<p>Tercihlerinizi (dil, tema vb.) hatırlamak için kullanılır.</p>

<h4>Analiz Çerezleri</h4>
<p>Kullanıcı davranışlarını analiz etmek için Google Analytics gibi araçlar kullanılabilir.</p>

<h3>3. Çerez Tercihleri</h3>
<p>Tarayıcı ayarlarınızdan çerezleri kontrol edebilir, silebilir veya engelleyebilirsiniz. Ancak bazı çerezlerin engellenmesi site işlevselliğini etkileyebilir.</p>

<h3>4. Üçüncü Taraf Çerezleri</h3>
<p>Platformumuzda ödeme sağlayıcıları ve analiz araçları gibi üçüncü taraf hizmetler çerez kullanabilir. Bu çerezler ilgili firmaların gizlilik politikalarına tabidir.</p>

<p><strong>Son güncelleme:</strong> Mart 2025</p>
HTML;
    }

    private function getDistanceSalesContent(): string
    {
        return <<<'HTML'
<h2>Mesafeli Satış Sözleşmesi</h2>

<h3>Madde 1 - Taraflar</h3>

<h4>1.1. Satıcı</h4>
<p><strong>Unvan:</strong> Platform üzerinde ilgili ürünü satışa sunan satıcı kırtasiye<br>
<strong>Platform:</strong> i-kirtasiye B2B Kırtasiye Pazaryeri (www.i-kirtasiye.com)<br>
<strong>E-posta:</strong> destek@i-kirtasiye.com</p>

<h4>1.2. Alıcı</h4>
<p>Platform üzerinden sipariş veren ve Vergi No doğrulaması yapılmış üye kırtasiye. Alıcıya ait bilgiler sipariş sırasında beyan edilen bilgilerdir.</p>

<h3>Madde 2 - Sözleşmenin Konusu</h3>
<p>İşbu sözleşmenin konusu, Alıcı'nın i-kirtasiye B2B Kırtasiye Pazaryeri üzerinden elektronik ortamda siparişini verdiği, sözleşmede bahsi geçen nitelikleri haiz ve satış fiyatı belirtilen ürün/ürünlerin satışı ve teslimine ilişkin olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>

<h3>Madde 3 - Sözleşme Konusu Ürün Bilgileri</h3>
<p>Ürünün türü, miktarı, marka/modeli, rengi, adedi ve satış bedeli sipariş onayında belirtildiği şekildedir. Ürünlere ilişkin temel özellikler Platform'daki ürün detay sayfasında yer almaktadır. KDV dahil satış fiyatı sipariş özet sayfasında gösterilmektedir.</p>

<h3>Madde 4 - Genel Hükümler</h3>
<p>4.1. Alıcı, Platform'da sözleşme konusu ürünün temel nitelikleri, satış fiyatı, ödeme şekli ve teslimata ilişkin ön bilgileri okuyup bilgi sahibi olduğunu ve elektronik ortamda gerekli onayı verdiğini kabul ve beyan eder.</p>
<p>4.2. Sözleşme konusu ürün, yasal 30 günlük süreyi aşmamak koşulu ile her bir ürün için Alıcı'nın sipariş tarihinden itibaren belirtilen süre içinde Alıcı'ya veya gösterdiği adresteki kişi/kuruluşa teslim edilir.</p>
<p>4.3. Sözleşme konusu ürün, Alıcı'dan başka bir kişi/kuruluşa teslim edilecek ise, teslim edilecek kişi/kuruluşun teslimatı kabul etmemesinden Platform ve Satıcı sorumlu tutulamaz.</p>
<p>4.4. Platform, aracı hizmet sağlayıcı sıfatıyla Alıcı ile Satıcı arasındaki ticari işlemlere aracılık etmektedir.</p>

<h3>Madde 5 - Teslimat Şartları</h3>
<p>5.1. Teslimat, anlaşmalı kargo firmaları aracılığıyla, Alıcı'nın sipariş sırasında belirttiği adrese yapılır.</p>
<p>5.2. Teslimat süresi, siparişin onaylandığı tarihten itibaren başlar. Belirtilen teslimat süresi tahmini olup, stok durumu ve kargo koşullarına göre değişkenlik gösterebilir.</p>
<p>5.3. Kargo ücreti, sipariş özet sayfasında ayrıca belirtilir. Kargo ücretinin taraflardan hangisi tarafından karşılanacağı sipariş sırasında gösterilir.</p>
<p>5.4. Teslimat sırasında Alıcı'nın adresinde bulunmaması durumunda dahi Satıcı edimini tam ve eksiksiz olarak yerine getirmiş sayılır. Kargo firmasının ürünü Alıcı'ya ulaştıramamasından kaynaklanan gecikmelerden Satıcı sorumlu değildir.</p>

<h3>Madde 6 - Ödeme Koşulları</h3>
<p>6.1. Ürün bedeli, sipariş sırasında seçilen ödeme yöntemiyle tahsil edilir.</p>
<p>6.2. Platform üzerinde kredi kartı ile yapılan ödemeler, güvenli ödeme altyapısı (PayTR) üzerinden gerçekleştirilir.</p>
<p>6.3. Kredi kartı ile ödeme yapıldığında, Alıcı'nın kart ile ilgili bilgileri Platform tarafından saklanmaz. Ödeme işlemi anlık olarak ilgili banka ile gerçekleştirilir.</p>

<h3>Madde 7 - Cayma Hakkı</h3>
<p>7.1. Alıcı, sözleşme konusu ürünün kendisine veya gösterdiği adresteki kişi/kuruluşa tesliminden itibaren 14 (on dört) gün içinde cayma hakkını kullanabilir.</p>
<p>7.2. Cayma hakkının kullanılabilmesi için bu süre içinde Platform üzerinden veya destek@i-kirtasiye.com adresine yazılı bildirimde bulunulması gerekmektedir.</p>
<p>7.3. Aşağıdaki hallerde cayma hakkı kullanılamaz:</p>
<ul>
<li>Alıcı'ya özel hazırlanan veya kişiselleştirilen ürünler</li>
<li>Çabuk bozulabilen veya son kullanma tarihi geçme ihtimali olan ürünler</li>
<li>Tesliminden sonra ambalaj, bant, mühür, paket gibi koruyucu unsurları açılmış ürünler (sağlık ve hijyen açısından iade edilmesi uygun olmayanlar)</li>
<li>İlaç ve tıbbi ürünler (ilgili mevzuat gereği)</li>
<li>6502 sayılı Kanun ve ilgili yönetmeliklerde belirtilen diğer istisnalar</li>
</ul>

<h3>Madde 8 - İade Prosedürü</h3>
<p>8.1. Cayma hakkı kapsamında iade edilecek ürünün, ambalajı açılmamış, kullanılmamış ve orijinal durumunda olması gerekmektedir.</p>
<p>8.2. İade kargo ücreti, cayma hakkı kapsamındaki iadelerde Satıcı tarafından karşılanır.</p>
<p>8.3. Cayma hakkının kullanılması halinde, ürün bedeli Alıcı'ya en geç 14 (on dört) gün içinde ödeme yöntemine uygun şekilde iade edilir.</p>

<h3>Madde 9 - Garanti ve Ayıplı Ürün</h3>
<p>9.1. Sözleşme konusu ürünlerin ayıplı olması halinde Alıcı, 6502 sayılı Kanun hükümleri çerçevesinde haklarını kullanabilir.</p>
<p>9.2. Ürünün hasarlı veya eksik teslim edilmesi durumunda, teslimat tarihinden itibaren 3 (üç) iş günü içinde Platform'a bildirimde bulunulması gerekmektedir.</p>

<h3>Madde 10 - Platform'un Sorumluluğu</h3>
<p>10.1. Platform, aracı hizmet sağlayıcı sıfatıyla hareket etmekte olup, satışa sunulan ürünlerin ayıplarından, niteliklerinden veya Satıcı'nın yükümlülüklerini yerine getirmemesinden doğrudan sorumlu değildir.</p>
<p>10.2. Platform, ödeme güvenliğini sağlamak ve taraflar arasındaki uyuşmazlıklarda arabuluculuk yapmakla yükümlüdür.</p>

<h3>Madde 11 - Uyuşmazlık Çözümü</h3>
<p>İşbu sözleşmeden doğan uyuşmazlıklarda, Ticaret Bakanlığı tarafından ilan edilen değerlere göre Tüketici Hakem Heyetleri veya Tüketici Mahkemeleri yetkilidir. B2B işlemlerde İstanbul Mahkemeleri ve İcra Daireleri yetkilidir.</p>

<h3>Madde 12 - Yürürlük</h3>
<p>İşbu sözleşme, Alıcı tarafından elektronik ortamda onaylanması ile yürürlüğe girer. Sözleşmenin bir nüshası Alıcı'nın kayıtlı e-posta adresine gönderilir ve Platform hesabı üzerinden her zaman erişilebilir durumdadır.</p>

<p><strong>Son güncelleme:</strong> Nisan 2026</p>
HTML;
    }

    private function getCancellationContent(): string
    {
        return <<<'HTML'
<h2>İptal ve İade Koşulları</h2>

<h3>Madde 1 - Genel Bilgilendirme</h3>
<p>i-kirtasiye B2B Kırtasiye Pazaryeri ("Platform") üzerinden gerçekleştirilen alışverişlere ilişkin iptal ve iade koşulları, 6502 sayılı Tüketicinin Korunması Hakkında Kanun, Mesafeli Sözleşmeler Yönetmeliği ve ilgili mevzuat hükümleri çerçevesinde aşağıda düzenlenmiştir.</p>

<h3>Madde 2 - Sipariş İptali</h3>
<p>2.1. Alıcı, siparişin kargoya verilmesine kadar geçen süre içinde siparişini iptal edebilir. İptal talebi, Platform üzerindeki "Siparişlerim" bölümünden veya destek@i-kirtasiye.com adresine e-posta göndererek yapılabilir.</p>
<p>2.2. Kargoya verilen siparişlerde iptal talebi, ürünün teslim alınmasının ardından iade prosedürüne göre değerlendirilir.</p>
<p>2.3. İptal edilen siparişlerde ödeme, aşağıdaki sürelerde iade edilir:</p>
<ul>
<li><strong>Kredi Kartı ile Ödeme:</strong> İptal tarihinden itibaren en geç 14 iş günü içinde karta iade yapılır. Bankanın iade süresi farklılık gösterebilir.</li>
<li><strong>Havale/EFT ile Ödeme:</strong> İptal tarihinden itibaren en geç 7 iş günü içinde Alıcı'nın belirttiği banka hesabına iade yapılır.</li>
</ul>

<h3>Madde 3 - Cayma Hakkı</h3>
<p>3.1. Alıcı, ürünün teslim tarihinden itibaren 14 (on dört) gün içinde herhangi bir gerekçe göstermeksizin cayma hakkını kullanabilir.</p>
<p>3.2. Cayma hakkının kullanılabilmesi için:</p>
<ul>
<li>Ürünün ambalajının açılmamış, kullanılmamış ve orijinal durumunda olması gerekir.</li>
<li>Ürünle birlikte gönderilen tüm aksesuarlar ve belgeler eksiksiz olarak iade edilmelidir.</li>
<li>Platform üzerinden veya destek@i-kirtasiye.com adresine yazılı bildirimde bulunulmalıdır.</li>
</ul>

<h3>Madde 4 - Cayma Hakkı Kullanılamayan Ürünler</h3>
<p>Aşağıdaki ürünlerde cayma hakkı kullanılamaz:</p>
<ul>
<li>Alıcı'nın istekleri veya açıkça kişisel ihtiyaçları doğrultusunda hazırlanan ürünler</li>
<li>Çabuk bozulabilen veya son kullanma tarihi geçme ihtimali olan ürünler</li>
<li>Tesliminden sonra ambalaj, bant, mühür, paket gibi koruyucu unsurları açılmış olan ve sağlık veya hijyen açısından iade edilmesi uygun olmayan ürünler</li>
<li>İlaç ve kırtasiyecilik ürünleri (T.C. Sağlık Bakanlığı düzenlemeleri kapsamında)</li>
<li>Soğuk zincir gerektiren ve özel saklama koşullarına tabi ürünler</li>
<li>Tesliminden sonra başka ürünlerle karışan ve doğası gereği ayrıştırılması mümkün olmayan ürünler</li>
</ul>

<h3>Madde 5 - İade Prosedürü</h3>
<p>5.1. İade talebi onaylanan ürünler için Platform tarafından kargo kodu oluşturulur.</p>
<p>5.2. Alıcı, iade edilecek ürünü orijinal ambalajında, hasarsız ve eksiksiz şekilde kargoya teslim etmelidir.</p>
<p>5.3. İade kargo ücreti:</p>
<ul>
<li><strong>Cayma hakkı kapsamında:</strong> Satıcı tarafından karşılanır.</li>
<li><strong>Alıcı kaynaklı iadeler:</strong> Alıcı tarafından karşılanır.</li>
<li><strong>Ayıplı/hasarlı ürün iadeleri:</strong> Satıcı tarafından karşılanır.</li>
</ul>
<p>5.4. İade edilen ürün, Satıcı tarafından kontrol edildikten sonra iade süreci başlatılır.</p>

<h3>Madde 6 - Geri Ödeme</h3>
<p>6.1. İade onaylanan ürünlerin bedeli, ürünün Satıcı'ya ulaşmasını takiben en geç 14 (on dört) gün içinde Alıcı'ya iade edilir.</p>
<p>6.2. Geri ödeme, siparişte kullanılan ödeme yöntemine uygun şekilde yapılır:</p>
<ul>
<li><strong>Kredi kartı:</strong> Ödeme yapılan karta iade edilir.</li>
<li><strong>Havale/EFT:</strong> Alıcı'nın bildirdiği IBAN numarasına iade yapılır.</li>
</ul>
<p>6.3. Kısmi iade durumunda, iade edilen ürün(ler)in bedeli ve ilgili kargo ücreti hesaplanarak iade edilir.</p>

<h3>Madde 7 - Hasarlı veya Hatalı Ürün</h3>
<p>7.1. Ürünün hasarlı, hatalı veya sipariş edilenden farklı teslim edilmesi halinde, teslimat tarihinden itibaren 3 (üç) iş günü içinde Platform'a bildirimde bulunulmalıdır.</p>
<p>7.2. Bildirim sırasında hasarlı ürünün fotoğrafı ve kargo tutanağı talep edilebilir.</p>
<p>7.3. Hasarlı/hatalı ürün iadeleri en öncelikli şekilde değerlendirilir ve geri ödeme süreci derhal başlatılır.</p>

<h3>Madde 8 - Platform'un Rolü</h3>
<p>8.1. Platform, aracı hizmet sağlayıcı olarak iptal ve iade süreçlerinde Alıcı ile Satıcı arasında koordinasyonu sağlar.</p>
<p>8.2. Uyuşmazlık halinde Platform, ödeme güvencesi kapsamında Alıcı'nın haklarını koruyacak şekilde arabuluculuk yapar.</p>

<h3>Madde 9 - İletişim</h3>
<p>İptal ve iade talepleriniz için:</p>
<ul>
<li><strong>E-posta:</strong> destek@i-kirtasiye.com</li>
<li><strong>Platform:</strong> Hesabım &gt; Siparişlerim &gt; İade Talebi</li>
</ul>

<p><strong>Son güncelleme:</strong> Nisan 2026</p>
HTML;
    }

    private function getMembershipContent(): string
    {
        return <<<'HTML'
<h2>Üyelik Sözleşmesi</h2>

<h3>Madde 1 - Taraflar</h3>
<p>İşbu Üyelik Sözleşmesi ("Sözleşme"), aşağıdaki taraflar arasında elektronik ortamda akdedilmiştir:</p>
<p><strong>Platform İşletmecisi:</strong> i-kirtasiye B2B Kırtasiye Pazaryeri (www.i-kirtasiye.com) ("Platform")<br>
<strong>E-posta:</strong> destek@i-kirtasiye.com</p>
<p><strong>Üye:</strong> Platform'a üyelik başvurusunda bulunan ve Vergi No doğrulaması tamamlanmış kırtasiye ("Üye")</p>

<h3>Madde 2 - Sözleşmenin Konusu</h3>
<p>İşbu Sözleşme, Üye'nin Platform'u kullanmasına ilişkin koşulları, tarafların karşılıklı hak ve yükümlülüklerini, hizmet bedellerini ve sorumluluk esaslarını düzenlemektedir.</p>

<h3>Madde 3 - Tanımlar</h3>
<ul>
<li><strong>Platform:</strong> www.i-kirtasiye.com alan adı üzerinden erişilebilen B2B kırtasiye pazaryeri.</li>
<li><strong>Üye:</strong> Platform'a kayıt olarak ürün alım-satım yapma hakkı kazanan kırtasiye.</li>
<li><strong>Alıcı:</strong> Platform üzerinden ürün satın alan Üye.</li>
<li><strong>Satıcı:</strong> Platform üzerinden ürün satışa sunan Üye.</li>
<li><strong>Vergi No:</strong> Vergi Numarası; kırtasiyenin kimlik doğrulamasında kullanılan uluslararası numara.</li>
<li><strong>Hizmet Bedeli:</strong> Platform'un aracılık hizmeti karşılığında aldığı komisyon veya sabit ücret.</li>
</ul>

<h3>Madde 4 - Üyelik Koşulları</h3>
<p>4.1. Platform'a üye olabilmek için aşağıdaki koşulların sağlanması zorunludur:</p>
<ul>
<li>Türkiye Cumhuriyeti yasalarına göre kurulmuş, aktif bir kırtasiye işletmeına sahip olmak</li>
<li>Geçerli bir Vergi Numarası numarasına sahip olmak</li>
<li>Vergi levhası ve kırtasiye işletme belgelerini Platform'a sunmak</li>
<li>Üyelik başvurusunda doğru ve güncel bilgiler beyan etmek</li>
<li>İşbu Sözleşme'yi, KVKK Aydınlatma Metni'ni ve Gizlilik Politikası'nı okuyup kabul etmek</li>
</ul>
<p>4.2. Platform, üyelik başvurularını değerlendirme ve reddetme hakkını saklı tutar.</p>
<p>4.3. Üye, kayıt sırasında verdiği bilgilerin doğruluğundan bizzat sorumludur. Bilgilerde değişiklik olması halinde derhal Platform'u bilgilendirmekle yükümlüdür.</p>

<h3>Madde 5 - Üye'nin Hak ve Yükümlülükleri</h3>
<p>5.1. Üye, Platform'u yalnızca yasal amaçlarla kullanacağını kabul ve taahhüt eder.</p>
<p>5.2. Üye'nin yükümlülükleri:</p>
<ul>
<li>Hesap bilgilerinin (kullanıcı adı, şifre) güvenliğini sağlamak; üçüncü kişilerle paylaşmamak</li>
<li>Platform üzerinden yalnızca mevzuata uygun ürünlerin ticaretini yapmak</li>
<li>Satışa sunduğu ürünlerin kalitesinden, son kullanma tarihlerinden ve saklama koşullarından sorumlu olmak</li>
<li>Siparişleri belirtilen sürede eksiksiz ve hasarsız teslim etmek</li>
<li>Fatura ve vergisel yükümlülüklerini zamanında yerine getirmek</li>
<li>Platform kurallarına, bu Sözleşme'ye ve yürürlükteki mevzuata uymak</li>
<li>Rekabet hukukuna aykırı davranışlarda bulunmamak</li>
</ul>
<p>5.3. Üye'nin hakları:</p>
<ul>
<li>Platform üzerinden ürün alım-satım yapma</li>
<li>Sipariş takibi, fatura ve raporlama araçlarını kullanma</li>
<li>Teknik destek talep etme</li>
<li>Platform tarafından sunulan kampanya ve avantajlardan yararlanma</li>
</ul>

<h3>Madde 6 - Platform'un Hak ve Yükümlülükleri</h3>
<p>6.1. Platform'un yükümlülükleri:</p>
<ul>
<li>Güvenli ve kesintisiz bir ticaret ortamı sağlamak için gerekli teknik altyapıyı sunmak</li>
<li>Ödeme güvenliğini sağlamak</li>
<li>Üye bilgilerini KVKK ve Gizlilik Politikası kapsamında korumak</li>
<li>Destek taleplerini makul sürede yanıtlamak</li>
</ul>
<p>6.2. Platform'un hakları:</p>
<ul>
<li>Hizmet bedellerini ve komisyon oranlarını belirleme ve güncelleme</li>
<li>Sözleşme koşullarını değiştirme (değişiklikler Platform üzerinden duyurulur)</li>
<li>Kurallara uymayan Üye'nin hesabını askıya alma veya kapatma</li>
<li>Teknik bakım ve güncelleme amacıyla hizmeti geçici olarak durdurma</li>
</ul>

<h3>Madde 7 - Hizmet Bedeli ve Komisyon</h3>
<p>7.1. Platform, aracılık hizmeti karşılığında Satıcı'dan hizmet bedeli tahsil eder.</p>
<p>7.2. Hizmet bedeli, sipariş bazında sabit ücret veya satış tutarı üzerinden yüzdesel komisyon olarak uygulanabilir. Güncel hizmet bedeli tarifeleri Platform'da yayımlanır.</p>
<p>7.3. Hizmet bedeli, satış tutarından mahsup edilerek Satıcı'ya ödeme yapılır.</p>
<p>7.4. Platform, hizmet bedellerini önceden duyurarak değiştirme hakkını saklı tutar. Değişiklikler, duyuru tarihinden itibaren verilen siparişlere uygulanır.</p>

<h3>Madde 8 - Ödeme ve Hakediş</h3>
<p>8.1. Alıcı'dan tahsil edilen ürün bedeli, Platform tarafından güvenli ödeme altyapısı üzerinden işlenir.</p>
<p>8.2. Satıcı hakedişleri, siparişin teslim edilmesini ve iade süresinin dolmasını takiben, hizmet bedeli düşülerek Satıcı'nın tanımlı banka hesabına aktarılır.</p>
<p>8.3. Hakediş ödeme periyotları ve koşulları Platform'un güncel politikalarına tabidir.</p>

<h3>Madde 9 - Fikri Mülkiyet</h3>
<p>9.1. Platform'un adı, logosu, tasarımı, yazılımı ve tüm içeriği Platform İşletmecisi'ne aittir.</p>
<p>9.2. Üye, Platform'un fikri mülkiyet haklarını ihlal edecek herhangi bir eylemde bulunamaz.</p>
<p>9.3. Üye'nin Platform'a yüklediği ürün bilgileri ve görsellerin fikri mülkiyet haklarından Üye sorumludur.</p>

<h3>Madde 10 - Gizlilik ve Kişisel Veriler</h3>
<p>10.1. Taraflar, işbu Sözleşme kapsamında öğrendikleri ticari sırları ve gizli bilgileri üçüncü kişilerle paylaşmamayı taahhüt eder.</p>
<p>10.2. Kişisel verilerin işlenmesine ilişkin hususlar, Platform'un KVKK Aydınlatma Metni ve Gizlilik Politikası'nda düzenlenmiştir.</p>

<h3>Madde 11 - Sözleşmenin Süresi ve Feshi</h3>
<p>11.1. İşbu Sözleşme, Üye'nin üyelik başvurusunu onaylaması ile yürürlüğe girer ve süresiz olarak devam eder.</p>
<p>11.2. Taraflardan her biri, karşı tarafa yazılı bildirimde bulunmak suretiyle Sözleşme'yi feshedebilir.</p>
<p>11.3. Aşağıdaki hallerde Platform, Üye'ye bildirimde bulunarak üyeliği derhal askıya alabilir veya sonlandırabilir:</p>
<ul>
<li>Üye'nin Sözleşme koşullarını veya Platform kurallarını ihlal etmesi</li>
<li>Üye'nin sahte, yanıltıcı veya eksik bilgi vermesi</li>
<li>Üye'nin mevzuata aykırı ürün satması veya işlem yapması</li>
<li>Üye'nin kırtasiye işletmeının iptal edilmesi veya Vergi numarasının geçersiz hale gelmesi</li>
<li>Üye'nin Platform'un güvenliğini tehlikeye atacak faaliyetlerde bulunması</li>
</ul>
<p>11.4. Fesih halinde, fesih tarihine kadar doğmuş olan hak ve yükümlülükler saklıdır. Devam eden siparişler tamamlanır ve mali yükümlülükler yerine getirilir.</p>

<h3>Madde 12 - Sorumluluk Sınırları</h3>
<p>12.1. Platform, aracı hizmet sağlayıcı sıfatıyla faaliyet göstermekte olup, Üyeler arasındaki ticari işlemlerin tarafı değildir.</p>
<p>12.2. Satıcı, satışa sunduğu ürünlerin mevzuata uygunluğundan, kalitesinden ve güvenliğinden münferiden sorumludur.</p>
<p>12.3. Platform, mücbir sebeplerden (doğal afet, savaş, terör, salgın, yasal düzenleme değişiklikleri, teknik altyapı arızaları vb.) kaynaklanan aksaklıklardan sorumlu tutulamaz.</p>

<h3>Madde 13 - Uyuşmazlık Çözümü</h3>
<p>13.1. İşbu Sözleşme'den doğan uyuşmazlıklarda öncelikle taraflar arasında dostane çözüm aranır.</p>
<p>13.2. Uyuşmazlığın çözülememesi halinde İstanbul Mahkemeleri ve İcra Daireleri yetkilidir.</p>
<p>13.3. İşbu Sözleşme, Türkiye Cumhuriyeti hukukuna tabidir.</p>

<h3>Madde 14 - Sözleşme Değişiklikleri</h3>
<p>14.1. Platform, işbu Sözleşme'nin hükümlerini değiştirme hakkını saklı tutar.</p>
<p>14.2. Değişiklikler, Platform üzerinden duyurulur ve duyuru tarihinden itibaren yürürlüğe girer.</p>
<p>14.3. Üye, değişiklikleri kabul etmemesi halinde üyeliğini sonlandırma hakkına sahiptir. Platform'u kullanmaya devam etmek, değişikliklerin kabul edildiği anlamına gelir.</p>

<h3>Madde 15 - Yürürlük</h3>
<p>İşbu Sözleşme, 15 (on beş) maddeden ibaret olup, Üye'nin Platform üzerinden elektronik ortamda onay vermesi ile yürürlüğe girmiştir.</p>

<p><strong>Son güncelleme:</strong> Nisan 2026</p>
HTML;
    }
}
