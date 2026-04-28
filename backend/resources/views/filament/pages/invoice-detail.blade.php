<div style="padding: 0.5rem 0;">
    {{-- Status Badge --}}
    <div style="margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
        @if($invoice->erp_status === 'synced')
            <span style="padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background: rgba(16,185,129,0.15); color: #34d399;">Fatura Olusturuldu</span>
        @elseif($invoice->erp_status === 'failed')
            <span style="padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background: rgba(239,68,68,0.15); color: #f87171;">Basarisiz</span>
        @else
            <span style="padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background: rgba(245,158,11,0.15); color: #fcd34d;">Fatura Kesilmedi</span>
        @endif

        <span style="padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background: rgba(99,102,241,0.15); color: #a5b4fc;">
            {{ match($invoice->type) { 'seller' => 'Satis Faturasi', 'commission' => 'Komisyon Faturasi', 'tax' => 'Vergi Faturasi', 'shipping' => 'Kargo Faturasi', default => 'Fatura' } }}
        </span>
    </div>

    {{-- Info Grid --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 0.875rem;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">Siparis No</p>
            <p style="font-size: 0.9rem; color: white; font-weight: 600; margin: 0.25rem 0 0 0;">{{ $invoice->order?->order_number ?? '-' }}</p>
        </div>
        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 0.875rem;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">Satici</p>
            <p style="font-size: 0.9rem; color: white; font-weight: 600; margin: 0.25rem 0 0 0;">{{ $invoice->seller?->business_name ?? $invoice->seller?->name ?? '-' }}</p>
        </div>
        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 0.875rem;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">Olusturulma Tarihi</p>
            <p style="font-size: 0.9rem; color: white; font-weight: 600; margin: 0.25rem 0 0 0;">{{ $invoice->created_at?->format('d.m.Y H:i') }}</p>
        </div>
        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 0.875rem;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">ERP Senkronizasyon</p>
            <p style="font-size: 0.9rem; color: white; font-weight: 600; margin: 0.25rem 0 0 0;">{{ $invoice->erp_synced_at?->format('d.m.Y H:i') ?? 'Henuz senkronize edilmedi' }}</p>
        </div>
    </div>

    {{-- Amounts --}}
    <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.25rem;">
        <p style="font-size: 0.75rem; font-weight: 700; color: white; margin: 0 0 0.75rem 0;">Tutar Bilgileri</p>
        <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
            <span style="color: #9ca3af; font-size: 0.85rem;">Ara Toplam</span>
            <span style="color: white; font-weight: 600; font-size: 0.85rem;">{{ number_format((float)$invoice->subtotal, 2, ',', '.') }} TL</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
            <span style="color: #9ca3af; font-size: 0.85rem;">KDV (%{{ $invoice->tax_rate }})</span>
            <span style="color: white; font-weight: 600; font-size: 0.85rem;">{{ number_format((float)$invoice->tax_amount, 2, ',', '.') }} TL</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0 0 0; margin-top: 0.25rem;">
            <span style="color: white; font-weight: 700; font-size: 0.9rem;">Toplam</span>
            <span style="color: #34d399; font-weight: 800; font-size: 1rem;">{{ number_format((float)$invoice->total_amount, 2, ',', '.') }} TL</span>
        </div>
    </div>

    {{-- Items --}}
    @if($invoice->items && count($invoice->items) > 0)
        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.25rem;">
            <p style="font-size: 0.75rem; font-weight: 700; color: white; margin: 0 0 0.75rem 0;">Fatura Kalemleri</p>
            @foreach($invoice->items as $item)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; {{ !$loop->last ? 'border-bottom: 1px solid rgba(255,255,255,0.06);' : '' }}">
                    <div style="flex: 1;">
                        <p style="color: {{ !empty($item['is_deduction']) ? '#fbbf24' : '#d1d5db' }}; font-size: 0.85rem; font-weight: 500; margin: 0;">
                            {{ $item['product_name'] }}
                            @if(!empty($item['is_deduction']))
                                <span style="font-size: 0.65rem; color: #9ca3af; margin-left: 0.25rem;">(Kesinti)</span>
                            @endif
                        </p>
                        <p style="color: #6b7280; font-size: 0.7rem; margin: 0.125rem 0 0 0;">{{ $item['quantity'] }} adet x {{ number_format($item['unit_price'], 2, ',', '.') }} TL &middot; KDV %{{ $item['vat_rate'] ?? 20 }}</p>
                    </div>
                    <span style="color: {{ !empty($item['is_deduction']) ? '#fbbf24' : 'white' }}; font-weight: 600; font-size: 0.85rem;">{{ number_format($item['total_price'], 2, ',', '.') }} TL</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ERP URL --}}
    @if($invoice->erp_invoice_url)
        <div style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 0.5rem; padding: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 18px; height: 18px; color: #34d399; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <a href="{{ $invoice->erp_invoice_url }}" target="_blank" style="color: #6ee7b7; font-size: 0.85rem; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">BizimHesap'ta Goruntule</a>
        </div>
    @endif

    {{-- ERP Error --}}
    @if($invoice->erp_status === 'failed' && $invoice->erp_error)
        <div style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; padding: 0.875rem; margin-top: 0.75rem;">
            <p style="font-size: 0.75rem; font-weight: 600; color: #f87171; margin: 0 0 0.25rem 0;">Hata Detayi</p>
            <p style="font-size: 0.8rem; color: #fca5a5; margin: 0;">{{ $invoice->erp_error }}</p>
        </div>
    @endif
</div>
