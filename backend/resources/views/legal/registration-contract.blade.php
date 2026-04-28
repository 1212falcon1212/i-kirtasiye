<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #333;
            margin: 30px;
        }
        h1 {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 25px;
        }
        h2 {
            font-size: 13px;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 8px;
        }
        .header-info {
            margin-bottom: 25px;
            font-size: 11px;
        }
        .header-info p {
            margin: 2px 0;
        }
        .header-info strong {
            display: inline-block;
            min-width: 180px;
        }
        p {
            margin: 5px 0;
            text-align: justify;
        }
        ul {
            margin: 5px 0 5px 15px;
            padding: 0;
        }
        li {
            margin: 3px 0;
        }
        .footer {
            margin-top: 40px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        .page-break {
            page-break-before: always;
        }
        .signature-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding: 10px 20px;
        }
        .signature-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
            text-align: center;
        }
        .signature-info {
            font-size: 10px;
            text-align: center;
            margin-bottom: 5px;
            color: #555;
        }
        .signature-box {
            border: 1px dashed #999;
            height: 120px;
            margin-top: 15px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .signature-box p {
            text-align: center;
            font-size: 9px;
            color: #999;
            margin: 0;
            padding-bottom: 8px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
            margin-top: 100px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            color: #555;
        }
        .signature-date {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-top: 8px;
        }
    </style>
</head>
<body>

@php
    $cmsPage = \App\Models\Page::where('slug', 'uyelik-sozlesmesi')->published()->first();
@endphp

<h1>ÜYELİK SÖZLEŞMESİ</h1>

<div class="header-info">
    <p><strong>İsim Soyisim / Unvan:</strong> {{ $member_name }}</p>
    <p><strong>Adres:</strong> {{ $member_address }}</p>
    <p><strong>Komisyon Oranı:</strong> {{ $commission_rate }}</p>
    <p><strong>Kargo Politikası:</strong> {{ $shipping_policy }}</p>
    <p><strong>Sözleşme Tarihi:</strong> {{ $date }}</p>
</div>

<h2>MADDE 1 – TARAFLAR</h2>

<p><strong>1.1.</strong> İşbu üyelik sözleşmesi ("Üyelik Sözleşmesi" veya "Sözleşme" olarak anılacaktır) Merdivenköy Mah. Hızırbey Cad. Çamlık Apt. No: 249/43 Kadıköy / İstanbul adresinde mukim <strong>İstanbul Vitamin Kozmetik Ve Gıda Takviyeleri Pazarlama Ticaret Limited Şirketi</strong> (Bundan sonra "i-kirtasiye" olarak anılacaktır) ile Üye (Bundan sonra "Üye" olarak anılacaktır) arasında, Üye'nin, i-kirtasiye'nun www.i-kirtasiye.com isimli web sitesinde sunduğu Hizmetler'den faydalanmasına ilişkin koşulların tespit edilmesi amacıyla akdedilmiştir.</p>

<p><strong>1.2.</strong> i-kirtasiye ile Üye işbu Üyelik Sözleşmesi'nde ayrı ayrı "Taraf" ve birlikte "Taraflar" olarak anılacaktır.</p>

<h2>MADDE 2 – KONU, AMAÇ VE KAPSAM</h2>

<p><strong>2.1.</strong> i-kirtasiye, 6563 Sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun kapsamında Aracı Hizmet Sağlayıcı olarak www.i-kirtasiye.com alan adlı çevrimiçi elektronik ticaret platformunu işletmektedir. Bu çerçevede i-kirtasiye, başkalarına ait ticari faaliyetlerin yürütülmesine elektronik ortam sağlamaktadır. i-kirtasiye, kullanıcıların mevzuatla satışına izin verilen ürünlere erişebilecekleri bir pazaryeri platformu olup; www.i-kirtasiye.com web sitesinde yer alan herhangi bir ürün veya hizmetin doğrudan satıcısı konumunda değildir. Bu kapsamda, hizmet sundukları elektronik ortamı kullanan üyeler tarafından sağlanan içerikleri denetlemek, bu içerik ve içeriğe konu ürün veya hizmetle ilgili hukuka aykırı bir faaliyetin mevcut olup olmadığını araştırmakla yükümlü bulunmamaktadır. i-kirtasiye, Üye tarafından sağlanan içeriklerin ve satışa sunulan ürünlerin doğruluğu, kalitesi, güvenilirliği veya yasal uygunluğu hususunda herhangi bir beyan, garanti veya taahhütte bulunmamaktadır. Üye, bu konuda i-kirtasiye'dan herhangi bir talepte bulunamayacağını açıkça kabul eder.</p>

<p><strong>2.2.</strong> İşbu Üyelik Sözleşmesi uyarınca Üye, i-kirtasiye tarafından yönetilen www.i-kirtasiye.com alan adlı elektronik ticaret platformuna üye olarak, bu platformda statüsüne uygun biçimde ürün satışı yapabilir, ilan ekleyebilir ve/veya ürün satın alabilir.</p>

<p><strong>2.3.</strong> İşbu Üyelik Sözleşmesi'nin amacı; www.i-kirtasiye.com üzerinde mevzuatla satışına veya takasına izin verilen ürünlerin ve bu ürünlere ilişkin hizmetlerin sunulması, sunulan hizmet ve ürünlerden Üye'nin yararlanması için gerekli koşulların belirlenmesi ve bu doğrultuda Taraflar'ın hak ve yükümlülüklerinin tespiti oluşturmaktadır. İşbu Sözleşme'nin Üye tarafından kabul edilmesiyle Üye; www.i-kirtasiye.com'da yer alan ve yer alacak olan, Aracı Hizmet Sağlayıcı olan i-kirtasiye tarafından sunulan hizmetlere, kullanıma, içeriklere, uygulamalara ve Üye'ye yönelik her türlü beyanı da kabul etmiş olduğunu beyan ve taahhüt etmektedir.</p>

<p><strong>2.4.</strong> İşbu Üyelik Sözleşmesi, yalnızca i-kirtasiye ile Üye arasındaki ilişkiyi kapsamaktadır. Alıcılar ve Satıcılar arasındaki ilişki bu Sözleşme'nin kapsamı dışındadır. i-kirtasiye, Üye ile Satıcı/Satıcılar arasındaki ilişkiden hiçbir surette sorumlu değildir. Üyeler, Sanal Pazaryeri'nden gerçekleştirecekleri işlemlere ilişkin olarak Satıcılar'a karşı mevzuat çerçevesinde haklarını arayabileceklerdir.</p>

<p><strong>2.5.</strong> i-kirtasiye, satıcılara ürün ve/veya hizmetlerini kendi oluşturdukları içeriklerle satışa sunabilecekleri, alıcılara ise çeşitli kategorilerdeki ürün ve/veya hizmetleri inceleyebilecekleri ve sipariş verebilecekleri bir platform sunmaktadır.</p>

<p><strong>2.6.</strong> i-kirtasiye olarak kesinlikle kırtasiyecilik veya ürün satışı hizmeti verilmemektedir. Siteyi ziyaret eden her kullanıcı bunun bilincinde olmalıdır.</p>

<h2>MADDE 3 – TANIMLAR</h2>

<ul>
<li><strong>Üye:</strong> www.i-kirtasiye.com alan adlı internet adresi, mobil site veya mobil uygulamalar üzerinden satış yapmak isteyen Satıcı veya Satıcı tarafından ilan edilen ürünü ve/veya hizmeti satın almak isteyen Alıcı; gerçek kişi tacir veya tüzel kişi kullanıcıdır.</li>
<li><strong>Alıcı:</strong> i-kirtasiye ile yaptığı sözleşme kapsamında www.i-kirtasiye.com'a üye olan ve site üzerinde oluşturduğu hesap aracılığıyla Satıcı'nın yayınladığı ilanlar vasıtasıyla çeşitli ürün ve/veya hizmetleri satın alan gerçek kişi tacir veya tüzel kişi üyeyi ifade etmektedir.</li>
<li><strong>Satıcı:</strong> i-kirtasiye ile yaptığı sözleşme kapsamında www.i-kirtasiye.com'a üye olan ve www.i-kirtasiye.com üzerinde oluşturduğu hesap vasıtasıyla yayınladığı ilanlar aracılığıyla çeşitli ürün ve/veya hizmetleri satışa arz eden gerçek kişi tacir veya tüzel kişi üyeyi ifade etmektedir.</li>
<li><strong>Profilim Sayfası:</strong> Üye'nin www.i-kirtasiye.com'da yer alan çeşitli uygulamalardan ve Hizmetler'den yararlanabilmesi için gerekli işlemleri gerçekleştirebildiği, kişisel bilgilerini girdiği, yalnızca ilgili Üye tarafından belirlenen kullanıcı adı ve şifre ile erişilebilen özel sayfayı ifade etmektedir.</li>
<li><strong>Hizmet:</strong> Üye'nin işbu Üyelik Sözleşmesi içerisinde tanımlı olan iş ve işlemlerini gerçekleştirmesini sağlamak amacıyla, i-kirtasiye tarafından ortaya konulan uygulamaları ifade etmektedir.</li>
<li><strong>Sanal Pazaryeri:</strong> i-kirtasiye'nun, www.i-kirtasiye.com üzerinde i-kirtasiye kurallarına uygun olarak Satıcılar'a sağlamış olduğu, Satıcılar'ın ürün ve/veya hizmet ilanlarını yayınlayabilme imkânına sahip oldukları sanal alanı ifade eder.</li>
<li><strong>www.i-kirtasiye.com Sayfasının Mülkiyeti:</strong> İşbu Üyelik Sözleşmesi ile belirlenen hizmetleri sağlamakta olan www.i-kirtasiye.com alan adına sahip internet sitesinde, mobil uygulamalarında ve mobil sitelerinde bulunan, i-kirtasiye'ya ait marka ve fikri haklar dahil her türlü fikri ve sınai mülkiyet hakkını ifade etmektedir.</li>
<li><strong>Site veya www.i-kirtasiye.com:</strong> www.i-kirtasiye.com alan adlı web sitesi/mobil internet sitesi veya i-kirtasiye mobil uygulamasını ifade etmektedir.</li>
</ul>

<h2>MADDE 4 – TARAFLARIN HAK VE YÜKÜMLÜLÜKLERİ</h2>

<p><strong>4.1.</strong> Üyelik statüsünün elde edilebilmesi için, Üye olmak isteyen kullanıcının www.i-kirtasiye.com adresinde yer alan işbu Üyelik Sözleşmesi'ni onaylaması gerekmektedir. İşbu sözleşmeyi onaylayarak üye olan kullanıcı, sözleşmenin tüm hükümlerini kayıtsız şartsız kabul etmiş sayılacaktır. Kullanıcı, üye olurken MERSİS ve KEP bilgileri dahil olmak üzere i-kirtasiye'nun sitesinde istenen tüm bilgilerin doğruluğunu ve güncelliğini gözeterek doldurmakla yükümlüdür. Doğru ve güncel bilgi sağlamayan Üye, bu sebeple doğabilecek tüm zararlardan bizzat sorumlu olacaktır. i-kirtasiye, gerekli gördüğü hallerde kullanıcıdan ek belgeler talep edebilir ve bu belgeler sunulana kadar üyeliği başlatmayabilir veya askıya alabilir. Üyelik sonrasında bilgilerde değişiklik olması hâlinde Üye, değişiklikleri en geç beş gün içinde i-kirtasiye'ya bildirmekle yükümlüdür; aksi hâlde bu değişikliklerden doğacak sorumluluk tamamen Üye'ye aittir. İşbu Sözleşme'nin onayı ile Üyelik kayıt işlemi, kullanıcı tarafından gerekli bilgilerin ilgili alanlara girilmesi üzerine gönderilecek aktivasyon e-postasının teyit edilmesi ile tamamlanır. İşbu sözleşme, tüm üyeler için geçerlidir. Yeni üyeler için üyelik kayıt işleminin tamamlanması ile yürürlüğe girer. İşbu Sözleşme hükümleri uyarınca i-kirtasiye tarafından sunulan Hizmet, sadece ticari şirketlerin, ticari işletmelerin ve yalnızca ticari ve mesleki amaçlarla işlem yapan tacirlerin kullanmasına açık bir hizmet olup; tacir olmayan gerçek kişi tüketicilerin Hizmet'ten yararlanması işbu Sözleşme uyarınca yasaklanmıştır. Üye, Site üzerinde yalnızca ticari/mesleki amaçla faaliyet gösterebileceğini; Site üzerindeki hiçbir işleminin tüketici mevzuatı kapsamında kalmadığını ve bu mevzuat uyarınca i-kirtasiye'dan herhangi bir talebi olamayacağını kabul, beyan ve taahhüt eder.</p>

<p><strong>4.2.</strong> Üye'nin oluşturduğu kullanıcı adı ve şifre bilgisi münhasıran Üye tarafından belirlenmekte olup, bu bilgilerin güvenliği ve gizliliği tamamen Üye'nin sorumluluğundadır. Üye, kendisine ait kullanıcı adı ve şifre ile gerçekleştirilen tüm işlemlerin yalnızca kendisi tarafından yapıldığını ve bu işlemlerden doğan sorumluluğun peşinen kendisine ait olduğunu kabul, beyan ve taahhüt etmektedir. Bu şekilde gerçekleştirilen işlemlere ilişkin herhangi bir itiraz ileri süremeyeceğini ve söz konusu işlemler nedeniyle i-kirtasiye'nun, diğer Üyeler'in ve üçüncü kişilerin uğradığı tüm zararları tazmin edeceğini kabul eder. Üye, i-kirtasiye tarafından gerçekleştirilecek telefon ve e-posta doğrulama işlemlerinde yalnızca kendisine ait bilgiler ve cihazlar ile doğrulama yapacağını, üçüncü kişilerin üyelik hesabına erişmesine hiçbir şekilde izin vermeyeceğini, aksi takdirde doğabilecek tüm zararları tazmin edeceğini kabul, beyan ve taahhüt eder.</p>

<p><strong>4.3.</strong> Üye, www.i-kirtasiye.com internet sitesi, mobil uygulamalar ile mobil site üzerinde gerçekleştirdiği işlemlerde ve yazışmalarda; işbu Üyelik Sözleşmesi ve site içerisinde yer alan Kullanım Koşulları hükümlerine, belirtilen tüm koşullara ve yürürlükteki mevzuata uygun olarak hareket edeceğini beyan etmektedir. Üye'nin Site üzerinden satış yapması halinde Satıcı sıfatına haiz olacak ve; (i) Site üzerinde yayınlayacağı bütün ürünleri listelemeye ve satışa sunmaya yetkili olduğunu, ürünlerin tüm mevzuata uygun olduğunu, orijinal olduğunu; (ii) ürünlere ilişkin marka, logo ve diğer içerikleri kullanmaya yetkili olduğunu; (iii) kullandığı marka, logo ve içeriklerin üçüncü kişilerin haklarını ihlal etmediğini; (iv) yürürlükteki ve gelecekte yürürlüğe girecek tüm yasal düzenlemelere uygun davranacağını beyan ve kabul eder. Satıcı, Site'de yer alacak ürünlerini piyasa koşullarının çok üzerinde fahiş fiyatlarla satışa çıkarmayacağını ve ilgili bakanlıkların ve idari mercilerin belirledikleri kurallara uygun davranacağını kabul, beyan ve taahhüt eder.</p>

<p><strong>4.4.</strong> Alıcı, Satıcı'dan aldığı mal veya hizmetlerdeki ayıplardan hiçbir surette i-kirtasiye'nun sorumlu olmadığını ve ilgili mevzuat hükümleri dahilinde i-kirtasiye'ya izafe edilebilecek her türlü sorumluluk karşısında i-kirtasiye'yu şimdiden ibra ettiğini kabul, beyan ve taahhüt etmektedir.</p>

<p><strong>4.5.</strong> Üye, işbu Üyelik Sözleşmesi'ni kabul etmekle, Kullanım Koşulları'nı ve i-kirtasiye marka hakları ile teknik yapısına karşı olabilecek tecavüzlere karşı tüm sorumlulukları kabul etmiş sayılır.</p>

<p><strong>4.6.</strong> i-kirtasiye; doğrudan, dolaylı veya arızi zararlar, netice kabilinden doğan zararlar ve cezai tazminatlar da dahil olmak üzere, www.i-kirtasiye.com'un kullanılmasından kaynaklanabilecek zararlar için sorumlu tutulamaz. Üye'nin, Site'ye kötü niyetli üçüncü kişiler tarafından yüklenebilecek zararlı yazılımlar sebebiyle i-kirtasiye tarafından alınan güvenlik önlemlerine rağmen doğacak zararlardan i-kirtasiye sorumlu değildir. i-kirtasiye, Site üzerinde meydana gelecek veri kayıplarından da sorumlu tutulamaz.</p>

<p><strong>4.7.</strong> Üye, i-kirtasiye'da 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun madde 3/3 uyarınca satış işleminden önce Alıcı ile Satıcının bilgilerinin paylaşılmadığını bildiğini kabul eder. Üye, ürün ve/veya hizmetin satış fiyatı, vade farkı, teslim şartları ve ödeme koşulları konusunda site altyapısından yararlanarak haberleşebilir.</p>

<p><strong>4.8.</strong> Üye, kullanıcı adı ve şifresini başka kişi ya da kuruluşlara veremez ve paylaşamaz. Aksi halde bu kişinin yaptığı tüm işlemlerden bizzat sorumlu olacaktır. Üye'nin www.i-kirtasiye.com'deki üyeliği ile sahip olduğu kullanım hakkı yalnızca kendisine ait olup başkalarına devredilemez. Herhangi bir gerçek kişinin Site'ye tüzel bir kişi adına üye olması halinde, söz konusu gerçek kişi ilgili tüzel kişiyi temsil ve ilzama yetkili olduğunu beyan ve garanti eder. Aksi halde gerçek kişi, doğan borç ve yükümlülüklerden şahsen sorumlu olmayı kabul eder.</p>

<p><strong>4.9.</strong> Ürünlerin teslim/ifa edilmemesi, fiyatların manipüle edilmesi, aldatıcı içerik yerleştirilmesi veya i-kirtasiye'nun yazılı onayı olmaksızın kullanıcı hesabının devredilmesi gibi fiiller, işbu sözleşmenin ihlali sayılacaktır. Bu durumda i-kirtasiye'nun, Üye'nin üyeliğini iptal etme hakkı ile Sözleşme'nin 12. maddesinde düzenlenen hakları saklıdır.</p>

<p><strong>4.10.</strong> i-kirtasiye, www.i-kirtasiye.com'da yer alan herhangi bir ürün veya hizmetin satıcısı konumunda bulunmamaktadır. 6563 sayılı Kanun uyarınca yalnızca "aracı hizmet sağlayıcı" ve 5651 sayılı Kanun uyarınca "yer sağlayıcı" konumundadır. Bu sebeple i-kirtasiye, Sanal Pazaryeri'nde yer alan ve kendisi tarafından yayınlanmamış hiçbir içeriğin gerçekliğinden, güvenilirliğinden veya hukuka uygunluğundan sorumlu değildir. Ancak i-kirtasiye, inisiyatifi tamamen kendisinde olmak üzere, içerikleri dilediği zaman kontrol etme ve gerekli gördüğünde erişime kapatma ve/veya silme hakkına sahiptir. Aşağıdaki haller bu yetkinin kullanımı için haklı sebeplerdendir: (a) Üyenin vergi numarasının pasif olması (b) i-kirtasiye çalışanlarına veya site kullanıcılarına uygunsuz üslupla iletişim kurulması (c) Sosyal medyada karalama yapılması (d) Şüpheli aktivitede bulunulması (bot kullanımı, data çalma vb.) (e) Satıcılar için düşük performans puanı (f) Sipariş süreçlerinde alıcı sorunlarının çözülmemesi</p>

<p><strong>4.11.</strong> i-kirtasiye, 6493 sayılı Ödeme ve Menkul Kıymet Mutabakat Sistemleri, Ödeme Hizmetleri ve Elektronik Para Kuruluşları Hakkında Kanun madde 12/2/(b) uyarınca; www.i-kirtasiye.com üzerinden gerçekleştirilen satışların bedellerine ilişkin tahsilat ve transferleri, ticari temsilci sıfatıyla ödeme hizmetlerini doğrudan kendisi sunarak gerçekleştirmektedir. Üye, satışını gerçekleştireceği ürünlerin bedelinin kendi adına ve hesabına tahsili konusunda i-kirtasiye'yu süresiz ve geri dönülemez şekilde yetkilendirdiğini kabul ve taahhüt eder.</p>

<p><strong>4.12.</strong> Üye, hem Alıcı hem Satıcı sıfatıyla, Site üzerindeki satışların güvenli bir şekilde gerçekleşmesi amacıyla, i-kirtasiye'ya tahsilat sürecinin yönetimiyle sınırlı olarak temsil yetkisi verdiğini kabul eder. Satıcı, satışa sunduğu ürünlere ilişkin bedellerin kendi nam ve hesabına Alıcı'dan tahsil edilmesi konusuna münhasır olmak kaydıyla i-kirtasiye'yu temsilci olarak atamaktadır. Alıcılar, ödemeyi Satıcı'nın temsilcisi sıfatıyla i-kirtasiye'ya yapmakla ödeme yükümlülüğünü ifa etmiş olacak ve ayrıca Satıcı'ya ödeme yapmak zorunda kalmayacaktır.</p>

<p><strong>4.13.</strong> Üye, www.i-kirtasiye.com üzerinden herhangi bir Satıcı'dan vereceği siparişler için akdedilecek Satış Sözleşmeleri'nde; Satıcı'nın satıcı taraf, kendisinin ise alıcı taraf olduğunu kabul ve beyan eder.</p>

<p><strong>4.14.</strong> i-kirtasiye, Satış Sözleşmesi ilişkisinde ve satış işlemlerinde hiçbir suretle taraf olarak bulunmamaktadır. Sanal Pazaryeri'nde satılan tüm ürünlerin kalitesinden, mevzuata uygunluğundan, faturalandırılmasından ve zamanında tesliminden münhasıran Satıcı sorumludur. i-kirtasiye satıcı, üretici veya ithalatçı sıfatına sahip olmadığından, sitesi üzerinden satılan ürünlerdeki ayıplardan da hiçbir şekilde sorumlu değildir.</p>

<p><strong>4.15.</strong> Ürünün kargolanması sürecinde meydana gelen hasarlardan münhasıran Satıcı sorumludur. Satıcı, bu konudaki tüm taleplerini doğrudan taşıyıcı firmaya yönlendirecektir. Satıcı, Alıcı tarafından hasarlı olduğu sebebiyle iade edilen ürünleri teslim almakla yükümlüdür. Hasar tespit tutanağı tutulmamış olması sebebiyle ürünleri teslim almayı reddedemez. İade yapılması halinde, i-kirtasiye, kargo bedeli dahil toplam sipariş bedelini ve iade kargo bedelini, Satıcı'ya yapacağı ödemelerden takas yoluyla tahsil/mahsup edebilir.</p>

<p><strong>4.16.</strong> Alıcı, satın aldığı ürünün kargoya verildiği tarihten itibaren 7 (yedi) iş günü içinde ürünü inceleyecek ve onay verecektir. Alıcı bu süre içerisinde onay vermez ancak itirazda da bulunmazsa, ürüne onay vermiş sayılacaktır. Alıcı'nın Site üzerinde satın aldığı ürünlere ilişkin sebepsiz iade/cayma hakkı bulunmamaktadır. Alıcı, ürünü sadece mevzuatta öngörülen ayıp hükümleri kapsamında veya Satıcı'nın iade talebini kabul etmesi halinde iade edebilecektir.</p>

<p><strong>4.17.</strong> Alıcı tarafından satın alınan üründe gizli ayıp ortaya çıkması durumunda, Alıcı gizli ayıbı derhal Site aracılığıyla Satıcı'ya bildirmelidir. Satıcı, iade veya değişim talebini kabul etmekle yükümlüdür. Satıcı, dilediği takdirde gizli ayıp sebebiyle iade aldığı ürünü, teslim aldığı tarihten itibaren 15 (on beş) gün içerisinde yetkili servise göndererek inceleme yaptırabilir.</p>

<p><strong>4.18.</strong> Alıcı, iade talebinde bulunduysa, i-kirtasiye tarafından kendisine verilecek iade kodunu en geç 5 (beş) iş günü içerisinde kullanmak ve iadeyi bu süre içerisinde tamamlamakla yükümlüdür. Aksi takdirde iade kodu geçersiz hale gelecek ve Alıcı'nın iade hakkı sona erecektir.</p>

<p><strong>4.19.</strong> Alıcı, teslim aldığı siparişindeki bir üründe eksik/hasar/yanlış olması halinde, sadece o ürüne ilişkin bedel iadesi talep edebilir veya tüm siparişi iptal ederek bedel iadesi talep edebilir. i-kirtasiye, ilgili bedelleri Satıcı'ya yapacağı ödemelerden takas/mahsup yoluyla tahsil edebilir.</p>

<p><strong>4.20.</strong> Satıcı, iade talebi geldiğinde i-kirtasiye'nun kendisiyle iletişime geçme çabasına yanıt vermekle yükümlüdür. Satıcı, i-kirtasiye'dan gelen aramalara ve mesajlara 1 (bir) gün boyunca yanıt vermezse, Alıcı'nın iade talebi kabul edilmiş gibi işlem yapılacaktır.</p>

<p><strong>4.21.</strong> Satıcı; (i) ürünlerin alıcıya hasarlı ulaşması, (ii) yanlış veya eksik ürün gönderilmesi, (iii) son kullanma tarihi geçmiş ürün gönderilmesi hallerinde, stoklarında ürün bulunuyorsa Alıcı'nın değişim talebini yerine getirmekle yükümlüdür. Satıcı bu talebi haksız olarak reddeder veya 3 (üç) iş günü içinde ürünü göndermezse, işlem başına 500 TL (beş yüz Türk Lirası) tutarında cezai şart uygulanacaktır.</p>

<p><strong>4.22.</strong> Satıcı, Alıcı'nın iade talebini, ürünü kendisinden satın almadığını iddia ederek reddetme hakkına sahip değildir. Satıcı bunu ispat edemiyorsa iade talebini kabul etmekle yükümlüdür.</p>

<p><strong>4.23.</strong> Satıcı, Site üzerinde gerçekleştirdiği ÜTS'ye tabi olan bütün ürün satışlarına ilişkin olarak ürünlerin Alıcı'ya tesliminden önce ÜTS bildirimi yapmakla yükümlüdür. Aksi takdirde Alıcı'nın iade etme hakkı doğacaktır.</p>

<p><strong>4.24.</strong> i-kirtasiye, ilgili mevzuatlar kapsamında yetkili makamların talebi halinde, Üye'nin kendisinde bulunan bilgileri ilgili makamlarla paylaşabilecektir.</p>

<p><strong>4.25.</strong> Üye, www.i-kirtasiye.com'u hiçbir şekilde hukuka ve ahlaka aykırı biçimde kullanmayacaktır. Aşağıdaki haller hukuka aykırı kullanıma örnektir: 4.25.1. Sitenin, herhangi bir kişi adına veri tabanı, kayıt veya rehber yaratmak amacıyla kullanılması 4.25.2. Yanlış bilgiler veya başka bir kişinin bilgileri kullanılarak işlem yapılması, sahte üyelik hesapları oluşturulması 4.25.3. Yorumların ve puanlamaların site dışında manipülatif şekilde kullanılması 4.25.4. Siteye virüs veya zararlı teknoloji yayılması 4.25.5. Üyeler hakkında izinsiz bilgi toplanması 4.25.6. Otomatik programlar, robotlar veya veri madenciliği yazılımları ile i-kirtasiye'nun yazılı izni alınmaksızın site içeriklerinin kopyalanması</p>

<p><strong>4.26.</strong> Üye, www.i-kirtasiye.com'da işlem yaparken internet sitesine hiçbir surette zarar vermeyecek şekilde hareket etmekle yükümlüdür. Sağlayacağı tüm içeriğin zararlı yazılım, virüs ve benzeri unsurlar içermemesi için gerekli her türlü tedbiri aldığını kabul, beyan ve taahhüt eder.</p>

<p><strong>4.27.</strong> www.i-kirtasiye.com'un veya site içeriğinin Sözleşme ile belirlenen kullanım şartlarına veya mevzuata aykırı olarak kullanılması hukuka aykırı kabul edilir. i-kirtasiye'nun fazlaya dair tüm dava, talep ve tazminat hakları saklıdır.</p>

<p><strong>4.28.</strong> Üye, i-kirtasiye'nun kendisiyle iletişim kurmak için kullanacağı bilgileri güncel tutmakla mükelleftir. Güncellemediği takdirde doğabilecek zararlardan i-kirtasiye sorumlu tutulamaz. E-posta veya adres değişikliği 7 (yedi) gün içinde yazılı olarak bildirilmediği takdirde, mevcut adrese yapılacak tebligat geçerli sayılır.</p>

<p><strong>4.29.</strong> Üye'nin, i-kirtasiye'nun önceden yazılı onayını almaksızın işbu Sözleşme'deki haklarını veya yükümlülüklerini temlik etmesi mümkün değildir.</p>

<p><strong>4.30.</strong> Taraflar'dan birinin Sözleşme'deki herhangi bir hakkı kullanmaması ya da icra etmemesi, söz konusu haktan feragat edildiği anlamına gelmeyecek veya hakkın sonradan kullanılmasını engellemeyecektir.</p>

<p><strong>4.31.</strong> Mevzuatın veya kargo şirketinin belirlediği taşınması yasak ürünlerin Satıcı tarafından gönderilmesi halinde, i-kirtasiye'nun her türlü sorumluluk için Üye'ye rücu etme hakkı mevcuttur.</p>

<p><strong>4.32.</strong> i-kirtasiye; Üye tarafından Site'ye eklenen ürünleri ve/veya her türlü içeriği dilediği zaman ve sebep göstermeksizin yayından kaldırabilir. Bu durumda Üye herhangi bir tazminat talep edemez.</p>

<p><strong>4.33.</strong> Üye, Site üzerinde site dışına yönlendirici hiçbir URL adresi (link) paylaşamaz.</p>

<p><strong>4.34.</strong> i-kirtasiye, Site üzerinden üçüncü kişilerin sahip olduğu web sitelerine link verebilir. Bu linkler herhangi bir beyan veya garanti niteliği taşımamaktadır.</p>

<p><strong>4.35.</strong> Üye; i-kirtasiye'nun ticari itibarını zedeleyici, güvenilirliğini sarsıcı herhangi bir eylemde bulunmayacağını kabul eder. Aykırı davranış halinde i-kirtasiye Sözleşme'yi derhal feshedebilir. Üye, diğer Üyeler'i Site haricindeki internet sitelerine yönlendirmeyeceğini ve Site dışında işlem gerçekleştirmeyeceğini kabul eder.</p>

<p><strong>4.36.</strong> Üye, Site üzerinde kullandığı her türlü hukuka aykırı yorum, ilan ve paylaşım nedeniyle hukuken sorumlu olacağını kabul, beyan ve taahhüt eder.</p>

<p><strong>4.37.</strong> Üye'nin belirlediği kullanıcı ismi de Sözleşme hükümlerine tabi olup, Üye üçüncü şahısların haklarını ihlal etmeyen bir kullanıcı ismi belirleyeceğini kabul eder.</p>

<p><strong>4.38.</strong> Üye, Site üzerinde diğer Üyeler'e ve/veya ilanlara/ürünlere ilişkin i-kirtasiye'nun belirlediği koşullarda puan, değerlendirme ve yorum paylaşabilir. Bu puan ve yorumlar ilgili Üyeler'in görüşlerini yansıtmakta olup i-kirtasiye'nun herhangi bir sorumluluğu bulunmamaktadır.</p>

<p><strong>4.39.</strong> i-kirtasiye, belirlediği kriterlere göre Üye'nin puanını düşürebilir, üyeliğini askıya alabilir veya yayından kaldırabilir. Satıcı konumundaki Üye'nin belirli veya tüm ürünlerine satış kısıtlaması getirebilir.</p>

<p><strong>4.40.</strong> i-kirtasiye'nun Üye tarafından paylaşılan iletişim bilgileri ile Üye'ye ulaşamaması halinde, Satıcı'nın puanını düşürebilir, ürün ilanlarını yayından kaldırabilir ve satış kısıtlaması/yasağı getirebilir.</p>

<p><strong>4.41.</strong> Satıcı'nın orijinal olmayan ve/veya satışı yasak bir ürünü Site üzerinde satışa sunması halinde ve/veya bu sebeple i-kirtasiye'ya herhangi bir talep, dava veya idari ceza yöneltilmesi halinde Satıcı, i-kirtasiye'nun talebi üzerine 1.000.000 TL (bir milyon Türk Lirası) cezai şart bedeli ödeyecektir. i-kirtasiye, bu bedeli Satıcı'nın Site üzerindeki alacaklarından mahsup/takas edebilir.</p>

<p><strong>4.42.</strong> Aracı hizmet sağlayıcı olarak i-kirtasiye, yürürlükteki yasal mevzuat uyarınca Satıcı'nın vergilerine mahsuben stopaj kesintisi yapmakla yükümlü kılınmıştır. Satıcı, i-kirtasiye tarafından yapılacak ödemelerden mevzuata uygun stopaj/tevkifat yapılacağını kabul ve taahhüt eder.</p>

<p><strong>4.43.</strong> Satıcı, Ürünler'in tedarikini, kargolanmasını ve Alıcı'ya teslimini eksiksiz ifa etmekle yükümlüdür. Satıcı, kargolama işlemlerini Site üzerinde belirtilen kutu boyutunu kullanarak yapacaktır. Satıcı, siparişlerin kargolamasını sipariş tarihi ile aynı gün veya en geç ertesi gün içerisinde yapmakla yükümlüdür.</p>

<p><strong>4.44.</strong> i-kirtasiye, Üye ile ilgili hukuka aykırı bir durum olması veya şüphelenilmesi halinde, kayıtların ihlal oluşturmadığını Üye'den ispatlamasını isteyebilir.</p>

<p><strong>4.45.</strong> i-kirtasiye, bir işlemin çalıntı kredi kartı ile gerçekleştirildiğini veya suç amaçlı kullanıldığını tespit etmesi halinde, işlemi derhal iptal etme ve üyeliği askıya alma hakkına sahiptir.</p>

<h2>MADDE 5 – MALİ HÜKÜMLER</h2>

<p><strong>5.1.</strong> i-kirtasiye, Site üzerinden Satıcılar tarafından gerçekleştirilen her bir satış işlemi karşılığında satış bedeli üzerinden Sözleşme'nin başlangıç kısmında belirtilen oranda komisyona hak kazanacaktır. i-kirtasiye, komisyon oranını değiştirmek istediğinde, değişikliği yürürlüğe gireceği tarihten 30 (otuz) gün önce Site üzerinden ve e-posta yoluyla Satıcı'ya bildirecektir. Satıcı, yeni komisyon oranını kabul etmiyorsa, belirtilen süre içerisinde Sözleşme'yi tek taraflı olarak feshedebilir.</p>

<p><strong>5.2.</strong> Kargo bedeli, Site üzerinde belirtilen hesaplama yöntemine göre belirlenecek ve Satıcı tarafından ödenecektir. i-kirtasiye, Satıcı'ya yansıtılacak kargo bedellerini Satıcı'ya yapacağı ödemelerden takas yoluyla tahsil/mahsup edebilir.</p>

<p><strong>5.3.</strong> i-kirtasiye, www.i-kirtasiye.com üzerinden gerçekleştirilen satış bedellerinin tahsilatını bünyesindeki hizmetler aracılığıyla gerçekleştirmektedir. i-kirtasiye, kendi komisyonunu düştükten sonra kalan ürün bedellerinin Satıcılar'a aktarılmasından sorumludur.</p>

<p><strong>5.3.1. i-kirtasiye Cüzdan ile Erken Erişim Seçeneği.</strong> Satıcı, sipariş tamamlandıktan sonra hakedişlerini i-kirtasiye Cüzdan (Wallet) hesabına aktarmayı talep edebilir. Bu tutarlar münhasıran Site içi ticari operasyonlarda kullanılmak üzere tahsis edilir. Satıcı, i-kirtasiye Cüzdan'a aktarılan tutarlar üzerinde herhangi bir faiz veya getiri talep edemez.</p>

<p><strong>5.3.2. Banka Hesabına Ödeme Seçeneği.</strong> Hakedişlerin doğrudan banka hesabına talep edilmesi durumunda ödeme, siparişin alıcıya tesliminden itibaren en geç 26 (yirmi altı) iş günü içerisinde, Satıcı'nın Site üzerinde belirttiği banka hesabına yapılır.</p>

<p><strong>5.4.</strong> i-kirtasiye, Satıcı'nın Sözleşme'ye ve/veya mevzuata aykırı eylemlerinden makul seviyede şüphelendiğinde, Satıcı'ya yapacağı ödemeleri durdurabilir veya askıya alabilir.</p>

<p><strong>5.5.</strong> Satıcı, Site üzerinden gerçekleştirdiği her bir satış işlemine dair fatura keseceğini ve en geç ürünün Alıcı'ya teslimi anına kadar sipariş detay sayfasına yükleyeceğini kabul eder. Fatura kesmediği takdirde, Alıcı siparişi iptal etme ve ürünü iade etme hakkına sahiptir.</p>

<p><strong>5.6.</strong> Sözleşme'nin herhangi bir düzenlemesi doğrultusunda iade gerçekleştiğinde, i-kirtasiye, kargo bedeli dahil toplam sipariş bedelini ve iade kargo bedelini Satıcı'ya yapacağı ödemelerden takas yoluyla tahsil edebilir.</p>

<p><strong>5.7.</strong> Değişim işlemi gerçekleştiğinde, i-kirtasiye, geri gönderim ve yeni ürün kargo bedellerini Satıcı'ya yapacağı ödemelerden takas yoluyla tahsil/mahsup edebilir.</p>

<p><strong>5.8.</strong> İade gerçekleşeceğinde, Alıcı iade faturası düzenlemekle yükümlüdür. i-kirtasiye aracı hizmet sağlayıcı konumunda olduğundan iade faturası ile ilgili herhangi bir yükümlülüğü bulunmamaktadır.</p>

<p><strong>5.9.</strong> Üye'nin borçları sebebiyle i-kirtasiye'ya haciz bildirimi gelmesi halinde i-kirtasiye, Üye'nin üyeliğini/profilini ve satış sayfasını borçlar kapatılana kadar askıya alma hakkına sahiptir.</p>

<p><strong>5.10.</strong> Satıcı, yürürlükteki tüm yasal mevzuat kapsamındaki mali ve vergisel yükümlülüklerine uyacağını kabul eder. KDV oranı bilgisi ve diğer vergi oranlarının doğru olmasını sağlamakla münhasıran yükümlüdür.</p>

<p><strong>5.11.</strong> Satıcı, Site'ye üye olurken beyan ettiği VKN, TCKN ve diğer tüm bilgilerin doğru, eksiksiz ve güncel olduğunu taahhüt eder. Bu bilgilerin eksik veya hatalı olmasından kaynaklanan her türlü sorumluluk münhasıran Satıcı'ya aittir.</p>

<h2>MADDE 6 – KİŞİSEL VERİLER</h2>

<p>Üye'nin kişisel verilerinin işlenmesine ilişkin bilgilendirme, Site üzerinde yer alan Kişisel Verilerin İşlenmesine İlişkin Aydınlatma Metni uyarınca yapılmaktadır. Üye, Site'ye üye olmadan önce ilgili Aydınlatma Metni'ni okuduğunu, anladığını ve kişisel verileri hakkında aydınlatıldığını kabul, beyan ve taahhüt eder.</p>

<h2>MADDE 7 – FİKRİ MÜLKİYET HAKLARI</h2>

<p><strong>7.1.</strong> i-kirtasiye markası ve logosu, www.i-kirtasiye.com internet sitesinin, mobil uygulamasının tasarımı, yazılımı, alan adı ve bunlara ilişkin her türlü marka, patent, tasarım, logo, ticari takdim şekli, slogan ve diğer tüm içeriğin her türlü fikri mülkiyet hakkı münhasıran i-kirtasiye'nun mülkiyetindedir. Üye, i-kirtasiye'nun mülkiyetindeki fikri mülkiyet haklarını kullanamaz, kopyalayamaz, çoğaltamaz. i-kirtasiye ise Üye'nin ticari unvanını ve bilgilerini Hizmet'in sunulması ve tanıtım amacıyla kullanabilir.</p>

<p><strong>7.2.</strong> i-kirtasiye, fikri mülkiyet hakkının ihlal edildiğini iddia eden Üyeler tarafından yapılacak başvuruları inceleyecektir. i-kirtasiye, gerektiğinde ürün listelemelerini kaldırma ve üyelikleri askıya alma veya iptal etme hakkını saklı tutar.</p>

<p><strong>7.3.</strong> Üye, site üzerinde ekleyeceği her türlü görselin, yazılı içeriğin ve fikri mülkiyet haklarına konu öğelerin Sözleşme süresi boyunca i-kirtasiye üzerinde yayınlanmasına onay verdiğini kabul eder.</p>

<h2>MADDE 8 – SÖZLEŞME DEĞİŞİKLİKLERİ</h2>

<p>i-kirtasiye, tamamen kendi takdirine bağlı olmak üzere, www.i-kirtasiye.com'da yer alan her türlü politikayı, hüküm ve şartı uygun göreceği herhangi bir zamanda, yürürlükteki mevzuata aykırı olmamak kaydıyla Site'de ilan ederek tek taraflı olarak değiştirebilir. Üyelik Sözleşmesi'nde herhangi bir değişiklik yapılması halinde Üyeler, Site'de duyuru yapılarak ve e-posta gönderilerek bilgilendirilecektir. Değişiklikleri kabul etmeyen üyelerin, üyeliklerinin silinmesini talep etme hakları saklıdır. Bunun için Üyelerin destek@i-kirtasiye.com adresine e-posta atmaları ve Site kullanımına derhal son vermeleri gerekmektedir.</p>

<h2>MADDE 9 – MÜCBİR SEBEP</h2>

<p>Sözleşmenin imzalandığı tarihte mevcut olmayan veya öngörülmeyen, i-kirtasiye'nun kontrolü dışında gelişen, ortaya çıkmasıyla i-kirtasiye'nun borç ve sorumluluklarını kısmen ya da tamamen yerine getirmesini olanaksız kılan durumlar (doğal afet, salgın, savaş, terör, ayaklanma, mevzuat değişikliği, el koyma, grev, lokavt, üretim ve iletişim tesislerinde önemli ölçüde arıza vb.) mücbir sebep olarak kabul edilir. Mücbir sebep yaşayan taraf, durumun öğrenilmesini izleyen 3 (üç) gün içinde diğer tarafa yazılı bildirimde bulunacaktır. Mücbir sebep durumunun 30 (otuz) gün süreyle devam etmesi halinde, taraflardan her birinin tek taraflı fesih hakkı doğacaktır.</p>

<h2>MADDE 10 – UYUŞMAZLIKLARIN ÇÖZÜMÜ</h2>

<p><strong>10.1.</strong> Satılan mal veya hizmete ilişkin sorumluluk bizzat Satıcı'ya aittir. Alıcılar, şikayetlerini Satıcılara doğrudan veya i-kirtasiye aracılığıyla iletebilirler. Şikayetin i-kirtasiye'ya iletilmesi halinde i-kirtasiye sorunun çözülmesi için mümkün olan tüm desteği sağlayacaktır.</p>

<p><strong>10.2.</strong> İşbu sözleşmeden kaynaklanabilecek ihtilaflarda, İstanbul (Çağlayan) Mahkemeleri ve İcra Daireleri yetkilidir.</p>

<h2>MADDE 11 – BİLDİRİMLER VE DELİL SÖZLEŞMESİ</h2>

<p>İşbu Sözleşme kapsamında Taraflar arasındaki yazışmalar, mevzuattaki zorunlu haller dışında, e-posta aracılığıyla yapılacaktır. Üye, Sözleşme'den doğabilecek ihtilaflarda i-kirtasiye'nun resmi defter ve ticari kayıtlarıyla, veri tabanında ve sunucularında tuttuğu elektronik bilgilerin ve bilgisayar kayıtlarının bağlayıcı, kesin ve münhasır delil teşkil edeceğini, bu maddenin HMK md. 193 anlamında delil sözleşmesi niteliğinde olduğunu kabul, beyan ve taahhüt eder.</p>

<h2>MADDE 12 – SÖZLEŞMENİN İHLALİ VE FESHİ</h2>

<p><strong>12.1.</strong> İşbu Sözleşme ve/veya yürürlükteki mevzuat hükümlerinin ihlali halinde i-kirtasiye'nun zararlarının tazmini dahil tüm hakları saklı kalmak üzere, Sözleşmeyi tek taraflı olarak derhal feshetme hakkı bulunmaktadır. Bu fesihten dolayı Üye herhangi bir talepte bulunamaz. i-kirtasiye, fesih yerine Satıcı'nın puanını düşürebilir, ilanlarını kaldırabilir veya satış kısıtlaması getirebilir. i-kirtasiye, Üye'nin aykırı eylemleri nedeniyle maruz kalabileceği her türlü zararı, masrafı, tazminatı ve idari para cezasını Üye'ye rücu edecektir. i-kirtasiye dilerse bu bedelleri Üye'nin alacaklarından mahsup/takas edebilir.</p>

<p><strong>12.2.</strong> İşbu Sözleşme'nin herhangi bir hükmünün geçersizliği, geri kalan hükümlerin yürürlüğünü etkilemeyecektir.</p>

<p><strong>12.3.</strong> Taraflar'ın ticari koşullar veya Sözleşme düzenlemeleri hakkında mutabık kalamadıkları durumda, her iki Taraf da fesih bildirimi ile Sözleşme'yi tek taraflı ve derhal feshedebilir.</p>

<p><strong>12.4.</strong> Üye'nin kendi isteği ile üyelik hesabını kapatması halinde işbu Sözleşme kendiliğinden feshedilmiş sayılacaktır. Ancak Üye'nin tüm sorumlulukları süresiz olarak devam edecektir.</p>

<p><strong>12.5.</strong> Kırtasiyeci Üye'nin ruhsatının sona ermesi halinde, Üye en geç bu tarih itibariyle Site üzerindeki üyeliğine son verecektir.</p>

<p><strong>12.6.</strong> i-kirtasiye, Sözleşme'nin herhangi bir sebeple sona ermesi halinde, Üye'nin hak kazandığı bedelleri, i-kirtasiye Cüzdan bakiyesi dahil olmak üzere, öncelikle Üye'nin borçlarına (komisyon, kargo bedelleri, vergi kesintileri vb.) mahsup eder. Kalan tutar, siparişin alıcıya tesliminden itibaren en geç 26 (yirmi altı) iş günü içerisinde Satıcı'nın banka hesabına ödenir.</p>

<h2>MADDE 13 – TAKAS VE MAHSUP HAKKI</h2>

<p>i-kirtasiye, işbu Sözleşme kapsamında Üye'den olan herhangi bir alacağına karşılık, Üye'nin i-kirtasiye'dan doğmuş veya doğacak her türlü alacağını, tek taraflı olarak takas ve mahsup etme hakkını haizdir.</p>

<h2>MADDE 14 – YÜRÜRLÜK</h2>

<p>İşbu sözleşmenin onaylanması ile yukarıdaki tüm şart ve koşullar kayıtsız olarak kabul edilmiş olup, 14 (on dört) maddeden ibaret bu Sözleşme, Üye tarafından tamamıyla okunarak elektronik ortamda onaylanmak suretiyle akdedilmiş ve yürürlüğe girmiş sayılır.</p>

<p><strong>Sözleşme Tarihi:</strong> {{ $date }}</p>

<div class="signature-section">
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">i-kirtasiye</div>
                <div class="signature-info">İstanbul Vitamin Kozmetik Ve Gıda<br>Takviyeleri Pazarlama Ticaret Ltd. Şti.</div>
                <div class="signature-box">
                    <p>Kaşe / İmza</p>
                </div>
                <div class="signature-date">Tarih: {{ $date }}</div>
            </td>
            <td>
                <div class="signature-title">ÜYE</div>
                <div class="signature-info">{{ $member_name }}</div>
                <div class="signature-box">
                    <p>Kaşe / İmza</p>
                </div>
                <div class="signature-date">Tarih: ....../....../............</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <p>Bu sözleşme {{ $date }} tarihinde elektronik ortamda oluşturulmuştur.</p>
    <p>İşbu sözleşme 2 (iki) nüsha olarak düzenlenmiş olup, her iki tarafça imzalanarak birer nüshası taraflarda kalmıştır.</p>
</div>

</body>
</html>
