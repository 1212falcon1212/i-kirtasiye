<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Order;
use App\Models\SellerBankAccount;
use App\Models\SellerDocument;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PDF; // Barryvdh\DomPDF\Facade\Pdf

class LegalController extends Controller
{
    /**
     * Get specific legal document text (HTML).
     * Tries to load from pages table first, falls back to static text.
     */
    public function getDocument(string $slug)
    {
        $page = \Illuminate\Support\Facades\Cache::remember("legal.page.{$slug}", 30 * 60, function () use ($slug) {
            return \App\Models\Page::published()->where('slug', $slug)->first();
        });

        if ($page) {
            return response()->json([
                'content' => $page->content,
                'version' => $page->updated_at->format('Y.m.d'),
                'title' => $page->title,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ]);
        }

        // Fallback: static texts for slugs not yet in database
        $text = match ($slug) {
            'mesafeli-satis-sozlesmesi' => $this->getDistanceSalesText(),
            'iptal-iade' => $this->getCancellationText(),
            'uyelik-sozlesmesi' => $this->getMembershipAgreementText(),
            default => null,
        };

        if (! $text) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return response()->json(['content' => $text, 'version' => '1.0']);
    }

    /**
     * Record user approval of a contract.
     */
    public function approveContract(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'version' => 'required|string',
        ]);

        Contract::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'version' => $request->version,
            'ip_address' => $request->ip(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Sözleşme onayı kaydedildi.']);
    }

    /**
     * Siparis bazli mesafeli satis sozlesmesi PDF olusturur.
     * Hem alici hem de satici erisebilir.
     */
    public function generateSalesContract(Request $request, int $orderId)
    {
        $user = $request->user();
        $order = Order::with(['items.product', 'user', 'subOrders'])->findOrFail($orderId);

        // Yetki kontrolu: alici, satici veya super admin
        $isBuyer = $order->user_id === $user->id;
        $isSeller = $order->items->contains('seller_id', $user->id);

        if (! $isBuyer && ! $isSeller && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Bu siparişe erişim yetkiniz yok.'], 403);
        }

        // Satici belirleme: query param > talep eden satici > ilk satici
        $sellerId = $request->query('seller_id')
            ? (int) $request->query('seller_id')
            : ($isSeller ? $user->id : $order->items->first()?->seller_id);
        $seller = User::find($sellerId);
        $buyer = $order->user;

        // Satici ticari bilgileri (banka hesabindan fallback)
        $sellerBank = $seller ? SellerBankAccount::where('seller_id', $seller->id)->first() : null;

        // Bu saticiya ait kalemleri filtrele
        $sellerItems = $order->items->where('seller_id', $sellerId);

        $items = $sellerItems->map(fn ($item) => [
            'name' => $item->product?->name ?? ('Ürün #'.$item->product_id),
            'quantity' => $item->quantity,
            'unit_price' => number_format($item->unit_price, 2, ',', '.'),
            'total_price' => number_format($item->total_price, 2, ',', '.'),
        ])->values()->toArray();

        $subOrder = $order->subOrders->firstWhere('seller_id', $sellerId);
        $shippingCost = (float) ($subOrder?->shipping_cost ?? 0);
        $itemsTotal = $sellerItems->sum('total_price');
        $grandTotal = $itemsTotal + $shippingCost;

        // Shipping address formatlama (array/json string -> string)
        $shippingAddr = $order->shipping_address;
        // JSON string ise decode et
        if (is_string($shippingAddr) && str_starts_with(trim($shippingAddr), '{')) {
            $decoded = json_decode($shippingAddr, true);
            if ($decoded) {
                $shippingAddr = $decoded;
            }
        }
        $deliveryAddress = '-';
        if (is_array($shippingAddr)) {
            $parts = array_filter([
                $shippingAddr['address'] ?? null,
                $shippingAddr['district'] ?? null,
                $shippingAddr['city'] ?? null,
                $shippingAddr['postal_code'] ?? null,
            ]);
            $deliveryAddress = implode(', ', $parts) ?: '-';
        } elseif (is_string($shippingAddr) && $shippingAddr !== '') {
            $deliveryAddress = $shippingAddr;
        }

        // Alici profil adresi
        $buyerAddressParts = array_filter([
            $buyer?->address,
            $buyer?->district,
            $buyer?->city,
        ]);
        $buyerAddress = implode(', ', $buyerAddressParts) ?: $deliveryAddress;

        // Satici profil adresi
        $sellerAddressParts = array_filter([
            $seller?->address,
            $seller?->district,
            $seller?->city,
        ]);
        $sellerAddress = implode(', ', $sellerAddressParts) ?: '-';

        $data = [
            'contract_number' => 'SS-'.$order->order_number,
            'order_number' => $order->order_number,
            'date' => $order->created_at->format('d.m.Y'),

            // Satici bilgileri (user alanlari oncelikli, banka hesabi fallback)
            'seller_trade_name' => $seller?->trade_name ?? $seller?->business_name ?? '-',
            'seller_mersis' => $seller?->mersis_no ?? $sellerBank?->mersis_number ?? '-',
            'seller_tax_number' => $seller?->tax_number ?? $sellerBank?->tax_id ?? '-',
            'seller_tax_office' => $seller?->tax_office ?? $sellerBank?->tax_office ?? '-',
            'seller_kep' => $seller?->kep_address ?? $sellerBank?->kep_address ?? '-',
            'seller_address' => $sellerAddress,
            'seller_phone' => $seller?->phone ?? $sellerBank?->phone ?? '-',
            'seller_email' => $seller?->email ?? '-',

            // Alici bilgileri
            'buyer_name' => $buyer?->trade_name ?? $buyer?->business_name ?? '-',
            'buyer_tax_number' => $buyer?->tax_number ?? '-',
            'buyer_tax_office' => $buyer?->tax_office ?? '-',
            'buyer_address' => $buyerAddress,
            'buyer_phone' => $buyer?->phone ?? '-',
            'buyer_email' => $buyer->email ?? '-',

            // Urunler
            'items' => $items,
            'shipping_cost' => $shippingCost,
            'grand_total' => number_format($grandTotal, 2, ',', '.'),

            // Odeme
            'payment_method' => match ($order->payment_method) {
                'credit_card' => 'Kredi Kartı',
                'bank_transfer' => 'Havale/EFT',
                default => $order->payment_method ?? '-',
            },
            'payment_status' => $order->payment_status === 'paid' ? 'Ödendi' : 'Bekliyor',

            // Teslimat
            'delivery_address' => $deliveryAddress,
        ];

        // Sozlesme olusturma kaydini tut
        Contract::create([
            'user_id' => $user->id,
            'type' => 'b2b_sales',
            'version' => '1.0',
            'ip_address' => $request->ip(),
            'approved_at' => now(),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'seller_id' => $sellerId,
                'buyer_id' => $buyer?->id,
                'total' => $grandTotal,
            ],
        ]);

        $pdf = app('dompdf.wrapper')->loadView('legal.b2b-contract', $data);

        return $pdf->download("satis-sozlesmesi-{$order->order_number}.pdf");
    }

    /**
     * Kayit sozlesmesi PDF'ini olusturur ve indirir.
     */
    public function downloadRegistrationContract(Request $request)
    {
        $user = $request->user();

        // Komisyon bilgilerini Hizmet Bedeli Ayarlarindan al
        $feeMode = Setting::getValue('commission.fee_mode', 'flat');
        $commissionRate = match ($feeMode) {
            'flat' => 'Sipariş başına sabit '.number_format((float) Setting::getValue('commission.flat_service_fee', 50), 0, ',', '.').' ₺ hizmet bedeli',
            'percentage' => '%'.number_format((float) Setting::getValue('commission.commission_percentage', 10), 1).' komisyon',
            'category' => 'Kategori bazlı komisyon (güncel oranlar için www.i-kirtasiye.com)',
            default => '-',
        };

        // Adres birlestirme
        $addressParts = array_filter([
            $user->address,
            $user->district,
            $user->city,
        ]);
        $fullAddress = implode(', ', $addressParts) ?: '-';

        $data = [
            'member_name' => $user->trade_name ?? $user->business_name ?? '-',
            'member_address' => $fullAddress,
            'commission_rate' => $commissionRate,
            'shipping_policy' => 'Kargo ücreti satıcı tarafından karşılanır',
            'date' => now()->format('d.m.Y'),
        ];

        $pdf = app('dompdf.wrapper')->loadView('legal.registration-contract', $data);

        return $pdf->download('uyelik-sozlesmesi.pdf');
    }

    /**
     * Imzali kayit sozlesmesini yukler.
     */
    public function uploadSignedContract(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $user = $request->user();

        // Onaylanmis sozlesme kontrolu
        $existing = $user->sellerDocuments()
            ->where('type', 'sozlesme')
            ->where('status', 'approved')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'Onaylanmış bir sözleşmeniz zaten mevcut.',
            ], 422);
        }

        // Eski bekleyen/reddedilen sozlesmeleri sil
        $oldDocs = $user->sellerDocuments()->where('type', 'sozlesme')->get();
        foreach ($oldDocs as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        // Yeni dosyayi kaydet
        $file = $request->file('file');
        $path = $file->store('documents/'.$user->id, 'public');

        $document = SellerDocument::create([
            'user_id' => $user->id,
            'type' => 'sozlesme',
            'status' => 'pending',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        // Sozlesme meta bilgilerini kullaniciya kaydet
        $user->update([
            'contract_signed_at' => now(),
            'contract_ip' => $request->ip(),
            'contract_user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sözleşme yüklendi. Admin onayından sonra aktif olacaktır.',
            'document' => [
                'id' => $document->id,
                'type' => $document->type,
                'type_label' => $document->type_label,
                'original_name' => $document->original_name,
                'status' => $document->status,
                'status_label' => $document->status_label,
                'created_at' => $document->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    // --- Static Texts (Fallback for contracts not yet in CMS) ---

    private function getDistanceSalesText(): string
    {
        return <<<'HTML'
<h2>Mesafeli Satış Sözleşmesi</h2>

<h3>Madde 1 - Taraflar</h3>
<h4>1.1. Satıcı</h4>
<p><strong>Unvan:</strong> Platform üzerinde ilgili ürünü satışa sunan satıcı kırtasiye<br>
<strong>Platform:</strong> i-kirtasiye B2B Kırtasiye Pazaryeri (www.i-kirtasiye.com)<br>
<strong>E-posta:</strong> destek@i-kirtasiye.com</p>
<h4>1.2. Alıcı</h4>
<p>Platform üzerinden sipariş veren ve Vergi No doğrulaması yapılmış üye kırtasiye.</p>

<h3>Madde 2 - Sözleşmenin Konusu</h3>
<p>İşbu sözleşmenin konusu, Alıcı'nın i-kirtasiye B2B Kırtasiye Pazaryeri üzerinden elektronik ortamda siparişini verdiği ürün/ürünlerin satışı ve teslimine ilişkin olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>

<h3>Madde 3 - Genel Hükümler</h3>
<p>Alıcı, sözleşme konusu ürünün temel nitelikleri, satış fiyatı, ödeme şekli ve teslimata ilişkin ön bilgileri okuyup bilgi sahibi olduğunu ve elektronik ortamda gerekli onayı verdiğini kabul eder.</p>

<h3>Madde 4 - Teslimat</h3>
<p>Teslimat, anlaşmalı kargo firmaları aracılığıyla Alıcı'nın belirttiği adrese yapılır. Teslimat süresi siparişin onayından itibaren yasal 30 günlük süreyi aşmaz.</p>

<h3>Madde 5 - Cayma Hakkı</h3>
<p>Alıcı, ürünün tesliminden itibaren 14 gün içinde cayma hakkını kullanabilir. İlaç ve tıbbi ürünler, ambalajı açılmış hijyen ürünleri ile çabuk bozulabilir ürünlerde cayma hakkı kullanılamaz.</p>

<h3>Madde 6 - Uyuşmazlık</h3>
<p>B2B işlemlerde İstanbul Mahkemeleri ve İcra Daireleri yetkilidir.</p>

<p><strong>Son güncelleme:</strong> Nisan 2026</p>
HTML;
    }

    private function getCancellationText(): string
    {
        return <<<'HTML'
<h2>İptal ve İade Koşulları</h2>

<h3>Madde 1 - Sipariş İptali</h3>
<p>Siparişin kargoya verilmesine kadar iptal edilebilir. Kargoya verilen siparişlerde ürünün teslim alınmasının ardından iade prosedürü uygulanır.</p>

<h3>Madde 2 - Cayma Hakkı</h3>
<p>Alıcı, ürünün teslim tarihinden itibaren 14 gün içinde cayma hakkını kullanabilir. Ürünün ambalajı açılmamış ve orijinal durumunda olmalıdır.</p>

<h3>Madde 3 - Cayma Hakkı İstisnaları</h3>
<ul>
<li>İlaç ve kırtasiyecilik ürünleri (Sağlık Bakanlığı düzenlemeleri kapsamında)</li>
<li>Ambalajı açılmış hijyen ürünleri</li>
<li>Çabuk bozulabilen ve son kullanma tarihi geçme ihtimali olan ürünler</li>
<li>Soğuk zincir gerektiren ürünler</li>
</ul>

<h3>Madde 4 - İade Kargo Ücreti</h3>
<ul>
<li><strong>Cayma hakkı:</strong> Satıcı karşılar</li>
<li><strong>Hasarlı/hatalı ürün:</strong> Satıcı karşılar</li>
<li><strong>Alıcı kaynaklı:</strong> Alıcı karşılar</li>
</ul>

<h3>Madde 5 - Geri Ödeme</h3>
<p>İade onaylanan ürünlerin bedeli, ürünün Satıcı'ya ulaşmasını takiben en geç 14 gün içinde orijinal ödeme yöntemine iade edilir.</p>

<h3>Madde 6 - İletişim</h3>
<p>İptal ve iade talepleriniz için: destek@i-kirtasiye.com</p>

<p><strong>Son güncelleme:</strong> Nisan 2026</p>
HTML;
    }

    private function getMembershipAgreementText(): string
    {
        return <<<'HTML'
<h2>Üyelik Sözleşmesi</h2>

<h3>Madde 1 - Taraflar</h3>
<p>İşbu Sözleşme, i-kirtasiye B2B Kırtasiye Pazaryeri ("Platform") ile üyelik başvurusunda bulunan ve Vergi No doğrulaması tamamlanmış kırtasiye ("Üye") arasında elektronik ortamda akdedilmiştir.</p>

<h3>Madde 2 - Sözleşmenin Konusu</h3>
<p>Üye'nin Platform'u kullanmasına ilişkin koşulları, tarafların karşılıklı hak ve yükümlülüklerini, hizmet bedellerini ve sorumluluk esaslarını düzenler.</p>

<h3>Madde 3 - Üyelik Koşulları</h3>
<ul>
<li>Aktif kırtasiye ruhsatına ve geçerli Vergi numarasına sahip olmak</li>
<li>Vergi levhası ve kırtasiye ruhsat belgelerini sunmak</li>
<li>Doğru ve güncel bilgiler beyan etmek</li>
<li>KVKK Aydınlatma Metni ve Gizlilik Politikası'nı kabul etmek</li>
</ul>

<h3>Madde 4 - Üye'nin Yükümlülükleri</h3>
<ul>
<li>Hesap bilgilerinin güvenliğini sağlamak</li>
<li>Mevzuata uygun ürün ticareti yapmak</li>
<li>Ürün kalitesinden ve son kullanma tarihlerinden sorumlu olmak</li>
<li>Siparişleri zamanında ve eksiksiz teslim etmek</li>
<li>Fatura ve vergisel yükümlülüklerini yerine getirmek</li>
</ul>

<h3>Madde 5 - Platform'un Yükümlülükleri</h3>
<ul>
<li>Güvenli ticaret ortamı ve teknik altyapı sağlamak</li>
<li>Ödeme güvenliğini sağlamak</li>
<li>Üye bilgilerini KVKK kapsamında korumak</li>
<li>Destek taleplerini makul sürede yanıtlamak</li>
</ul>

<h3>Madde 6 - Hizmet Bedeli</h3>
<p>Platform, aracılık hizmeti karşılığında Satıcı'dan hizmet bedeli tahsil eder. Güncel tarifeler Platform'da yayımlanır.</p>

<h3>Madde 7 - Sözleşmenin Feshi</h3>
<p>Taraflar yazılı bildirimle feshedebilir. Kural ihlali, sahte bilgi veya ruhsat iptali halinde Platform üyeliği derhal sonlandırabilir.</p>

<h3>Madde 8 - Uyuşmazlık</h3>
<p>İstanbul Mahkemeleri ve İcra Daireleri yetkilidir. Sözleşme Türkiye Cumhuriyeti hukukuna tabidir.</p>

<h3>Madde 9 - Yürürlük</h3>
<p>İşbu Sözleşme, Üye'nin elektronik ortamda onay vermesi ile yürürlüğe girer.</p>

<p><strong>Son güncelleme:</strong> Nisan 2026</p>
HTML;
    }
}
