'use client';

import { useState, useEffect, useCallback, useRef, Fragment } from 'react';
import {
  walletApi,
  SettlementsResponse,
  SettlementGroup,
  SettlementDetailsResponse,
} from '@/lib/api';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import {
  Wallet,
  Clock,
  CheckCircle2,
  ChevronDown,
  TrendingUp,
  FileText,
  Package,
  Calendar,
  ShoppingBag,
  Download,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const formatMoney = (amount: number) =>
  new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(amount);

const detailsCache = new Map<string, SettlementDetailsResponse>();

function SettlementCard({
  group,
  type,
}: {
  group: SettlementGroup;
  type: 'upcoming' | 'past';
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [viewMode, setViewMode] = useState<'summary' | 'detail'>('summary');
  const [details, setDetails] = useState<SettlementDetailsResponse | null>(null);
  const [loadingDetails, setLoadingDetails] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);
  const fetchedRef = useRef(false);

  const loadDetails = useCallback(async () => {
    const cacheKey = `${group.date}_${type}`;
    if (detailsCache.has(cacheKey)) {
      setDetails(detailsCache.get(cacheKey)!);
      setLoadError(null);
      return;
    }

    setLoadingDetails(true);
    setLoadError(null);
    try {
      const res = await walletApi.getSettlementDetails(group.date, type);
      if (res.data) {
        detailsCache.set(cacheKey, res.data);
        setDetails(res.data);
      } else {
        setLoadError(res.error || `HTTP ${res.status}`);
      }
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : 'Bağlantı hatası');
    } finally {
      setLoadingDetails(false);
    }
  }, [group.date, type]);

  const handleToggle = () => {
    const willOpen = !isOpen;
    setIsOpen(willOpen);
    if (willOpen && !fetchedRef.current) {
      fetchedRef.current = true;
      loadDetails();
    }
  };

  const isUpcoming = type === 'upcoming';

  return (
    <div className={cn(
      'rounded-2xl bg-white overflow-hidden transition-colors border',
      isOpen ? 'border-[#ffedd5]' : 'border-[#f0eceb] hover:border-[#ffedd5]'
    )}>
      {/* Header */}
      <button
        onClick={handleToggle}
        className="w-full flex items-center justify-between p-4 hover:bg-[#faf8f6] transition-colors text-left"
      >
        <div className="min-w-0">
          <div className="flex items-center gap-2.5 flex-wrap">
            <span className="text-sm font-bold text-[#1a1a1a]">{group.date_formatted} Hakedis Ödemesi</span>
            {isUpcoming ? (
              <span className="inline-flex items-center text-[10px] font-semibold px-3 py-1 rounded-full bg-amber-50 text-amber-700">
                Tahmini Hesaplanmistir
              </span>
            ) : (
              <span className="inline-flex items-center text-[10px] font-semibold px-3 py-1 rounded-full bg-[#ffedd5] text-[#ea580c]">
                Ödeme Yapildi
              </span>
            )}
          </div>
          <p className="text-xs text-[#6b7280] mt-1">
            {group.order_count} sipariş &middot; {group.item_count} ürün
            {isUpcoming && group.days_remaining > 0 && (
              <span className="text-amber-600 font-medium"> &middot; {group.days_remaining} gun sonra</span>
            )}
          </p>
        </div>
        <div className="flex items-center gap-3 flex-shrink-0 ml-4">
          <div className="text-right">
            <p className="text-xs text-[#6b7280]">Tahmini Tutar:</p>
            <p className="text-xl font-black text-[#1a1a1a]">{formatMoney(group.net_amount)}</p>
          </div>
          <ChevronDown className={cn(
            'w-5 h-5 text-slate-400 transition-transform duration-200',
            isOpen && 'rotate-180'
          )} />
        </div>
      </button>

      {/* Expandable content */}
      {isOpen && (
        <div className="border-t border-[#f0eceb]">
          <div className="p-4">
            {loadingDetails ? (
              <div className="space-y-3">
                <Skeleton className="h-10 w-full rounded-lg" />
                <Skeleton className="h-10 w-full rounded-lg" />
                <Skeleton className="h-10 w-full rounded-lg" />
                <Skeleton className="h-10 w-full rounded-lg" />
              </div>
            ) : details ? (
              <>
                {/* View mode toggle */}
                <div className="flex gap-1 bg-[#faf8f6] rounded-xl p-1 mb-5 w-fit">
                  <button
                    onClick={() => setViewMode('summary')}
                    className={cn(
                      'px-4 py-1.5 text-xs font-semibold rounded-lg transition-all',
                      viewMode === 'summary'
                        ? 'bg-white text-[#ea580c] border border-[#f0eceb]'
                        : 'text-[#6b7280] hover:text-[#1a1a1a]'
                    )}
                  >
                    Özet Gorunum
                  </button>
                  <button
                    onClick={() => setViewMode('detail')}
                    className={cn(
                      'px-4 py-1.5 text-xs font-semibold rounded-lg transition-all',
                      viewMode === 'detail'
                        ? 'bg-white text-[#ea580c] border border-[#f0eceb]'
                        : 'text-[#6b7280] hover:text-[#1a1a1a]'
                    )}
                  >
                    Sipariş Detaylari
                  </button>
                </div>

                {viewMode === 'summary' ? (
                  <SummaryView rows={details.summary} />
                ) : (
                  <DetailView items={details.details} />
                )}
              </>
            ) : (
              <div className="text-center py-6">
                <FileText className="w-10 h-10 mx-auto text-slate-300 mb-2" />
                <p className="text-sm text-slate-500">Detay bilgisi yüklenemedi</p>
                {loadError && (
                  <p className="text-xs text-red-500 mt-1 font-mono">{loadError}</p>
                )}
                <button
                  onClick={() => { fetchedRef.current = false; setLoadError(null); loadDetails(); }}
                  className="text-xs text-[#ea580c] hover:text-[#c2410c] font-medium mt-2"
                >
                  Tekrar Dene
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

function SummaryView({ rows }: { rows: SettlementDetailsResponse['summary'] }) {
  return (
    <div className="border border-[#f0eceb] rounded-2xl overflow-hidden">
      <table className="w-full text-sm">
        <thead>
          <tr className="bg-[#faf8f6] border-b border-[#f0eceb]">
            <th className="text-left py-3 px-4 text-xs font-semibold text-[#6b7280] uppercase tracking-wider w-[180px]">Islem Tipi</th>
            <th className="text-left py-3 px-4 text-xs font-semibold text-[#6b7280] uppercase tracking-wider">Aciklama</th>
            <th className="text-right py-3 px-4 text-xs font-semibold text-[#6b7280] uppercase tracking-wider w-[140px]">Tutar</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row, i) => {
            const isTotal = row.type === 'total';
            return (
              <tr
                key={i}
                className={cn(
                  'border-b border-[#f0eceb] last:border-0',
                  isTotal && 'bg-[#ffedd5]'
                )}
              >
                <td className={cn(
                  'py-3.5 px-4',
                  isTotal ? 'font-bold text-[#1a1a1a]' : 'font-medium text-[#1a1a1a]'
                )}>
                  {row.label}
                </td>
                <td className={cn(
                  'py-3.5 px-4 text-[#6b7280]',
                  isTotal && 'font-medium text-[#ea580c]'
                )}>
                  {row.description || (isTotal ? 'Tum kesintiler dusuldukten sonra net tutar' : '')}
                </td>
                <td className={cn(
                  'py-3.5 px-4 text-right font-bold whitespace-nowrap',
                  isTotal
                    ? 'text-[#ea580c] text-base'
                    : row.type === 'credit' ? 'text-[#ea580c]' : 'text-red-500'
                )}>
                  {formatMoney(row.amount)}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

function exportToExcel(items: SettlementDetailsResponse['details']) {
  const BOM = '\uFEFF';
  const headers = ['Sipariş No', 'Sipariş Tarihi', 'Ürün Sayisi', 'Sipariş Tutari', 'Komisyon', 'Stopaj', 'Kargo Payi', 'Toplam Kesinti', 'Net Tutar'];
  const rows = items.map((order) => {
    const deduction = order.service_fee + order.withholding_tax + order.shipping_share;
    return [
      order.order_number,
      order.order_date,
      order.item_count,
      order.total_price.toFixed(2),
      order.service_fee.toFixed(2),
      order.withholding_tax.toFixed(2),
      order.shipping_share.toFixed(2),
      deduction.toFixed(2),
      order.net_amount.toFixed(2),
    ];
  });

  // Totals row
  const totals = [
    `Toplam (${items.length} sipariş)`,
    '',
    items.reduce((s, o) => s + o.item_count, 0),
    items.reduce((s, o) => s + o.total_price, 0).toFixed(2),
    items.reduce((s, o) => s + o.service_fee, 0).toFixed(2),
    items.reduce((s, o) => s + o.withholding_tax, 0).toFixed(2),
    items.reduce((s, o) => s + o.shipping_share, 0).toFixed(2),
    items.reduce((s, o) => s + o.service_fee + o.withholding_tax + o.shipping_share, 0).toFixed(2),
    items.reduce((s, o) => s + o.net_amount, 0).toFixed(2),
  ];
  rows.push(totals);

  // Add product details
  rows.push([]);
  rows.push(['--- Ürün Detaylari ---']);
  rows.push(['Sipariş No', 'Ürün Adi', 'Adet', 'Birim Fiyat', 'Toplam']);
  items.forEach((order) => {
    order.items?.forEach((product) => {
      rows.push([
        order.order_number,
        product.product_name,
        product.quantity,
        product.unit_price.toFixed(2),
        product.total_price.toFixed(2),
      ]);
    });
  });

  const escape = (val: string | number) => {
    const str = String(val);
    if (str.includes(';') || str.includes('"') || str.includes('\n')) {
      return `"${str.replace(/"/g, '""')}"`;
    }
    return str;
  };

  const csv = BOM + [headers.join(';'), ...rows.map((row) => (Array.isArray(row) ? row : [row]).map(escape).join(';'))].join('\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `hakedis-detay-${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function DetailView({ items }: { items: SettlementDetailsResponse['details'] }) {
  const [expandedOrder, setExpandedOrder] = useState<string | null>(null);

  if (items.length === 0) {
    return (
      <div className="text-center py-8">
        <Package className="w-12 h-12 mx-auto text-slate-300 mb-3" />
        <p className="text-sm text-slate-500">Sipariş bulunamadi</p>
      </div>
    );
  }

  const totalDeduction = (order: SettlementDetailsResponse['details'][0]) =>
    order.service_fee + order.withholding_tax + order.shipping_share;

  return (
    <div className="space-y-3">
      {/* Export Button */}
      <div className="flex justify-end">
        <button
          onClick={() => exportToExcel(items)}
          className="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-[#ea580c] bg-[#ffedd5] hover:bg-[#ffedd5] border border-[#f0eceb] rounded-xl transition-colors"
        >
          <Download className="w-3.5 h-3.5" />
          Excel Olarak İndir
        </button>
      </div>

      {/* Desktop Table */}
      <div className="hidden lg:block border border-[#f0eceb] rounded-2xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[#faf8f6] border-b border-[#f0eceb]">
                <th className="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sipariş</th>
                <th className="text-right py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sipariş Tutari</th>
                <th className="text-right py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Komisyon</th>
                <th className="text-right py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stopaj</th>
                <th className="text-right py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kargo</th>
                <th className="text-right py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Toplam Kesinti</th>
                <th className="text-right py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Tutar</th>
                <th className="text-center py-3 px-2 text-xs font-semibold text-slate-500 uppercase tracking-wider w-12"></th>
              </tr>
            </thead>
            <tbody>
              {items.map((order, i) => (
                <Fragment key={order.order_number}>
                  <tr
                    className={cn(
                      'border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors',
                      expandedOrder === order.order_number && 'bg-slate-50/50'
                    )}
                  >
                    <td className="py-3.5 px-4">
                      <p className="font-semibold text-slate-800">{order.order_number}</p>
                      <p className="text-xs text-slate-400 mt-0.5">{order.order_date}</p>
                    </td>
                    <td className="py-3.5 px-4 text-right font-medium text-slate-700 whitespace-nowrap">
                      {formatMoney(order.total_price)}
                    </td>
                    <td className="py-3.5 px-4 text-right text-red-500 whitespace-nowrap">
                      {order.service_fee > 0 ? `-${formatMoney(order.service_fee)}` : '-'}
                    </td>
                    <td className="py-3.5 px-4 text-right text-red-500 whitespace-nowrap">
                      {order.withholding_tax > 0 ? `-${formatMoney(order.withholding_tax)}` : '-'}
                    </td>
                    <td className="py-3.5 px-4 text-right text-red-500 whitespace-nowrap">
                      {order.shipping_share > 0 ? `-${formatMoney(order.shipping_share)}` : '-'}
                    </td>
                    <td className="py-3.5 px-4 text-right font-medium text-red-600 whitespace-nowrap">
                      {totalDeduction(order) > 0 ? `-${formatMoney(totalDeduction(order))}` : '-'}
                    </td>
                    <td className="py-3.5 px-4 text-right font-bold text-[#ea580c] whitespace-nowrap">
                      {formatMoney(order.net_amount)}
                    </td>
                    <td className="py-3.5 px-2 text-center">
                      {order.items && order.items.length > 0 && (
                        <button
                          onClick={() => setExpandedOrder(expandedOrder === order.order_number ? null : order.order_number)}
                          className="p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                          title="Ürünleri goster"
                        >
                          <ChevronDown className={cn(
                            'w-4 h-4 text-slate-400 transition-transform duration-200',
                            expandedOrder === order.order_number && 'rotate-180'
                          )} />
                        </button>
                      )}
                    </td>
                  </tr>
                  {expandedOrder === order.order_number && order.items && order.items.length > 0 && (
                    <tr>
                      <td colSpan={8} className="bg-slate-50/80 px-4 py-3 border-b border-slate-100">
                        <p className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Ürünler</p>
                        <div className="space-y-1.5">
                          {order.items.map((product, pi) => (
                            <div key={pi} className="flex items-center justify-between text-xs">
                              <span className="text-slate-700">{product.product_name}</span>
                              <span className="text-slate-500 whitespace-nowrap ml-4">
                                {product.quantity} adet x {formatMoney(product.unit_price)} = <span className="font-medium text-slate-700">{formatMoney(product.total_price)}</span>
                              </span>
                            </div>
                          ))}
                        </div>
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
            </tbody>
            {/* Total footer */}
            {items.length > 1 && (
              <tfoot>
                <tr className="bg-[#ffedd5] border-t border-[#f0eceb]">
                  <td className="py-3.5 px-4 font-semibold text-slate-700">
                    Toplam ({items.length} sipariş)
                  </td>
                  <td className="py-3.5 px-4 text-right font-bold text-slate-700 whitespace-nowrap">
                    {formatMoney(items.reduce((s, o) => s + o.total_price, 0))}
                  </td>
                  <td className="py-3.5 px-4 text-right font-bold text-red-500 whitespace-nowrap">
                    -{formatMoney(items.reduce((s, o) => s + o.service_fee, 0))}
                  </td>
                  <td className="py-3.5 px-4 text-right font-bold text-red-500 whitespace-nowrap">
                    -{formatMoney(items.reduce((s, o) => s + o.withholding_tax, 0))}
                  </td>
                  <td className="py-3.5 px-4 text-right font-bold text-red-500 whitespace-nowrap">
                    -{formatMoney(items.reduce((s, o) => s + o.shipping_share, 0))}
                  </td>
                  <td className="py-3.5 px-4 text-right font-bold text-red-600 whitespace-nowrap">
                    -{formatMoney(items.reduce((s, o) => s + totalDeduction(o), 0))}
                  </td>
                  <td className="py-3.5 px-4 text-right font-bold text-[#ea580c] whitespace-nowrap">
                    {formatMoney(items.reduce((s, o) => s + o.net_amount, 0))}
                  </td>
                  <td />
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </div>

      {/* Tablet Table (md-lg) */}
      <div className="hidden md:block lg:hidden border border-[#f0eceb] rounded-2xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[#faf8f6] border-b border-[#f0eceb]">
                <th className="text-left py-3 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sipariş</th>
                <th className="text-right py-3 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tutar</th>
                <th className="text-right py-3 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kesinti</th>
                <th className="text-right py-3 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Net</th>
                <th className="w-10" />
              </tr>
            </thead>
            <tbody>
              {items.map((order) => (
                <Fragment key={order.order_number}>
                  <tr
                    className="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors"
                  >
                    <td className="py-3 px-3">
                      <p className="font-semibold text-slate-800 text-sm">{order.order_number}</p>
                      <p className="text-xs text-slate-400">{order.order_date}</p>
                    </td>
                    <td className="py-3 px-3 text-right font-medium text-slate-700 whitespace-nowrap text-sm">
                      {formatMoney(order.total_price)}
                    </td>
                    <td className="py-3 px-3 text-right text-red-500 whitespace-nowrap text-sm">
                      -{formatMoney(totalDeduction(order))}
                    </td>
                    <td className="py-3 px-3 text-right font-bold text-[#ea580c] whitespace-nowrap text-sm">
                      {formatMoney(order.net_amount)}
                    </td>
                    <td className="py-3 px-2">
                      {order.items && order.items.length > 0 && (
                        <button
                          onClick={() => setExpandedOrder(expandedOrder === order.order_number ? null : order.order_number)}
                          className="p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                        >
                          <ChevronDown className={cn(
                            'w-4 h-4 text-slate-400 transition-transform duration-200',
                            expandedOrder === order.order_number && 'rotate-180'
                          )} />
                        </button>
                      )}
                    </td>
                  </tr>
                  {expandedOrder === order.order_number && order.items && order.items.length > 0 && (
                    <tr>
                      <td colSpan={5} className="bg-slate-50/80 px-3 py-3 border-b border-slate-100">
                        <div className="grid grid-cols-3 gap-2 text-xs mb-3">
                          <div className="p-2 bg-white rounded-lg border border-slate-100">
                            <p className="text-slate-400 text-[10px] uppercase">Komisyon</p>
                            <p className="font-semibold text-red-500">{order.service_fee > 0 ? `-${formatMoney(order.service_fee)}` : '-'}</p>
                          </div>
                          <div className="p-2 bg-white rounded-lg border border-slate-100">
                            <p className="text-slate-400 text-[10px] uppercase">Stopaj</p>
                            <p className="font-semibold text-red-500">{order.withholding_tax > 0 ? `-${formatMoney(order.withholding_tax)}` : '-'}</p>
                          </div>
                          <div className="p-2 bg-white rounded-lg border border-slate-100">
                            <p className="text-slate-400 text-[10px] uppercase">Kargo</p>
                            <p className="font-semibold text-red-500">{order.shipping_share > 0 ? `-${formatMoney(order.shipping_share)}` : '-'}</p>
                          </div>
                        </div>
                        <p className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Ürünler</p>
                        <div className="space-y-1.5">
                          {order.items.map((product, pi) => (
                            <div key={pi} className="flex items-center justify-between text-xs">
                              <span className="text-slate-700">{product.product_name}</span>
                              <span className="text-slate-500 whitespace-nowrap ml-2">
                                {product.quantity}x {formatMoney(product.unit_price)}
                              </span>
                            </div>
                          ))}
                        </div>
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
            </tbody>
            {items.length > 1 && (
              <tfoot>
                <tr className="bg-[#ffedd5] border-t border-[#f0eceb]">
                  <td className="py-3 px-3 font-semibold text-slate-700 text-sm">Toplam</td>
                  <td className="py-3 px-3 text-right font-bold text-slate-700 whitespace-nowrap text-sm">
                    {formatMoney(items.reduce((s, o) => s + o.total_price, 0))}
                  </td>
                  <td className="py-3 px-3 text-right font-bold text-red-500 whitespace-nowrap text-sm">
                    -{formatMoney(items.reduce((s, o) => s + totalDeduction(o), 0))}
                  </td>
                  <td className="py-3 px-3 text-right font-bold text-[#ea580c] whitespace-nowrap text-sm">
                    {formatMoney(items.reduce((s, o) => s + o.net_amount, 0))}
                  </td>
                  <td />
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </div>

      {/* Mobile Cards */}
      <div className="md:hidden space-y-3">
        {items.map((order) => (
          <div key={order.order_number} className="border border-[#f0eceb] rounded-2xl overflow-hidden bg-white">
            <button
              onClick={() => setExpandedOrder(expandedOrder === order.order_number ? null : order.order_number)}
              className="w-full p-4 text-left hover:bg-slate-50/50 transition-colors"
            >
              <div className="flex items-start justify-between gap-2">
                <div>
                  <p className="font-semibold text-slate-800">{order.order_number}</p>
                  <p className="text-xs text-slate-400 mt-0.5">{order.order_date} &middot; {order.item_count} kalem</p>
                </div>
                <div className="flex items-center gap-1.5">
                  <div className="text-right">
                    <p className="text-[10px] text-slate-400">Net</p>
                    <p className="font-bold text-[#ea580c]">{formatMoney(order.net_amount)}</p>
                  </div>
                  <ChevronDown className={cn(
                    'w-4 h-4 text-slate-400 transition-transform duration-200',
                    expandedOrder === order.order_number && 'rotate-180'
                  )} />
                </div>
              </div>

              {/* Quick summary row */}
              <div className="flex items-center gap-2 mt-2 text-xs flex-wrap">
                <span className="text-slate-600 font-medium">{formatMoney(order.total_price)}</span>
                <span className="text-red-400">-{formatMoney(totalDeduction(order))}</span>
              </div>
            </button>

            {expandedOrder === order.order_number && (
              <div className="border-t border-slate-100 px-4 py-3 space-y-3">
                {/* Fee breakdown */}
                <div className="grid grid-cols-3 gap-2">
                  <div className="p-2.5 bg-slate-50 rounded-lg text-center">
                    <p className="text-[10px] text-slate-400 uppercase font-semibold">Komisyon</p>
                    <p className="text-sm font-bold text-red-500 mt-0.5">
                      {order.service_fee > 0 ? `-${formatMoney(order.service_fee)}` : '-'}
                    </p>
                  </div>
                  <div className="p-2.5 bg-slate-50 rounded-lg text-center">
                    <p className="text-[10px] text-slate-400 uppercase font-semibold">Stopaj</p>
                    <p className="text-sm font-bold text-red-500 mt-0.5">
                      {order.withholding_tax > 0 ? `-${formatMoney(order.withholding_tax)}` : '-'}
                    </p>
                  </div>
                  <div className="p-2.5 bg-slate-50 rounded-lg text-center">
                    <p className="text-[10px] text-slate-400 uppercase font-semibold">Kargo</p>
                    <p className="text-sm font-bold text-red-500 mt-0.5">
                      {order.shipping_share > 0 ? `-${formatMoney(order.shipping_share)}` : '-'}
                    </p>
                  </div>
                </div>

                {/* Products */}
                {order.items && order.items.length > 0 && (
                  <div>
                    <p className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Ürünler</p>
                    <div className="space-y-2">
                      {order.items.map((product, pi) => (
                        <div key={pi} className="flex items-center justify-between text-xs">
                          <span className="text-slate-700 truncate mr-2">{product.product_name}</span>
                          <span className="text-slate-500 whitespace-nowrap">
                            {product.quantity}x {formatMoney(product.unit_price)}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        ))}

        {/* Mobile Total */}
        {items.length > 1 && (
          <div className="flex items-center justify-between px-4 py-3 bg-[#ffedd5] rounded-xl border border-[#f0eceb]">
            <span className="text-sm font-semibold text-slate-600">Toplam ({items.length} sipariş)</span>
            <span className="font-bold text-[#ea580c]">{formatMoney(items.reduce((s, o) => s + o.net_amount, 0))}</span>
          </div>
        )}
      </div>
    </div>
  );
}

export function SettlementsContent({ subNav }: { subNav: string }) {
  const [data, setData] = useState<SettlementsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    loadSettlements();
  }, []);

  const loadSettlements = async () => {
    setLoading(true);
    setError(false);
    try {
      const res = await walletApi.getSettlements();
      if (res.data) {
        setData(res.data);
      }
    } catch {
      setError(true);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <div className="grid grid-cols-3 gap-3">
          {[1, 2, 3].map((i) => <Skeleton key={i} className="h-24 w-full rounded-xl" />)}
        </div>
        <Skeleton className="h-16 w-full rounded-xl" />
        {[1, 2, 3].map((i) => <Skeleton key={i} className="h-20 w-full rounded-xl" />)}
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="text-center py-16 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
        <FileText className="w-16 h-16 mx-auto text-[#6b7280]/40 mb-4" />
        <p className="text-[#1a1a1a] font-medium mb-1">Hakedis bilgileri yuklenemedi</p>
        <p className="text-sm text-[#6b7280] mb-4">Lutfen internet baglantinizi kontrol edin</p>
        <button
          onClick={loadSettlements}
          className="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-[#1e3a8a] hover:bg-[#1e40af] rounded-xl transition-colors"
        >
          Tekrar Dene
        </button>
      </div>
    );
  }

  const isUpcoming = subNav === 'gelecek-odemeler';
  const isPast = subNav === 'gecmis-odemeler';
  const groups = isUpcoming ? data.upcoming : isPast ? data.past : [];
  const upcomingSummary = data.upcoming_summary;

  return (
    <div className="space-y-5">
      {/* Ozet kartlari */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="border border-[#f0eceb] rounded-2xl p-4">
          <div className="flex items-center gap-2 mb-3">
            <div className="w-7 h-7 rounded-lg bg-[#ffedd5] flex items-center justify-center">
              <TrendingUp className="w-3.5 h-3.5 text-[#ea580c]" />
            </div>
            <span className="text-xs text-[#6b7280] font-medium">Toplam Satış</span>
          </div>
          <p className="text-xl font-black text-[#1a1a1a]">{formatMoney(data.total_gross_sales)}</p>
          <p className="text-xs text-[#6b7280] mt-1">Tamamlanan siparisler toplami</p>
        </div>
        <div className="border border-[#f0eceb] rounded-2xl p-4">
          <div className="flex items-center gap-2 mb-3">
            <div className="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
              <Wallet className="w-3.5 h-3.5 text-amber-600" />
            </div>
            <span className="text-xs text-[#6b7280] font-medium">Hakedise Yansiyacak Tutar</span>
          </div>
          <p className="text-xl font-black text-[#1a1a1a]">{formatMoney(data.upcoming_summary.net_estimated_total)}</p>
          <p className="text-xs text-[#6b7280] mt-1">Kesintiler dusuldukten sonra</p>
        </div>
        <div className="border border-[#f0eceb] rounded-2xl p-4">
          <div className="flex items-center gap-2 mb-3">
            <div className="w-7 h-7 rounded-lg bg-[#faf8f6] flex items-center justify-center">
              <ShoppingBag className="w-3.5 h-3.5 text-[#6b7280]" />
            </div>
            <span className="text-xs text-[#6b7280] font-medium">Onaylanan Siparişler</span>
          </div>
          <p className="text-xl font-black text-[#1a1a1a]">{data.confirmed_order_count}</p>
          <p className="text-xs text-[#6b7280] mt-1">Onaylanan ve hazirlanan</p>
        </div>
      </div>

      {/* Bekleyen hakedis ozet cubugu */}
      {isUpcoming && upcomingSummary && upcomingSummary.net_estimated_total > 0 && (
        <div className="p-4 bg-[#1a1a1a] rounded-2xl text-white">
          <div className="flex items-center gap-2 mb-4">
            <div className="w-6 h-6 rounded-md bg-white/10 flex items-center justify-center">
              <Calendar className="w-3.5 h-3.5 text-white/70" />
            </div>
            <span className="text-sm text-white/70 font-medium">Bekleyen Hakedis Özeti</span>
          </div>
          {/* Mobile: stacked rows */}
          <div className="sm:hidden space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-xs text-white/50">Satış Tutari</span>
              <span className="text-sm font-bold text-white">{formatMoney(upcomingSummary.total_sales)}</span>
            </div>
            {upcomingSummary.total_service_fee > 0 && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-white/50">Hizmet Bedeli</span>
                <span className="text-sm font-bold text-red-400">{formatMoney(-upcomingSummary.total_service_fee)}</span>
              </div>
            )}
            {upcomingSummary.total_withholding_tax > 0 && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-white/50">Stopaj</span>
                <span className="text-sm font-bold text-red-400">{formatMoney(-upcomingSummary.total_withholding_tax)}</span>
              </div>
            )}
            {upcomingSummary.total_shipping_share > 0 && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-white/50">Kargo</span>
                <span className="text-sm font-bold text-red-400">{formatMoney(-upcomingSummary.total_shipping_share)}</span>
              </div>
            )}
            {(upcomingSummary as any).total_refunds > 0 && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-white/50">İade Kesintisi</span>
                <span className="text-sm font-bold text-red-400">{formatMoney(-(upcomingSummary as any).total_refunds)}</span>
              </div>
            )}
            <div className="flex items-center justify-between pt-2 border-t border-white/10">
              <span className="text-xs text-[#ffedd5] font-semibold">Tahmini Net</span>
              <span className="text-base font-black text-[#ffedd5]">{formatMoney(upcomingSummary.net_estimated_total)}</span>
            </div>
          </div>
          {/* Desktop: grid */}
          <div className={`hidden sm:grid gap-4 text-center ${(upcomingSummary as any).total_refunds > 0 ? 'grid-cols-6' : 'grid-cols-5'}`}>
            <div className="space-y-1">
              <p className="text-[10px] text-white/50 uppercase tracking-wider font-semibold">Satış Tutarı</p>
              <p className="text-base font-bold text-white">{formatMoney(upcomingSummary.total_sales)}</p>
            </div>
            <div className="space-y-1">
              <p className="text-[10px] text-white/50 uppercase tracking-wider font-semibold">Hizmet Bedeli</p>
              <p className="text-base font-bold text-red-400">{formatMoney(-upcomingSummary.total_service_fee)}</p>
            </div>
            <div className="space-y-1">
              <p className="text-[10px] text-white/50 uppercase tracking-wider font-semibold">Stopaj</p>
              <p className="text-base font-bold text-red-400">{formatMoney(-upcomingSummary.total_withholding_tax)}</p>
            </div>
            <div className="space-y-1">
              <p className="text-[10px] text-white/50 uppercase tracking-wider font-semibold">Kargo</p>
              <p className="text-base font-bold text-red-400">{formatMoney(-upcomingSummary.total_shipping_share)}</p>
            </div>
            {(upcomingSummary as any).total_refunds > 0 && (
              <div className="space-y-1">
                <p className="text-[10px] text-white/50 uppercase tracking-wider font-semibold">İade</p>
                <p className="text-base font-bold text-red-400">{formatMoney(-(upcomingSummary as any).total_refunds)}</p>
              </div>
            )}
            <div className="space-y-1 border-l border-white/10 pl-4">
              <p className="text-[10px] text-[#ffedd5] uppercase tracking-wider font-semibold">Tahmini Net</p>
              <p className="text-base font-black text-[#ffedd5]">{formatMoney(upcomingSummary.net_estimated_total)}</p>
            </div>
          </div>
        </div>
      )}

      {/* Hakedis kartlari */}
      {groups.length === 0 ? (
        <div className="text-center py-16 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
          <div className="w-16 h-16 mx-auto bg-white rounded-2xl border border-[#f0eceb] flex items-center justify-center mb-4">
            {isUpcoming
              ? <Clock className="w-8 h-8 text-[#6b7280]/40" />
              : <CheckCircle2 className="w-8 h-8 text-[#6b7280]/40" />
            }
          </div>
          <p className="text-[#1a1a1a] font-medium">
            {isUpcoming ? 'Bekleyen hakedis bulunmuyor' : 'Gecmis odeme bulunmuyor'}
          </p>
          <p className="text-xs text-[#6b7280] mt-2 max-w-xs mx-auto">
            {isUpcoming
              ? 'Alici siparisi onayladiktan sonra hakedisleriniz burada gorunecektir'
              : 'Tamamlanan hakedis odemeleri burada listelenir'}
          </p>
        </div>
      ) : (
        <div className="space-y-3">
          {groups.map((group) => (
            <SettlementCard
              key={group.date}
              group={group}
              type={isUpcoming ? 'upcoming' : 'past'}
            />
          ))}
        </div>
      )}
    </div>
  );
}
