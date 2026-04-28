<x-filament-panels::page>
<div class="sp-page">
<style>
.sp-page {
    --sp-bg-card: rgba(255,255,255,0.04);
    --sp-bg-muted: rgba(255,255,255,0.03);
    --sp-bg-subtle: rgba(255,255,255,0.05);
    --sp-border: rgba(255,255,255,0.08);
    --sp-border-subtle: rgba(255,255,255,0.06);
    --sp-border-faint: rgba(255,255,255,0.04);
    --sp-border-med: rgba(255,255,255,0.1);
    --sp-text: #ffffff;
    --sp-text-2: #d1d5db;
    --sp-row-hover: rgba(255,255,255,0.03);
    --sp-btn-hover: rgba(255,255,255,0.1);
    --sp-btn-bg: rgba(255,255,255,0.05);
    --sp-thead-bg: rgba(255,255,255,0.04);
}
html:not(.dark) .sp-page {
    --sp-bg-card: #ffffff;
    --sp-bg-muted: #f9fafb;
    --sp-bg-subtle: #f3f4f6;
    --sp-border: #e5e7eb;
    --sp-border-subtle: #f0f0f0;
    --sp-border-faint: #f3f4f6;
    --sp-border-med: #d1d5db;
    --sp-text: #111827;
    --sp-text-2: #374151;
    --sp-row-hover: #f9fafb;
    --sp-btn-hover: #e5e7eb;
    --sp-btn-bg: #f3f4f6;
    --sp-thead-bg: #f9fafb;
}
</style>
    @php $stats = $this->getSummaryStats(); @endphp

    @if($this->detailSubOrderId)
        {{-- ═══════ DETAIL VIEW ═══════ --}}
        @php $detail = $this->getDetailData(); @endphp

        @if($detail)
            {{-- Back --}}
            <div style="margin-bottom: 1.25rem;">
                <button wire:click="backToList" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 500; color: #9ca3af; background: var(--sp-btn-bg); border: 1px solid var(--sp-border-med); border-radius: 0.75rem; cursor: pointer;" onmouseover="this.style.background='var(--sp-btn-hover)'" onmouseout="this.style.background='var(--sp-btn-bg)'">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Odeme Listesine Don
                </button>
            </div>

            {{-- Header --}}
            <div style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: 0.75rem; background: rgba(16,185,129,0.2); display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 24px; height: 24px; color: #34d399;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <p style="font-size: 0.65rem; color: #6b7280; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">Odeme Detaylari</p>
                        <p style="font-size: 1.25rem; font-weight: 800; color: white; margin: 0;">#{{ $detail['order_number'] }}</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <span style="padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background: rgba(245,158,11,0.15); color: #fcd34d;">Beklemede</span>
                    <span style="padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background: rgba(245,158,11,0.15); color: #fcd34d;">Beklemede</span>
                </div>
            </div>

            {{-- Info Cards --}}
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: var(--sp-bg-card); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                        <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Magaza</span>
                    </div>
                    <p style="font-size: 1rem; font-weight: 700; color: var(--sp-text); margin: 0;">{{ $detail['seller_name'] }}</p>
                </div>
                <div style="background: var(--sp-bg-card); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                        <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Siparis Tarihi</span>
                    </div>
                    <p style="font-size: 1rem; font-weight: 700; color: var(--sp-text); margin: 0;">{{ $detail['order_date'] }}</p>
                    <p style="font-size: 0.8rem; color: #6b7280; margin: 0.125rem 0 0 0;">{{ $detail['order_time'] }}</p>
                </div>
                <div style="background: var(--sp-bg-card); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                        <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Musteri</span>
                    </div>
                    <p style="font-size: 1rem; font-weight: 700; color: var(--sp-text); margin: 0;">{{ $detail['buyer_name'] }}</p>
                    <p style="font-size: 0.8rem; color: #6b7280; margin: 0.125rem 0 0 0;">{{ $detail['buyer_phone'] }}</p>
                </div>
                <div style="background: var(--sp-bg-card); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                        <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Odeme Yontemi</span>
                    </div>
                    <p style="font-size: 1rem; font-weight: 700; color: var(--sp-text); margin: 0;">{{ $detail['payment_method'] }}</p>
                </div>
            </div>

            {{-- Two Column Layout --}}
            <div style="display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem;">
                {{-- Left --}}
                <div>
                    {{-- Products --}}
                    <div style="background: var(--sp-bg-muted); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                            <p style="font-size: 0.9rem; font-weight: 700; color: var(--sp-text); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                Siparis Urunleri
                            </p>
                            <span style="padding: 0.25rem 0.625rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; background: rgba(99,102,241,0.15); color: #a5b4fc;">{{ count($detail['items']) }} urun</span>
                        </div>

                        @foreach($detail['items'] as $item)
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.875rem 0; {{ !$loop->last ? 'border-bottom: 1px solid var(--sp-border-subtle);' : '' }}">
                                <div style="width: 52px; height: 52px; border-radius: 0.5rem; background: var(--sp-bg-subtle); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                    @if($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" style="width: 100%; height: 100%; object-fit: cover;" alt="">
                                    @else
                                        <svg style="width: 22px; height: 22px; color: #4b5563;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 0.85rem; font-weight: 500; color: var(--sp-text-2); margin: 0;">{{ $item['product_name'] }}</p>
                                </div>
                                <div style="text-align: center; min-width: 50px;">
                                    <p style="font-size: 0.6rem; color: #6b7280; margin: 0; text-transform: uppercase;">Adet</p>
                                    <p style="font-size: 0.9rem; font-weight: 700; color: var(--sp-text); margin: 0.125rem 0 0 0;">{{ $item['quantity'] }}</p>
                                </div>
                                <div style="text-align: right; min-width: 75px;">
                                    <p style="font-size: 0.6rem; color: #6b7280; margin: 0; text-transform: uppercase;">Birim</p>
                                    <p style="font-size: 0.85rem; font-weight: 600; color: var(--sp-text-2); margin: 0.125rem 0 0 0;">{{ number_format($item['unit_price'], 2, ',', '.') }} ₺</p>
                                </div>
                                <div style="text-align: right; min-width: 85px;">
                                    <p style="font-size: 0.6rem; color: #6b7280; margin: 0; text-transform: uppercase;">Toplam</p>
                                    <p style="font-size: 0.9rem; font-weight: 700; color: var(--sp-text); margin: 0.125rem 0 0 0;">{{ number_format($item['total_price'], 2, ',', '.') }} ₺</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Bank Info --}}
                    <div style="background: var(--sp-bg-muted); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.5rem;">
                        <p style="font-size: 0.9rem; font-weight: 700; color: var(--sp-text); margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Satici Banka Bilgileri
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem;">
                            <div>
                                <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">Hesap Sahibi</p>
                                <p style="font-size: 0.9rem; color: var(--sp-text-2); font-weight: 500; margin: 0.25rem 0 0 0;">{{ $detail['bank_holder'] }}</p>
                            </div>
                            <div>
                                <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">IBAN</p>
                                <p style="font-size: 0.9rem; color: var(--sp-text-2); font-weight: 500; font-family: monospace; margin: 0.25rem 0 0 0;">{{ $detail['bank_iban'] }}</p>
                            </div>
                            <div>
                                <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin: 0;">Banka</p>
                                <p style="font-size: 0.9rem; color: var(--sp-text-2); font-weight: 500; margin: 0.25rem 0 0 0;">{{ $detail['bank_name'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Amount Details --}}
                <div>
                    <div style="background: var(--sp-bg-card); border: 1px solid var(--sp-border); border-radius: 0.75rem; padding: 1.5rem; position: sticky; top: 1rem;">
                        <p style="font-size: 0.9rem; font-weight: 700; color: var(--sp-text); margin: 0 0 1.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 18px; height: 18px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Tutar Detaylari
                        </p>

                        {{-- Total --}}
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--sp-border);">
                            <span style="color: var(--sp-text-2); font-size: 0.9rem;">Siparis Toplam Tutari</span>
                            <span style="color: var(--sp-text); font-weight: 800; font-size: 1.2rem;">{{ number_format($detail['total_sales'], 2, ',', '.') }} ₺</span>
                        </div>

                        {{-- Deductions --}}
                        <div style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.15); border-radius: 0.625rem; padding: 1.125rem; margin-bottom: 1.25rem;">
                            <p style="font-size: 0.7rem; font-weight: 700; color: #f87171; margin: 0 0 0.875rem 0; text-transform: uppercase; letter-spacing: 0.05em;">Kesintiler</p>

                            <div style="display: flex; justify-content: space-between; padding: 0.4rem 0;">
                                <span style="color: var(--sp-text-2); font-size: 0.85rem;">{{ $detail['fee_label'] }}</span>
                                <span style="color: #f87171; font-weight: 600; font-size: 0.85rem;">-{{ number_format($detail['service_fee'], 2, ',', '.') }} ₺</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 0.4rem 0;">
                                <span style="color: var(--sp-text-2); font-size: 0.85rem;">Stopaj %{{ $detail['withholding_rate'] }}</span>
                                <span style="color: #f87171; font-weight: 600; font-size: 0.85rem;">-{{ number_format($detail['withholding'], 2, ',', '.') }} ₺</span>
                            </div>
                            @if($detail['shipping'] > 0)
                                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0;">
                                    <span style="color: var(--sp-text-2); font-size: 0.85rem;">Kargo Payi</span>
                                    <span style="color: #f87171; font-weight: 600; font-size: 0.85rem;">-{{ number_format($detail['shipping'], 2, ',', '.') }} ₺</span>
                                </div>
                            @endif

                            <div style="display: flex; justify-content: space-between; padding: 0.625rem 0 0 0; margin-top: 0.625rem; border-top: 1px solid rgba(239,68,68,0.2);">
                                <span style="color: var(--sp-text-2); font-size: 0.85rem; font-weight: 600;">Toplam Kesintiler</span>
                                <span style="color: #f87171; font-weight: 700; font-size: 0.9rem;">-{{ number_format($detail['total_deductions'], 2, ',', '.') }} ₺</span>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div style="margin-bottom: 1.25rem;">
                            <div style="width: 100%; height: 10px; border-radius: 5px; background: #dc2626; overflow: hidden;">
                                <div style="width: {{ $detail['net_percent'] }}%; height: 100%; background: #10b981; border-radius: 5px;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                                <span style="font-size: 0.7rem; color: #6ee7b7; display: flex; align-items: center; gap: 0.25rem;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981;"></span>
                                    Net Kazanc (%{{ $detail['net_percent'] }})
                                </span>
                                <span style="font-size: 0.7rem; color: #f87171; display: flex; align-items: center; gap: 0.25rem;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #dc2626;"></span>
                                    Kesinti (%{{ $detail['deduction_percent'] }})
                                </span>
                            </div>
                        </div>

                        {{-- Net Amount Box --}}
                        <div style="background: linear-gradient(135deg, #059669, #10b981); border-radius: 0.75rem; padding: 1.5rem; text-align: center; margin-bottom: 1rem;">
                            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.8); font-weight: 600; margin: 0;">Saticiya Odenecek Tutar</p>
                            <p style="font-size: 2rem; font-weight: 800; color: white; margin: 0.25rem 0 0 0;">{{ number_format($detail['net_amount'], 2, ',', '.') }} ₺</p>
                        </div>

                        {{-- Release Note --}}
                        <div style="padding: 0.875rem; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); border-radius: 0.5rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <svg style="width: 18px; height: 18px; color: #fbbf24; flex-shrink: 0;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                            <span style="font-size: 0.8rem; color: #fcd34d;">Serbest birakma tarihi: {{ $detail['release_date'] }}</span>
                        </div>

                        {{-- Invoice Info --}}
                        @if($detail['invoice'])
                            <div style="padding: 0.875rem; border-radius: 0.5rem; margin-bottom: 1rem; {{ $detail['invoice']['erp_status'] === 'synced' ? 'background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);' : ($detail['invoice']['erp_status'] === 'failed' ? 'background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);' : 'background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2);') }}">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <svg style="width: 16px; height: 16px; color: {{ $detail['invoice']['erp_status'] === 'synced' ? '#34d399' : ($detail['invoice']['erp_status'] === 'failed' ? '#f87171' : '#fbbf24') }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--sp-text);">Fatura: {{ $detail['invoice']['number'] }}</span>
                                    <span style="padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600; {{ $detail['invoice']['erp_status'] === 'synced' ? 'background: rgba(16,185,129,0.2); color: #6ee7b7;' : ($detail['invoice']['erp_status'] === 'failed' ? 'background: rgba(239,68,68,0.2); color: #fca5a5;' : 'background: rgba(245,158,11,0.2); color: #fcd34d;') }}">
                                        {{ $detail['invoice']['erp_status'] === 'synced' ? 'Fatura Olusturuldu' : ($detail['invoice']['erp_status'] === 'failed' ? 'Basarisiz' : 'Fatura Kesilmedi') }}
                                    </span>
                                </div>
                                <p style="font-size: 0.75rem; color: #9ca3af; margin: 0;">{{ $detail['invoice']['created_at'] }}</p>
                                @if($detail['invoice']['erp_invoice_url'])
                                    <a href="{{ $detail['invoice']['erp_invoice_url'] }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.375rem; margin-top: 0.5rem; font-size: 0.75rem; color: #60a5fa; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        BizimHesap'ta Goruntule
                                    </a>
                                @endif
                                @if($detail['invoice']['erp_status'] === 'failed' && $detail['invoice']['erp_error'])
                                    <p style="font-size: 0.7rem; color: #fca5a5; margin: 0.375rem 0 0 0;">Hata: {{ $detail['invoice']['erp_error'] }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Action --}}
                        <button
                            wire:click="markSingleAsProcessed({{ $this->detailSubOrderId }})"
                            wire:confirm="{{ number_format($detail['net_amount'], 2, ',', '.') }} TL odeme yapildi olarak isaretlensin mi?"
                            style="width: 100%; padding: 1rem; border: none; border-radius: 0.75rem; background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 14px rgba(16,185,129,0.3);"
                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'"
                        >
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Odeme Yapildi
                        </button>
                    </div>
                </div>
            </div>
        @endif

    @else
        {{-- ═══════ LIST VIEW ═══════ --}}

        {{-- Summary Cards --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem;">
            <div style="background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 1rem; padding: 1.5rem; color: white; position: relative; overflow: hidden;">
                <p style="font-size: 0.8rem; font-weight: 500; opacity: 0.85; margin: 0;">Bekleyen Toplam</p>
                <p style="font-size: 1.75rem; font-weight: 800; margin: 0.25rem 0 0 0;">{{ number_format($stats['total_pending'], 2, ',', '.') }} ₺</p>
                <svg style="position: absolute; right: 14px; top: 14px; width: 44px; height: 44px; opacity: 0.15;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm1-13h-2v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            <div style="background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.5rem; color: white; position: relative; overflow: hidden;">
                <p style="font-size: 0.8rem; font-weight: 500; opacity: 0.85; margin: 0;">Odenecek Toplam</p>
                <p style="font-size: 1.75rem; font-weight: 800; margin: 0.25rem 0 0 0;">{{ number_format($stats['total_available'], 2, ',', '.') }} ₺</p>
                <svg style="position: absolute; right: 14px; top: 14px; width: 44px; height: 44px; opacity: 0.15;" fill="currentColor" viewBox="0 0 24 24"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
            </div>
            <div style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.5rem; color: white; position: relative; overflow: hidden;">
                <p style="font-size: 0.8rem; font-weight: 500; opacity: 0.85; margin: 0;">Tamamlanan (Bu Ay)</p>
                <p style="font-size: 1.75rem; font-weight: 800; margin: 0.25rem 0 0 0;">{{ number_format($stats['completed_this_month'], 2, ',', '.') }} ₺</p>
                <svg style="position: absolute; right: 14px; top: 14px; width: 44px; height: 44px; opacity: 0.15;" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <div style="background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 1rem; padding: 1.5rem; color: white; position: relative; overflow: hidden;">
                <p style="font-size: 0.8rem; font-weight: 500; opacity: 0.85; margin: 0;">Payout Bekleyen</p>
                <p style="font-size: 1.75rem; font-weight: 800; margin: 0.25rem 0 0 0;">{{ number_format($stats['pending_payout_amount'], 2, ',', '.') }} ₺</p>
                <p style="font-size: 0.7rem; opacity: 0.65; margin: 0.25rem 0 0 0;">{{ $stats['pending_payout_count'] }} adet</p>
                <svg style="position: absolute; right: 14px; top: 14px; width: 44px; height: 44px; opacity: 0.15;" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </div>
        </div>

        {{-- Table --}}
        @php $rows = $this->getPaymentRows(); @endphp

        @if(count($rows) > 0)
            <div style="background: var(--sp-bg-muted); border: 1px solid var(--sp-border); border-radius: 1rem; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="background: var(--sp-thead-bg); border-bottom: 2px solid var(--sp-border);">
                                <th style="padding: 1rem; text-align: left; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Son Odeme</th>
                                <th style="padding: 1rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Aciklama</th>
                                <th style="padding: 1rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Satici</th>
                                <th style="padding: 1rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; white-space: nowrap;">Satis(₺)</th>
                                <th style="padding: 1rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #f87171; white-space: nowrap;">Komisyon(₺)</th>
                                <th style="padding: 1rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #c084fc; white-space: nowrap;">Kargo(₺)</th>
                                <th style="padding: 1rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fbbf24; white-space: nowrap;">Stopaj(₺)</th>
                                <th style="padding: 1rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; white-space: nowrap;">Iade(₺)</th>
                                <th style="padding: 1rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #34d399; white-space: nowrap;">Tutar(₺)</th>
                                <th style="padding: 1rem 0.75rem; text-align: center; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Odeme Durumu</th>
                                <th style="padding: 1rem 0.75rem; text-align: center; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Islem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr style="border-bottom: 1px solid var(--sp-border-faint);" onmouseover="this.style.background='var(--sp-row-hover)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem;">
                                        @if($row['last_payout_date'])
                                            <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; background: #059669; color: white; white-space: nowrap;">{{ $row['last_payout_date'] }}</span>
                                        @else
                                            <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; background: #dc2626; color: white; white-space: nowrap;">Odenmedi</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem 0.75rem;">
                                        <p style="color: var(--sp-text-2); font-weight: 500; margin: 0; font-size: 0.8rem;">{{ $row['order_number'] }} kodlu siparis satisi.</p>
                                        <p style="color: #6b7280; font-size: 0.7rem; margin: 0.125rem 0 0 0;">{{ $row['order_date'] }}</p>
                                    </td>
                                    <td style="padding: 1rem 0.75rem;">
                                        <p style="color: var(--sp-text); font-weight: 600; margin: 0; font-size: 0.8rem;">{{ $row['seller_name'] }}</p>
                                        @if(!$row['has_iban'])
                                            <p style="color: #f87171; font-size: 0.65rem; margin: 0.125rem 0 0 0; display: flex; align-items: center; gap: 0.25rem;">
                                                <svg style="width: 10px; height: 10px;" fill="currentColor" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                                                IBAN yok
                                            </p>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 600; color: var(--sp-text); white-space: nowrap;">{{ number_format($row['total_sales'], 2, ',', '.') }}</td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 500; color: #f87171; white-space: nowrap;">{{ number_format($row['service_fee'], 2, ',', '.') }}</td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 500; color: #c084fc; white-space: nowrap;">{{ number_format($row['shipping'], 2, ',', '.') }}</td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 500; color: #fbbf24; white-space: nowrap;">{{ number_format($row['withholding'], 2, ',', '.') }}</td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 500; color: #6b7280; white-space: nowrap;">0,00</td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; white-space: nowrap;">
                                        <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: 0.375rem; font-size: 0.85rem; font-weight: 700; background: rgba(16,185,129,0.15); color: #34d399;">{{ number_format($row['net_amount'], 2, ',', '.') }}</span>
                                    </td>
                                    <td style="padding: 1rem 0.75rem; text-align: center;">
                                        @if($row['days_remaining'] <= 0)
                                            <span style="padding: 0.25rem 0.625rem; border-radius: 0.375rem; font-size: 0.7rem; font-weight: 600; background: rgba(16,185,129,0.15); color: #6ee7b7; white-space: nowrap;">Odenmedi</span>
                                        @else
                                            <span style="padding: 0.25rem 0.625rem; border-radius: 0.375rem; font-size: 0.7rem; font-weight: 600; background: rgba(245,158,11,0.15); color: #fcd34d; white-space: nowrap;">Beklemede</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem 0.75rem; text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.375rem;">
                                            <button wire:click="showDetail({{ $row['sub_order_id'] }})" style="width: 32px; height: 32px; border-radius: 0.5rem; display: inline-flex; align-items: center; justify-content: center; background: rgba(59,130,246,0.15); color: #60a5fa; border: none; cursor: pointer;" title="Detay" onmouseover="this.style.background='rgba(59,130,246,0.3)'" onmouseout="this.style.background='rgba(59,130,246,0.15)'">
                                                <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                            </button>
                                            <button wire:click="markSingleAsProcessed({{ $row['sub_order_id'] }})" wire:confirm="'{{ $row['seller_name'] }}' icin {{ number_format($row['net_amount'], 2, ',', '.') }} TL odeme yapildi olarak isaretlensin mi?" style="width: 32px; height: 32px; border-radius: 0.5rem; display: inline-flex; align-items: center; justify-content: center; background: rgba(16,185,129,0.15); color: #34d399; border: none; cursor: pointer;" title="Odeme Yapildi" onmouseover="this.style.background='rgba(16,185,129,0.3)'" onmouseout="this.style.background='rgba(16,185,129,0.15)'">
                                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div style="background: var(--sp-bg-muted); border: 1px solid var(--sp-border); border-radius: 1rem; padding: 4rem; text-align: center;">
                <svg style="width: 64px; height: 64px; color: #4b5563; margin: 0 auto 1.25rem auto;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--sp-text); margin: 0 0 0.5rem 0;">Bekleyen Odeme Yok</h3>
                <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">Henuz odenmesi gereken satici hakedisi bulunamadi.</p>
            </div>
        @endif
    @endif
</div>
</x-filament-panels::page>
