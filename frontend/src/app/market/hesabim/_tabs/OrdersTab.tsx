'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { ordersApi, Order, SellerOrder, api, sellerApi, SellerOrderDetail, invoiceApi, shippingApi, returnsApi, ReturnRequest, ReturnReason, walletApi, contractsApi } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import {
    Box,
    ShoppingBag,
    Store,
    FileText,
    Clock,
    CheckCircle2,
    Truck,
    XCircle,
    Eye,
    Search,
    ChevronRight,
    AlertCircle,
    Link2,
    Loader2,
    Check,
    X,
    Wallet,
    RotateCcw,
    Camera,
    Minus,
    Plus,
    Download,
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { SellerTypeBadge } from '@/components/ui/SellerTypeBadge';

// Status icon component
function StatusIcon({ status }: { status: string }) {
    const icons: Record<string, { icon: React.ReactNode; color: string }> = {
        pending: { icon: <Clock className="w-4 h-4" />, color: 'text-amber-600' },
        confirmed: { icon: <CheckCircle2 className="w-4 h-4" />, color: 'text-blue-600' },
        processing: { icon: <Box className="w-4 h-4" />, color: 'text-blue-600' },
        shipped: { icon: <Truck className="w-4 h-4" />, color: 'text-[#b8651a]' },
        delivered: { icon: <CheckCircle2 className="w-4 h-4" />, color: 'text-[#b8651a]' },
        returned: { icon: <RotateCcw className="w-4 h-4" />, color: 'text-red-600' },
        cancelled: { icon: <XCircle className="w-4 h-4" />, color: 'text-red-600' },
    };
    const config = icons[status] || icons.pending;
    return <span className={config.color}>{config.icon}</span>;
}

// Copy button component
function CopyButton({ text }: { text: string }) {
    const [copied, setCopied] = useState(false);
    const handleCopy = () => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };
    return (
        <button onClick={handleCopy} className="text-[#6b7280] hover:text-[#1a1a1a] transition-colors">
            {copied ? <CheckCircle2 className="w-3.5 h-3.5 text-[#b8651a]" /> : <Link2 className="w-3.5 h-3.5" />}
        </button>
    );
}

// Buyer Returns Content - Alicinin iade talepleri
function BuyerReturnsContent() {
    const [myReturns, setMyReturns] = useState<ReturnRequest[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadMyReturns();
    }, []);

    const loadMyReturns = async () => {
        setLoading(true);
        try {
            const res = await returnsApi.getMyRequests();
            if (res.data?.data) {
                setMyReturns(res.data.data);
            }
        } catch (error) {
            console.error('Failed to load returns:', error);
        } finally {
            setLoading(false);
        }
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            pending: 'bg-amber-50 text-amber-700',
            approved: 'bg-[#fbeede] text-[#b8651a]',
            rejected: 'bg-red-50 text-red-700',
            shipped: 'bg-blue-50 text-blue-700',
            received: 'bg-[#fbeede] text-[#934f12]',
            refunded: 'bg-[#fbeede] text-[#934f12]',
            cancelled: 'bg-red-50 text-red-700',
        };
        return colors[status] || 'bg-[#faf8f6] text-[#6b7280]';
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
            </div>
        );
    }

    if (myReturns.length === 0) {
        return (
            <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                <Box className="w-16 h-16 mx-auto text-[#d1ccc9] mb-4" />
                <p className="text-sm text-[#6b7280]">İade talebiniz bulunmuyor</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <h2 className="text-lg font-black text-[#1a1a1a] mb-4">İade Taleplerim</h2>
            {myReturns.map((r) => (
                <div key={r.id} className="bg-white rounded-2xl border border-[#f0eceb] p-4">
                    <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div className="flex items-start gap-4">
                            {r.product?.image ? (
                                <Image src={r.product.image} alt={r.product.name} width={64} height={64} className="w-16 h-16 object-cover rounded-xl border border-[#f0eceb]" loading="lazy" />
                            ) : (
                                <div className="w-16 h-16 bg-[#faf8f6] rounded-xl border border-[#f0eceb] flex items-center justify-center">
                                    <Box className="w-8 h-8 text-[#d1ccc9]" />
                                </div>
                            )}
                            <div>
                                <p className="font-semibold text-[#1a1a1a]">{r.product?.name || 'Ürün'}</p>
                                <p className="text-sm text-[#6b7280]">#{r.order_number}</p>
                                <p className="text-sm text-[#6b7280] mt-1">
                                    <strong className="text-[#1a1a1a]">Sebep:</strong> {r.reason_label}
                                </p>
                                {r.reason_detail && (
                                    <p className="text-sm text-[#6b7280] mt-1">{r.reason_detail}</p>
                                )}
                                <p className="text-xs text-[#6b7280] mt-2">{new Date(r.created_at).toLocaleDateString('tr-TR')}</p>
                            </div>
                        </div>
                        <div className="flex flex-col items-end gap-2">
                            <span className={cn("px-3 py-1 rounded-full text-xs font-semibold", getStatusBadge(r.status))}>{r.status_label}</span>
                            {r.formatted_refund && (
                                <p className="text-sm font-bold text-[#b8651a]">{r.formatted_refund}</p>
                            )}
                            {r.seller_note && (
                                <p className="text-xs text-[#6b7280] max-w-[200px] text-right">Satıcı notu: {r.seller_note}</p>
                            )}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// Seller Return Requests Content - Saticinin gelen iade talepleri
function SellerReturnRequestsContent() {
    const [sellerReturns, setSellerReturns] = useState<ReturnRequest[]>([]);
    const [pendingCount, setPendingCount] = useState(0);
    const [loading, setLoading] = useState(true);
    const [processingId, setProcessingId] = useState<number | null>(null);

    useEffect(() => {
        loadSellerReturns();
    }, []);

    const loadSellerReturns = async () => {
        setLoading(true);
        try {
            const res = await returnsApi.getSellerRequests();
            if (res.data) {
                setSellerReturns(res.data.data);
                setPendingCount(res.data.pending_count);
            }
        } catch (error) {
            console.error('Failed to load seller returns:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleApprove = async (id: number) => {
        if (!confirm('Bu iade talebini onaylamak istediğinize emin misiniz?')) return;
        setProcessingId(id);
        try {
            const res = await returnsApi.approve(id);
            if (res.data?.success) {
                toast.success('İade talebi onaylandı');
                loadSellerReturns();
            } else {
                toast.error(res.error || 'Onaylama başarısız');
            }
        } catch (error) {
            toast.error('Onaylama başarısız');
        } finally {
            setProcessingId(null);
        }
    };

    const handleReject = async (id: number) => {
        const note = prompt('Red nedeninizi yazın:');
        if (!note) {
            toast.error('Red nedeni zorunludur');
            return;
        }
        setProcessingId(id);
        try {
            const res = await returnsApi.reject(id, note);
            if (res.data?.success) {
                toast.success('İade talebi reddedildi');
                loadSellerReturns();
            } else {
                toast.error(res.error || 'Reddetme başarısız');
            }
        } catch (error) {
            toast.error('Reddetme başarısız');
        } finally {
            setProcessingId(null);
        }
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            pending: 'bg-amber-50 text-amber-700',
            approved: 'bg-[#fbeede] text-[#b8651a]',
            rejected: 'bg-red-50 text-red-700',
            shipped: 'bg-blue-50 text-blue-700',
            received: 'bg-[#fbeede] text-[#934f12]',
            refunded: 'bg-[#fbeede] text-[#934f12]',
            cancelled: 'bg-red-50 text-red-700',
        };
        return colors[status] || 'bg-[#faf8f6] text-[#6b7280]';
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
            </div>
        );
    }

    if (sellerReturns.length === 0) {
        return (
            <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                <Box className="w-16 h-16 mx-auto text-[#d1ccc9] mb-4" />
                <p className="text-sm text-[#6b7280]">İade talebi bulunmuyor</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-black text-[#1a1a1a]">Gelen İade Talepleri</h2>
                {pendingCount > 0 && (
                    <span className="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">{pendingCount} bekleyen</span>
                )}
            </div>
            {sellerReturns.map((r) => {
                const productImage = (() => {
                    const img = r.product?.image;
                    if (!img) return null;
                    if (img.startsWith('http')) return img;
                    const apiUrl = process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || '';
                    return `${apiUrl}/storage/${img}`;
                })();
                const returnDate = r.created_at ? new Date(r.created_at) : null;
                const formattedDate = returnDate && !isNaN(returnDate.getTime())
                    ? returnDate.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric' })
                    : '';

                return (
                <div key={r.id} className="bg-white rounded-2xl border border-[#f0eceb] p-4 hover:border-[#fbeede] transition-colors">
                    <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div className="flex items-start gap-4">
                            {productImage ? (
                                <Image src={productImage} alt={r.product?.name || 'Ürün'} width={64} height={64} className="w-16 h-16 object-cover rounded-xl border border-[#f0eceb]" loading="lazy" />
                            ) : (
                                <div className="w-16 h-16 bg-[#faf8f6] rounded-xl border border-[#f0eceb] flex items-center justify-center">
                                    <Box className="w-8 h-8 text-[#d1ccc9]" />
                                </div>
                            )}
                            <div>
                                <p className="font-semibold text-[#1a1a1a]">{r.product?.name || 'Ürün'}</p>
                                <p className="text-sm text-[#6b7280]">#{r.order_number}</p>
                                <p className="text-sm text-[#6b7280]">Alıcı: {r.buyer?.business_name || '-'}</p>
                                <p className="text-sm text-[#6b7280] mt-1">
                                    <strong className="text-[#1a1a1a]">Sebep:</strong> {r.reason_label}
                                </p>
                                {r.reason_detail && (
                                    <p className="text-sm text-[#6b7280] mt-1 bg-[#faf8f6] p-2 rounded-xl">{r.reason_detail}</p>
                                )}
                                {formattedDate && <p className="text-xs text-[#6b7280] mt-2">{formattedDate}</p>}
                            </div>
                        </div>
                        <div className="flex flex-col items-end gap-2">
                            <span className={cn("px-3 py-1 rounded-full text-xs font-semibold", getStatusBadge(r.status))}>{r.status_label}</span>
                            {r.formatted_refund && (
                                <p className="text-sm font-bold text-[#b8651a]">{r.formatted_refund}</p>
                            )}
                            {r.status === 'pending' && (
                                <div className="flex gap-2 mt-2">
                                    <Button
                                        size="sm"
                                        onClick={() => handleApprove(r.id)}
                                        disabled={processingId === r.id}
                                        className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                    >
                                        {processingId === r.id ? <Loader2 className="w-4 h-4 animate-spin" /> : <Check className="w-4 h-4 mr-1" />}
                                        Onayla
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleReject(r.id)}
                                        disabled={processingId === r.id}
                                        className="text-red-600 border-red-200 hover:bg-red-50 rounded-xl"
                                    >
                                        <X className="w-4 h-4 mr-1" />
                                        Reddet
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                );
            })}
        </div>
    );
}

// Reusable Order Card Component
function OrderCard({
    order,
    isSeller,
    isIptaller,
    formatPrice,
    formatDate,
    getStatusLabel,
    onViewOrderDetail,
}: {
    order: any;
    isSeller: boolean;
    isIptaller: boolean;
    formatPrice: (price: number) => string;
    formatDate: (date: string) => string;
    getStatusLabel: (status: string) => string;
    onViewOrderDetail: (orderId: number, isSeller: boolean) => void;
}) {
    const allItems = order.items || [];
    const visibleItems = allItems.slice(0, 4);
    const extraCount = allItems.length - 4;

    const getItemImageUrl = (item: any) => {
        return item?.product?.image_url ||
            (item?.product?.image ?
                (item.product.image.startsWith('http') ? item.product.image : `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || ''}/storage/${item.product.image}`)
                : null) ||
            (item?.image ?
                (item.image.startsWith('http') ? item.image : `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || ''}/storage/${item.image}`)
                : null);
    };

    const sellerLabel = (() => {
        if (isIptaller) {
            if (order.seller_total !== undefined) {
                return { prefix: 'Alıcı', badge: 'seller' as const, name: order.buyer?.business_name || '-', role: (order.buyer as any)?.role };
            }
            const names = order.items?.reduce((acc: string[], item: any) => {
                const n = item.seller?.nickname || item.seller?.business_name;
                if (n && !acc.includes(n)) acc.push(n);
                return acc;
            }, [] as string[]);
            const firstSeller = order.items?.find((i: any) => i.seller)?.seller;
            return { prefix: 'Satıcı', badge: 'buyer' as const, name: names?.length ? names.join(', ') : '-', role: firstSeller?.role };
        }
        if (isSeller) {
            return { prefix: 'Alıcı', name: order.buyer?.nickname || order.buyer?.business_name || '-', role: (order.buyer as any)?.role };
        }
        const sellerNames = order.items?.reduce((names: string[], item: any) => {
            const name = item.seller?.nickname || item.seller?.business_name;
            if (name && !names.includes(name)) names.push(name);
            return names;
        }, [] as string[]);
        const firstSeller = order.items?.find((i: any) => i.seller)?.seller;
        return { prefix: 'Satıcı', name: sellerNames?.length ? sellerNames.join(', ') : '-', role: firstSeller?.role };
    })();

    return (
        <div className="bg-white rounded-2xl border border-[#f0eceb] p-3 sm:p-4 hover:border-[#fbeede] transition-colors">
            {/* ===== DESKTOP LAYOUT ===== */}
            <div className="hidden sm:flex gap-5 items-center">
                {/* Thumbnails */}
                <div className="w-[120px] shrink-0">
                    <div className="flex flex-wrap gap-1.5">
                        {visibleItems.map((item: any, i: number) => {
                            const imageUrl = getItemImageUrl(item);
                            const qty = item.quantity || 1;
                            return (
                                <div key={i} className="relative w-14 h-14 bg-white rounded-xl border border-[#f0eceb] overflow-hidden flex items-center justify-center">
                                    {imageUrl ? (
                                        <>
                                            <img src={imageUrl} alt={item?.product?.name || 'Ürün'} className="w-full h-full object-contain p-0.5" onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; (e.target as HTMLImageElement).nextElementSibling?.classList.remove('hidden'); }} />
                                            <Box className="w-7 h-7 text-[#d1ccc9] hidden" />
                                        </>
                                    ) : (
                                        <Box className="w-7 h-7 text-[#d1ccc9]" />
                                    )}
                                    {!(i === 3 && extraCount > 0) && <span className="absolute top-0 left-0 min-w-[20px] h-5 px-0.5 rounded-br-lg bg-[#1a1a1a]/80 text-white text-[10px] font-bold flex items-center justify-center">x{qty}</span>}
                                    {i === 3 && extraCount > 0 && <div className="absolute inset-0 bg-[#1a1a1a]/50 flex items-center justify-center"><span className="text-white text-sm font-bold">+{extraCount}</span></div>}
                                </div>
                            );
                        })}
                    </div>
                </div>
                {/* Details */}
                <div className="flex-1 min-w-0 space-y-1.5">
                    <div className="flex items-center gap-2">
                        <StatusIcon status={order.status} />
                        <span className={cn("font-bold text-[15px] leading-tight", order.status === 'shipped' && "text-[#b8651a]", order.status === 'delivered' && "text-[#b8651a]", order.status === 'cancelled' && "text-red-600", order.status === 'pending' && "text-amber-600", order.status === 'processing' && "text-blue-600")}>{getStatusLabel(order.status)}</span>
                        {!isSeller && order.sub_orders && order.sub_orders.length > 1 && (
                            <span className="text-xs text-[#6b7280] border border-[#f0eceb] rounded-full px-2 py-0.5">{order.sub_orders.length} Teslimat &middot; {allItems.length} Ürün</span>
                        )}
                    </div>
                    <div className="text-sm leading-relaxed space-y-0.5">
                        <div className="flex"><span className="w-14 shrink-0 text-[#6b7280]">Sipariş</span><span className="text-[#1a1a1a] truncate"><span className="font-medium">{order.order_number}</span><span className="ml-1 inline-flex align-middle"><CopyButton text={order.order_number} /></span></span></div>
                        <div className="flex"><span className="w-14 shrink-0 text-[#6b7280]">{sellerLabel.prefix}</span><span className="text-[#1a1a1a] truncate flex items-center gap-1.5">{isIptaller && sellerLabel.badge && (<Badge variant="outline" className={cn("text-[10px] px-1.5 py-0 shrink-0", sellerLabel.badge === 'seller' ? "bg-[#fbeede] text-[#b8651a] border-[#fbeede]" : "bg-blue-50 text-blue-700 border-blue-200")}>{sellerLabel.badge === 'seller' ? 'Satıcı' : 'Alıcı'}</Badge>)}<span className="font-medium truncate">{sellerLabel.name}</span>{sellerLabel.role && <SellerTypeBadge role={sellerLabel.role} size="sm" />}</span></div>
                        <div className="flex"><span className="w-14 shrink-0 text-[#6b7280]">Tarih</span><span className="text-[#6b7280]">{formatDate(order.created_at)}</span></div>
                    </div>
                    {!isSeller && order.payment_status === 'pending' && order.payment_method === 'credit_card' && (
                        <div className="mt-2 p-2 bg-amber-50 border border-amber-100 rounded-xl flex items-center justify-between">
                            <span className="text-xs text-amber-700 font-medium flex items-center gap-1"><AlertCircle className="w-3.5 h-3.5" />Ödeme bekleniyor</span>
                            <Link href={`/market/odeme/${order.id}`}><Button size="sm" className="bg-[#b8651a] hover:bg-[#934f12] text-white text-xs h-7 rounded-xl">Ödemeye Git</Button></Link>
                        </div>
                    )}
                    {!isSeller && (order.payment_status === 'expired' || order.payment_status === 'failed') && (
                        <div className="mt-2 p-2 bg-red-50 border border-red-100 rounded-xl"><span className="text-xs text-red-700 font-medium flex items-center gap-1"><AlertCircle className="w-3.5 h-3.5" />{order.payment_status === 'expired' ? 'Ödeme süresi doldu' : 'Ödeme başarısız'}</span></div>
                    )}
                </div>
                {/* Price & Button */}
                <div className="flex flex-col items-end gap-3 shrink-0">
                    <p className="text-xl font-black text-[#1a1a1a] whitespace-nowrap">{formatPrice(isIptaller ? (order.seller_total !== undefined ? order.seller_total : order.total_amount) : (isSeller ? order.seller_total : order.total_amount))}<span className="text-sm font-normal text-[#6b7280] ml-1">TL</span></p>
                    <Button size="sm" className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl" onClick={() => onViewOrderDetail(order.id, isIptaller ? order.seller_total !== undefined : isSeller)}>Siparişe Git</Button>
                </div>
            </div>

            {/* ===== MOBILE LAYOUT ===== */}
            <div className="sm:hidden space-y-3">
                {/* Status + Price */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <StatusIcon status={order.status} />
                        <span className={cn("font-bold text-[15px]", order.status === 'shipped' && "text-[#b8651a]", order.status === 'delivered' && "text-[#b8651a]", order.status === 'cancelled' && "text-red-600", order.status === 'pending' && "text-amber-600", order.status === 'processing' && "text-blue-600")}>{getStatusLabel(order.status)}</span>
                    </div>
                    <p className="text-lg font-black text-[#1a1a1a]">{formatPrice(isIptaller ? (order.seller_total !== undefined ? order.seller_total : order.total_amount) : (isSeller ? order.seller_total : order.total_amount))}<span className="text-xs font-normal text-[#6b7280] ml-0.5">TL</span></p>
                </div>

                {/* Product images row */}
                <div className="flex gap-2 overflow-x-auto pb-1">
                    {allItems.slice(0, 6).map((item: any, i: number) => {
                        const imageUrl = getItemImageUrl(item);
                        const qty = item.quantity || 1;
                        return (
                            <div key={i} className="relative w-16 h-16 bg-white rounded-xl border border-[#f0eceb] overflow-hidden flex items-center justify-center shrink-0">
                                {imageUrl ? (
                                    <>
                                        <img src={imageUrl} alt={item?.product?.name || 'Ürün'} className="w-full h-full object-contain p-0.5" onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; (e.target as HTMLImageElement).nextElementSibling?.classList.remove('hidden'); }} />
                                        <Box className="w-7 h-7 text-[#d1ccc9] hidden" />
                                    </>
                                ) : (
                                    <Box className="w-7 h-7 text-[#d1ccc9]" />
                                )}
                                <span className="absolute top-0 left-0 min-w-[18px] h-[18px] px-0.5 rounded-br-lg bg-[#1a1a1a]/80 text-white text-[9px] font-bold flex items-center justify-center">x{qty}</span>
                            </div>
                        );
                    })}
                    {allItems.length > 6 && (
                        <div className="w-16 h-16 bg-[#f0eceb] rounded-xl flex items-center justify-center shrink-0">
                            <span className="text-sm font-bold text-[#6b7280]">+{allItems.length - 6}</span>
                        </div>
                    )}
                </div>

                {/* Info rows */}
                <div className="text-[13px] leading-relaxed space-y-1">
                    <div className="flex"><span className="w-14 shrink-0 text-[#6b7280]">Sipariş</span><span className="text-[#1a1a1a] font-medium truncate">{order.order_number}</span><span className="ml-1 shrink-0"><CopyButton text={order.order_number} /></span></div>
                    <div className="flex"><span className="w-14 shrink-0 text-[#6b7280]">{sellerLabel.prefix}</span><span className="text-[#1a1a1a] font-medium truncate">{sellerLabel.name}</span>{sellerLabel.role && <span className="ml-1 shrink-0"><SellerTypeBadge role={sellerLabel.role} size="sm" /></span>}</div>
                    <div className="flex"><span className="w-14 shrink-0 text-[#6b7280]">Tarih</span><span className="text-[#6b7280]">{formatDate(order.created_at)}</span></div>
                </div>

                {/* Payment warning */}
                {!isSeller && order.payment_status === 'pending' && order.payment_method === 'credit_card' && (
                    <div className="p-2 bg-amber-50 border border-amber-100 rounded-xl flex items-center justify-between">
                        <span className="text-xs text-amber-700 font-medium flex items-center gap-1"><AlertCircle className="w-3.5 h-3.5" />Ödeme bekleniyor</span>
                        <Link href={`/market/odeme/${order.id}`}><Button size="sm" className="bg-[#b8651a] hover:bg-[#934f12] text-white text-xs h-7 rounded-xl">Ödemeye Git</Button></Link>
                    </div>
                )}
                {!isSeller && (order.payment_status === 'expired' || order.payment_status === 'failed') && (
                    <div className="p-2 bg-red-50 border border-red-100 rounded-xl"><span className="text-xs text-red-700 font-medium flex items-center gap-1"><AlertCircle className="w-3.5 h-3.5" />{order.payment_status === 'expired' ? 'Ödeme süresi doldu' : 'Ödeme başarısız'}</span></div>
                )}

                {/* Full-width button */}
                <Button className="w-full bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl" onClick={() => onViewOrderDetail(order.id, isIptaller ? order.seller_total !== undefined : isSeller)}>Siparişe Git</Button>
            </div>
        </div>
    );
}

// Orders Content - Referans tasarima uygun
export function OrdersContent({
    subNav,
    buyerOrders,
    sellerOrders,
    loadingOrders,
    onViewOrderDetail,
    statusFilter,
    onStatusFilterChange
}: {
    subNav: string;
    buyerOrders: Order[];
    sellerOrders: SellerOrder[];
    loadingOrders: boolean;
    onViewOrderDetail: (orderId: number, isSeller: boolean) => void;
    statusFilter: string;
    onStatusFilterChange: (filter: string) => void;
}) {
    const formatPrice = (price: number) => new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(price);

    const formatDate = (date: string) => {
        const d = new Date(date);
        return `${d.toLocaleDateString('tr-TR')} — ${d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            pending: 'Beklemede',
            confirmed: 'Onaylandı',
            processing: 'Hazırlanıyor',
            shipped: 'Kargoda',
            delivered: 'Tamamlandı',
            returned: 'İade Edildi',
            cancelled: 'İptal Edildi',
        };
        return labels[status] || status;
    };

    // Filter orders by status
    const filterOrders = (orders: any[]) => {
        if (statusFilter === 'all') return orders;
        if (statusFilter === 'active') return orders.filter(o => ['pending', 'confirmed', 'processing'].includes(o.status));
        if (statusFilter === 'cancelled_returned') return orders.filter(o => o.status === 'cancelled' || o.status === 'returned');
        return orders.filter(o => o.status === statusFilter);
    };

    // Show loading state
    if (loadingOrders) {
        return (
            <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
            </div>
        );
    }

    const isSeller = subNav === 'sattiklarim';
    const isIptaller = subNav === 'iptaller';
    const isIadelerim = subNav === 'iadelerim';
    const isIadeTalepleri = subNav === 'iade-talepleri';

    // Get base orders based on section
    let orders: any[] = [];
    if (isIptaller) {
        // Show cancelled and returned orders from both buyer and seller perspectives
        const cancelledBuyerOrders = buyerOrders.filter(o => o.status === 'cancelled' || o.status === 'returned');
        const cancelledSellerOrders = sellerOrders.filter(o => o.status === 'cancelled' || o.status === 'returned');
        orders = [...cancelledBuyerOrders, ...cancelledSellerOrders];
    } else {
        orders = isSeller ? sellerOrders : buyerOrders;
    }

    // Apply additional status filter
    const filteredOrders = isIptaller ? orders : filterOrders(orders);

    // For iadelerim and iade-talepleri sections, render special content
    if (isIadelerim) {
        return <BuyerReturnsContent />;
    }

    if (isIadeTalepleri) {
        return <SellerReturnRequestsContent />;
    }

    // Empty state
    if (orders.length === 0) {
        return (
            <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                {isSeller ? (
                    <>
                        <Store className="w-16 h-16 mx-auto text-[#d1ccc9] mb-4" />
                        <p className="text-sm text-[#6b7280] mb-4">Henüz satış yapmadınız</p>
                        <Link href="/market/hesabim?tab=ilanlarim">
                            <Button variant="outline" className="rounded-xl border-[#f0eceb] hover:border-[#fbeede] hover:bg-[#faf8f6]">Ürün Ekle</Button>
                        </Link>
                    </>
                ) : isIptaller ? (
                    <>
                        <XCircle className="w-16 h-16 mx-auto text-[#d1ccc9] mb-4" />
                        <p className="text-sm text-[#6b7280] mb-4">İptal edilmiş sipariş bulunmuyor</p>
                    </>
                ) : (
                    <>
                        <ShoppingBag className="w-16 h-16 mx-auto text-[#d1ccc9] mb-4" />
                        <p className="text-sm text-[#6b7280] mb-4">Henüz siparişiniz bulunmuyor</p>
                        <Link href="/market">
                            <Button variant="outline" className="rounded-xl border-[#f0eceb] hover:border-[#fbeede] hover:bg-[#faf8f6]">Alışverişe Başla</Button>
                        </Link>
                    </>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <h2 className="text-lg font-black text-[#1a1a1a]">
                        {isIptaller ? 'İptal Edilmiş Siparişler' : isSeller ? 'Tüm Sattıklarım' : 'Tüm Aldıklarım'}
                    </h2>
                    <div className="flex items-center gap-2">
                        <div className="relative">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#6b7280]" />
                            <Input
                                placeholder="Ürün, kullanıcı, sipariş ara"
                                className="pl-9 w-full sm:w-64 h-9 rounded-xl border-[#f0eceb] focus:border-[#fbeede]"
                            />
                        </div>
                    </div>
                </div>

                {/* Orders List */}
                <div className="space-y-3">
                    {filteredOrders.map((order: any) => (
                        <OrderCard
                            key={order.id}
                            order={order}
                            isSeller={isSeller}
                            isIptaller={isIptaller}
                            formatPrice={formatPrice}
                            formatDate={formatDate}
                            getStatusLabel={getStatusLabel}
                            onViewOrderDetail={onViewOrderDetail}
                        />
                    ))}
                </div>

                {filteredOrders.length === 0 && (
                    <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                        <Box className="w-12 h-12 mx-auto text-[#d1ccc9] mb-3" />
                        <p className="text-sm text-[#6b7280]">Bu filtreye uygun sipariş bulunamadı</p>
                        <Button variant="link" onClick={() => onStatusFilterChange('all')} className="text-[#b8651a]">
                            Tüm siparişleri göster
                        </Button>
                    </div>
                )}
        </div>
    );
}

// Order Detail View - Referans tasarima uygun
export function OrderDetailView({
    orderId,
    isSeller,
    onBack
}: {
    orderId: number;
    isSeller: boolean;
    onBack: () => void;
}) {
    const [order, setOrder] = useState<Order | null>(null);
    const [sellerOrderDetail, setSellerOrderDetail] = useState<SellerOrderDetail | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isCreatingInvoice, setIsCreatingInvoice] = useState(false);
    const [isCreatingErpInvoice, setIsCreatingErpInvoice] = useState(false);
    const [isCancellingOrder, setIsCancellingOrder] = useState(false);
    const [isUpdatingOrderStatus, setIsUpdatingOrderStatus] = useState(false);
    const [isCreatingShipment, setIsCreatingShipment] = useState(false);
    const [isUploadingInvoice, setIsUploadingInvoice] = useState(false);
    const [isCancellingBuyerOrder, setIsCancellingBuyerOrder] = useState(false);
    const [cancellingSubOrderId, setCancellingSubOrderId] = useState<number | null>(null);
    const [isConfirmingDelivery, setIsConfirmingDelivery] = useState(false);
    const [isRequestingReturn, setIsRequestingReturn] = useState(false);
    const [showReturnModal, setShowReturnModal] = useState(false);
    const [returnReasons, setReturnReasons] = useState<ReturnReason[]>([]);
    const [selectedReturnReason, setSelectedReturnReason] = useState('');
    const [returnReasonDetail, setReturnReasonDetail] = useState('');
    const [returnRequests, setReturnRequests] = useState<ReturnRequest[]>([]);
    const [isLoadingReturnRequests, setIsLoadingReturnRequests] = useState(false);
    const [selectedItems, setSelectedItems] = useState<{itemId: number; quantity: number}[]>([]);
    const [returnImages, setReturnImages] = useState<File[]>([]);

    useEffect(() => {
        loadOrderDetail();
    }, [orderId, isSeller]);

    const loadOrderDetail = async () => {
        setIsLoading(true);
        try {
            if (isSeller) {
                const response = await sellerApi.getOrderDetail(orderId);
                if (response.data?.data) {
                    setSellerOrderDetail(response.data.data);
                    // Load return requests for seller view
                    const returnsRes = await returnsApi.getOrderRequests(orderId);
                    if (returnsRes.data?.data) {
                        setReturnRequests(returnsRes.data.data);
                    }
                }
            } else {
                const response = await ordersApi.get(orderId);
                if (response.data?.order) {
                    setOrder(response.data.order);
                    // Load return requests for buyer view too
                    const returnsRes = await returnsApi.getOrderRequests(orderId);
                    if (returnsRes.data?.data) {
                        setReturnRequests(returnsRes.data.data);
                    }
                }
            }
        } catch (error) {
            console.error('Failed to load order:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleCreateInvoice = async () => {
        if (!sellerOrderDetail) return;
        setIsCreatingInvoice(true);
        try {
            const response = await invoiceApi.createForOrder(sellerOrderDetail.id);
            if (response.data?.success) {
                toast.success('Fatura oluşturuldu');
                // Reload order detail to get updated invoice info
                await loadOrderDetail();
            }
        } catch (error) {
            console.error('Failed to create invoice:', error);
            toast.error('Fatura oluşturulamadı');
        } finally {
            setIsCreatingInvoice(false);
        }
    };

    const handleCreateErpInvoice = async () => {
        if (!sellerOrderDetail) return;
        setIsCreatingErpInvoice(true);
        try {
            const response = await invoiceApi.createViaErp(sellerOrderDetail.id);
            if (response.data?.success) {
                toast.success('ERP üzerinden fatura kesildi');
                // Reload order detail to get updated invoice info
                await loadOrderDetail();
            } else {
                toast.error(response.data?.message || 'Fatura kesilemedi');
            }
        } catch (error) {
            console.error('Failed to create ERP invoice:', error);
            toast.error('ERP fatura hatası. Lütfen ERP entegrasyonunuzu kontrol edin.');
        } finally {
            setIsCreatingErpInvoice(false);
        }
    };

    // Siparis Iptal Et
    const handleCancelOrder = async () => {
        if (!sellerOrderDetail) return;

        if (!confirm('Bu siparişi iptal etmek istediğinizden emin misiniz?')) return;

        setIsCancellingOrder(true);
        try {
            const response = await ordersApi.cancel(sellerOrderDetail.id);
            if (response.data?.order) {
                toast.success('Sipariş iptal edildi');
                await loadOrderDetail();
            }
        } catch (error: unknown) {
            console.error('Failed to cancel order:', error);
            const errorMessage = error instanceof Error ? error.message : 'Sipariş iptal edilemedi';
            toast.error(errorMessage);
        } finally {
            setIsCancellingOrder(false);
        }
    };

    // Siparis Durumunu Guncelle
    const handleUpdateOrderStatus = async (newStatus: string) => {
        if (!sellerOrderDetail) return;

        const statusLabels: Record<string, string> = {
            confirmed: 'onaylamak',
            processing: 'hazırlanıyora almak',
            shipped: 'kargoya vermek',
            delivered: 'teslim edildi olarak işaretlemek',
        };

        if (!confirm(`Bu siparişi "${statusLabels[newStatus] || newStatus}" istediğinizden emin misiniz?`)) return;

        setIsUpdatingOrderStatus(true);
        try {
            const response = await ordersApi.updateStatus(sellerOrderDetail.id, newStatus);
            if (response.data?.sub_order || response.data?.order) {
                toast.success('Sipariş durumu güncellendi');
                await loadOrderDetail();
            } else if (response.error) {
                toast.error(response.error);
            }
        } catch (error: unknown) {
            console.error('Failed to update order status:', error);
            const errorMessage = error instanceof Error ? error.message : 'Sipariş durumu güncellenemedi';
            toast.error(errorMessage);
        } finally {
            setIsUpdatingOrderStatus(false);
        }
    };

    // Kargo Çıktısı Oluştur — etiket zaten varsa direkt aç, yoksa SetOrder çağır
    const handleCreateShipment = async () => {
        if (!sellerOrderDetail) return;

        // Eğer tracking number zaten varsa (kargo daha önce oluşturuldu), etiketi aç
        if (sellerOrderDetail.tracking_number) {
            const opened = await shippingApi.openLabel(sellerOrderDetail.id);
            if (!opened.success) {
                toast.error(opened.error || 'Etiket açılamadı');
            }
            return;
        }

        setIsCreatingShipment(true);
        try {
            const response = await shippingApi.createShipment(sellerOrderDetail.id);
            if (response.data?.success) {
                toast.success(response.data.message || 'Kargo gönderisi oluşturuldu');
                // Etiketi yeni sekmede aç
                await shippingApi.openLabel(sellerOrderDetail.id);
                await loadOrderDetail();
            } else {
                toast.error(response.data?.error || 'Kargo etiketi oluşturulamadı');
            }
        } catch (error: unknown) {
            console.error('Failed to create shipment:', error);
            toast.error('Kargo etiketi oluşturulamadı. Lütfen daha sonra tekrar deneyin.');
        } finally {
            setIsCreatingShipment(false);
        }
    };

    // e-Fatura Yükle
    const handleInvoiceFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file || !sellerOrderDetail) return;

        // Validate file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/xml'];
        if (!allowedTypes.includes(file.type)) {
            toast.error('Sadece PDF, JPEG, PNG veya XML dosyaları yüklenebilir');
            return;
        }

        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            toast.error('Dosya boyutu 10MB\'dan küçük olmalıdır');
            return;
        }

        setIsUploadingInvoice(true);
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('order_id', sellerOrderDetail.id.toString());
            formData.append('type', 'e_invoice');

            const response = await api.postFormData<{ success: boolean; message?: string }>(`/invoices/orders/${sellerOrderDetail.id}/upload`, formData);

            if (response.data?.success) {
                toast.success('e-Fatura yüklendi');
                await loadOrderDetail();
            } else {
                toast.error(response.data?.message || 'Fatura yüklenemedi');
            }
        } catch (error: unknown) {
            console.error('Failed to upload invoice:', error);
            toast.error('Fatura yüklenirken hata oluştu');
        } finally {
            setIsUploadingInvoice(false);
            // Reset input
            event.target.value = '';
        }
    };

    // Alici - Siparis/Teslimat Iptal Et (per sub_order)
    const handleCancelBuyerOrder = async (subOrderId?: number) => {
        if (!order) return;

        const sellerName = subOrderId
            ? order.sub_orders?.find((so: any) => so.id === subOrderId)?.seller_name
            : undefined;
        const confirmMsg = sellerName
            ? `${sellerName} teslimatını iptal etmek istediğinizden emin misiniz?`
            : 'Bu siparişi iptal etmek istediğinizden emin misiniz?';

        if (!confirm(confirmMsg)) return;

        if (subOrderId) setCancellingSubOrderId(subOrderId);
        setIsCancellingBuyerOrder(true);
        try {
            const response = await ordersApi.cancel(order.id, subOrderId);
            if (response.data?.order || response.data?.sub_order) {
                toast.success(subOrderId ? 'Teslimat iptal edildi' : 'Sipariş iptal edildi');
                await loadOrderDetail();
            }
        } catch (error: unknown) {
            console.error('Failed to cancel order:', error);
            toast.error(subOrderId ? 'Teslimat iptal edilemedi' : 'Sipariş iptal edilemedi');
        } finally {
            setIsCancellingBuyerOrder(false);
            setCancellingSubOrderId(null);
        }
    };

    // Alici - Teslimat Onayla (per sub_order)
    const [confirmingSubOrderId, setConfirmingSubOrderId] = useState<number | null>(null);
    const [deliveryConfirmDialog, setDeliveryConfirmDialog] = useState<{ open: boolean; subOrderId?: number; sellerName?: string }>({ open: false });
    const [deliveryConfirmChecked, setDeliveryConfirmChecked] = useState(false);

    const openDeliveryConfirmDialog = (subOrderId?: number) => {
        if (!order) return;
        const sellerName = subOrderId
            ? order.sub_orders?.find((so: any) => so.id === subOrderId)?.seller_name
            : undefined;
        setDeliveryConfirmChecked(false);
        setDeliveryConfirmDialog({ open: true, subOrderId, sellerName });
    };

    const handleConfirmDelivery = async () => {
        if (!order) return;
        const subOrderId = deliveryConfirmDialog.subOrderId;

        setDeliveryConfirmDialog(prev => ({ ...prev, open: false }));
        setIsConfirmingDelivery(true);
        if (subOrderId) setConfirmingSubOrderId(subOrderId);
        try {
            const response = await ordersApi.confirmDelivery(order.id, subOrderId);
            if (response.data) {
                toast.success('Teslimat onayınız alındı. Teşekkürler!');
                await loadOrderDetail();
            } else if (response.error) {
                toast.error(response.error);
            }
        } catch (error: unknown) {
            console.error('Failed to confirm delivery:', error);
            toast.error('Teslimat onaylanamadı');
        } finally {
            setIsConfirmingDelivery(false);
            setConfirmingSubOrderId(null);
        }
    };

    // Alici - Iade Talebi Modal Ac (per sub_order)
    const [returnSubOrderId, setReturnSubOrderId] = useState<number | null>(null);
    const handleRequestReturn = async (subOrderId?: number) => {
        if (!order) return;

        if (subOrderId) setReturnSubOrderId(subOrderId);

        // Reset item selection and images
        setSelectedItems([]);
        setReturnImages([]);

        // Load reasons if not already loaded
        if (returnReasons.length === 0) {
            try {
                const res = await returnsApi.getReasons();
                if (res.data?.reasons) {
                    setReturnReasons(res.data.reasons);
                }
            } catch (error) {
                console.error('Failed to load return reasons:', error);
            }
        }
        setShowReturnModal(true);
    };

    // Alici - Iade Talebi Gonder
    const handleSubmitReturn = async () => {
        if (!order || !selectedReturnReason) {
            toast.error('Lütfen bir iade nedeni seçin');
            return;
        }

        // If items are selected, create per-item return requests
        if (selectedItems.length > 0) {
            setIsRequestingReturn(true);
            try {
                let allSuccess = true;
                for (const sel of selectedItems) {
                    const res = await returnsApi.create({
                        order_id: order.id,
                        sub_order_id: returnSubOrderId || undefined,
                        order_item_id: sel.itemId,
                        reason: selectedReturnReason,
                        reason_detail: returnReasonDetail || undefined,
                        quantity: sel.quantity,
                    }, returnImages.length > 0 ? returnImages : undefined);

                    if (!res.data?.success) {
                        allSuccess = false;
                        toast.error(res.error || 'Bir ürün için iade talebi oluşturulamadı');
                    }
                }

                if (allSuccess) {
                    toast.success('İade talepleriniz oluşturuldu');
                }
                setShowReturnModal(false);
                setSelectedReturnReason('');
                setReturnReasonDetail('');
                setReturnSubOrderId(null);
                setSelectedItems([]);
                setReturnImages([]);
                await loadOrderDetail();
            } catch (error: unknown) {
                console.error('Failed to create return request:', error);
                toast.error('İade talebi oluşturulamadı');
            } finally {
                setIsRequestingReturn(false);
            }
            return;
        }

        // Fallback: full sub-order return (no items selected)
        setIsRequestingReturn(true);
        try {
            const res = await returnsApi.create({
                order_id: order.id,
                sub_order_id: returnSubOrderId || undefined,
                reason: selectedReturnReason,
                reason_detail: returnReasonDetail || undefined,
            }, returnImages.length > 0 ? returnImages : undefined);

            if (res.data?.success) {
                toast.success(res.data.message || 'İade talebiniz oluşturuldu');
                setShowReturnModal(false);
                setSelectedReturnReason('');
                setReturnReasonDetail('');
                setReturnSubOrderId(null);
                setSelectedItems([]);
                setReturnImages([]);
                await loadOrderDetail();
            } else {
                toast.error(res.error || 'İade talebi oluşturulamadı');
            }
        } catch (error: unknown) {
            console.error('Failed to create return request:', error);
            toast.error('İade talebi oluşturulamadı');
        } finally {
            setIsRequestingReturn(false);
        }
    };

    // Calculate estimated refund total for selected items
    const calculateRefundTotal = () => {
        if (!order) return 0;
        return selectedItems.reduce((total, sel) => {
            const item = order.items?.find((i: any) => i.id === sel.itemId);
            return total + (item ? item.unit_price * sel.quantity : 0);
        }, 0);
    };

    // Satici - Siparis İade Taleplerini Yukle
    const loadOrderReturnRequests = async () => {
        if (!sellerOrderDetail) return;
        setIsLoadingReturnRequests(true);
        try {
            const res = await returnsApi.getOrderRequests(sellerOrderDetail.id);
            if (res.data?.data) {
                setReturnRequests(res.data.data);
            }
        } catch (error) {
            console.error('Failed to load return requests:', error);
        } finally {
            setIsLoadingReturnRequests(false);
        }
    };

    // Satici - Iade Talebini Onayla
    const handleApproveReturn = async (returnId: number) => {
        if (!confirm('İade talebini onaylamak istediğinizden emin misiniz?')) return;

        try {
            const res = await returnsApi.approve(returnId);
            if (res.data?.success) {
                toast.success('İade talebi onaylandı');
                await loadOrderReturnRequests();
            } else {
                toast.error(res.error || 'Onaylama başarısız');
            }
        } catch (error) {
            toast.error('Onaylama başarısız');
        }
    };

    // Satici - Iade Talebini Reddet
    const handleRejectReturn = async (returnId: number) => {
        const note = prompt('Reddetme nedeninizi yazın:');
        if (!note) {
            toast.error('Red nedeni zorunludur');
            return;
        }

        try {
            const res = await returnsApi.reject(returnId, note);
            if (res.data?.success) {
                toast.success('İade talebi reddedildi');
                await loadOrderReturnRequests();
            } else {
                toast.error(res.error || 'Reddetme başarısız');
            }
        } catch (error) {
            toast.error('Reddetme başarısız');
        }
    };

    const getImageUrl = (image: string | undefined | null) => {
        if (!image) return null;
        if (image.startsWith('http')) return image;
        const apiUrl = process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || '';
        return `${apiUrl}/storage/${image}`;
    };

    const formatPrice = (price: number) => new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(price);

    const formatDate = (date: string) => {
        const d = new Date(date);
        return `${d.toLocaleDateString('tr-TR')} — ${d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            pending: 'Beklemede',
            confirmed: 'Onaylandı',
            processing: 'Hazırlanıyor',
            shipped: 'Kargoda',
            delivered: 'Tamamlandı',
            returned: 'İade Edildi',
            cancelled: 'İptal Edildi',
        };
        return labels[status] || status;
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
            </div>
        );
    }

    // Seller order detail view
    if (isSeller && sellerOrderDetail) {
        const autoCompleteDate = new Date(sellerOrderDetail.created_at);
        autoCompleteDate.setDate(autoCompleteDate.getDate() + 14);

        return (
            <div className="flex flex-col lg:flex-row gap-6">
                {/* Sol Sidebar - Siparis Ozeti */}
                <div className="lg:w-64 shrink-0">
                    <div className="lg:sticky lg:top-4 space-y-4">
                        {/* Geri Butonu */}
                        <button
                            onClick={onBack}
                            className="flex items-center gap-2 text-[#6b7280] hover:text-[#1a1a1a] transition-colors"
                        >
                            <ChevronRight className="w-4 h-4 rotate-180" />
                            <span className="text-sm font-medium">Geri</span>
                        </button>

                        {/* Alici Adi */}
                        <div>
                            <p className="font-black text-lg text-[#1a1a1a]">
                                {sellerOrderDetail.buyer ? sellerOrderDetail.buyer.name : 'Alıcı bilgisi gizli'}
                            </p>
                        </div>

                        {/* Durum */}
                        <div className="flex items-center gap-2">
                            <Truck className="w-5 h-5 text-[#b8651a]" />
                            <span className={cn(
                                "font-semibold",
                                sellerOrderDetail.status === 'shipped' && "text-[#b8651a]",
                                sellerOrderDetail.status === 'delivered' && "text-[#b8651a]",
                                sellerOrderDetail.status === 'returned' && "text-red-600",
                                sellerOrderDetail.status === 'cancelled' && "text-red-600"
                            )}>
                                {getStatusLabel(sellerOrderDetail.status)}
                            </span>
                        </div>

                        {/* Referans Numarası */}
                        <div>
                            <p className="text-xs text-[#6b7280]">Referans Numarası</p>
                            <div className="flex items-center gap-2">
                                <span className="font-mono font-medium text-[#1a1a1a]">{sellerOrderDetail.order_number}</span>
                                <CopyButton text={sellerOrderDetail.order_number} />
                            </div>
                        </div>

                        {/* Sipariş Tarihi */}
                        <div>
                            <p className="text-xs text-[#6b7280]">Sipariş Tarihi</p>
                            <p className="font-medium text-[#1a1a1a]">{formatDate(sellerOrderDetail.created_at)}</p>
                        </div>

                        {/* Otomatik Tamamlanma Notu */}
                        <div className="bg-[#fbeede] border border-[#fbeede] rounded-2xl p-3">
                            <p className="text-sm text-[#934f12]">
                                Sipariş {autoCompleteDate.toLocaleDateString('tr-TR')} tarihinde otomatik olarak tamamlanacaktır.
                            </p>
                        </div>

                        {/* Fatura Durumu - Sadece fatura kesildiyse goster */}
                        {sellerOrderDetail.invoice && (
                            <div className="bg-[#fbeede] border border-[#fbeede] rounded-2xl p-3 space-y-2">
                                <div className="flex items-center gap-2 text-[#b8651a]">
                                    <FileText className="w-4 h-4" />
                                    <span className="font-medium text-sm">Fatura Kesildi</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="font-mono text-sm text-[#1a1a1a]">{sellerOrderDetail.invoice.invoice_number}</span>
                                    <CopyButton text={sellerOrderDetail.invoice.invoice_number} />
                                </div>
                                <p className="text-xs text-[#6b7280]">{sellerOrderDetail.invoice.created_at}</p>
                                {sellerOrderDetail.invoice.pdf_path && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full mt-2"
                                        onClick={async () => {
                                            try {
                                                const response = await api.getBlob(`/invoices/${sellerOrderDetail.invoice?.id}/download`);
                                                if (response.error || !response.blob) {
                                                    toast.error(response.error || 'Fatura indirilemedi');
                                                    return;
                                                }
                                                const url = URL.createObjectURL(response.blob);
                                                window.open(url, '_blank');
                                            } catch (error) {
                                                console.error('Failed to download invoice:', error);
                                                toast.error('Fatura indirilemedi');
                                            }
                                        }}
                                    >
                                        <Eye className="w-4 h-4 mr-2" />
                                        Görüntüle
                                    </Button>
                                )}
                            </div>
                        )}

                        {/* Satis Sozlesmesi */}
                        <div className="space-y-1.5">
                            <p className="text-xs font-medium text-[#6b7280]">Satış Sözleşmesi</p>
                            <div className="flex gap-2">
                                <button
                                    onClick={async () => {
                                        try {
                                            const response = await contractsApi.downloadSalesContract(sellerOrderDetail.id);
                                            if (response.blob) {
                                                const url = window.URL.createObjectURL(response.blob);
                                                window.open(url, '_blank');
                                            }
                                        } catch (error) {
                                            toast.error('Sözleşme açılamadı');
                                        }
                                    }}
                                    className="flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 transition-colors"
                                >
                                    <Eye className="w-3.5 h-3.5" />
                                    <span>Görüntüle</span>
                                </button>
                                <button
                                    onClick={async () => {
                                        try {
                                            const response = await contractsApi.downloadSalesContract(sellerOrderDetail.id);
                                            if (response.blob) {
                                                const url = window.URL.createObjectURL(response.blob);
                                                const a = document.createElement('a');
                                                a.href = url;
                                                a.download = `satis-sozlesmesi-${sellerOrderDetail.order_number}.pdf`;
                                                document.body.appendChild(a);
                                                a.click();
                                                document.body.removeChild(a);
                                                window.URL.revokeObjectURL(url);
                                            }
                                        } catch (error) {
                                            toast.error('Sözleşme indirilemedi');
                                        }
                                    }}
                                    className="flex items-center gap-1.5 text-sm text-[#b8651a] hover:text-[#b8651a] transition-colors"
                                >
                                    <Download className="w-3.5 h-3.5" />
                                    <span>İndir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Sag Ana Icerik */}
                <div className="flex-1 min-w-0 space-y-6">
                    {/* Ürünler */}
                    <div>
                        <h2 className="text-lg font-black text-[#1a1a1a] mb-4">Ürünler</h2>
                        <div className="bg-white border border-[#f0eceb] rounded-2xl overflow-hidden">
                            {/* Ürün Listesi */}
                            <div className="divide-y divide-[#f0eceb]">
                                {sellerOrderDetail.items?.map((item) => {
                                    const imageUrl = getImageUrl(item.image);
                                    return (
                                        <div key={item.id} className="flex items-start gap-3 px-4 py-4">
                                            {/* Ürün Görseli */}
                                            <div className="w-14 h-14 bg-[#faf8f6] rounded-xl flex items-center justify-center shrink-0 border border-[#f0eceb] overflow-hidden">
                                                {imageUrl ? (
                                                    <img
                                                        src={imageUrl}
                                                        alt={item.product_name}
                                                        className="w-full h-full object-contain"
                                                        onError={(e) => {
                                                            (e.target as HTMLImageElement).style.display = 'none';
                                                            (e.target as HTMLImageElement).nextElementSibling?.classList.remove('hidden');
                                                        }}
                                                    />
                                                ) : null}
                                                <Box className={cn("w-6 h-6 text-[#d1ccc9]", imageUrl && "hidden")} />
                                            </div>

                                            {/* Ürün Bilgileri (dikey) */}
                                            <div className="flex-1 min-w-0 space-y-0.5">
                                                <p className="font-semibold text-sm text-[#1a1a1a] leading-snug">
                                                    {item.quantity > 1 && (
                                                        <span className="text-[#9ca3af] font-normal mr-1">{item.quantity}x</span>
                                                    )}
                                                    {item.product_name}
                                                </p>
                                                <p className="text-xs text-[#9ca3af]">
                                                    Miat: Miatsız Ürün
                                                </p>
                                                <p className="text-sm font-medium text-[#1a1a1a]">
                                                    Fiyat: {formatPrice(item.total_price)} TL
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Toplam */}
                            <div className="border-t border-[#f0eceb] p-4 bg-[#faf8f6]">
                                <div className="flex items-center justify-between mb-2">
                                    <Button variant="ghost" size="sm" className="text-[#b8651a] hover:text-[#934f12] hover:bg-[#fbeede] rounded-xl">
                                        Ürün Listesini Yazdır
                                    </Button>
                                    <div className="text-right">
                                        <p className="text-sm text-[#6b7280]">Ürünler</p>
                                        <p className="font-medium text-[#1a1a1a]">{formatPrice(sellerOrderDetail.financials.subtotal.value)} TL</p>
                                    </div>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleCancelOrder}
                                            disabled={isCancellingOrder || sellerOrderDetail.status === 'cancelled' || sellerOrderDetail.status === 'shipped' || sellerOrderDetail.status === 'delivered'}
                                            className="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700 rounded-xl"
                                        >
                                            {isCancellingOrder ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    İptal Ediliyor...
                                                </>
                                            ) : (
                                                'Siparişi İptal Et'
                                            )}
                                        </Button>
                                        {sellerOrderDetail.status === 'pending' && (
                                            <Button
                                                size="sm"
                                                onClick={() => handleUpdateOrderStatus('confirmed')}
                                                disabled={isUpdatingOrderStatus}
                                                className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                            >
                                                {isUpdatingOrderStatus ? (
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                ) : (
                                                    <CheckCircle2 className="w-4 h-4 mr-2" />
                                                )}
                                                Siparişi Onayla
                                            </Button>
                                        )}
                                        {sellerOrderDetail.status === 'confirmed' && (
                                            <Button
                                                size="sm"
                                                onClick={() => handleUpdateOrderStatus('processing')}
                                                disabled={isUpdatingOrderStatus}
                                                className="bg-blue-600 hover:bg-blue-700 text-white rounded-xl"
                                            >
                                                {isUpdatingOrderStatus ? (
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                ) : (
                                                    <Box className="w-4 h-4 mr-2" />
                                                )}
                                                Hazırlanıyor
                                            </Button>
                                        )}
                                        {sellerOrderDetail.status === 'processing' && (
                                            <Button
                                                size="sm"
                                                onClick={() => handleUpdateOrderStatus('shipped')}
                                                disabled={isUpdatingOrderStatus}
                                                className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                            >
                                                {isUpdatingOrderStatus ? (
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                ) : (
                                                    <Truck className="w-4 h-4 mr-2" />
                                                )}
                                                Kargoya Ver
                                            </Button>
                                        )}
                                        {sellerOrderDetail.status === 'shipped' && (
                                            <Button
                                                size="sm"
                                                onClick={() => handleUpdateOrderStatus('delivered')}
                                                disabled={isUpdatingOrderStatus}
                                                className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                            >
                                                {isUpdatingOrderStatus ? (
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                ) : (
                                                    <CheckCircle2 className="w-4 h-4 mr-2" />
                                                )}
                                                Teslim Edildi
                                            </Button>
                                        )}
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm text-[#6b7280]">Sipariş Toplamı</p>
                                        <p className="text-xl font-black text-[#1a1a1a]">{formatPrice(sellerOrderDetail.financials.subtotal.value)} TL</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Fatura ve Kargo Bilgileri */}
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Fatura Bilgileri - Sadece siparis onaylandiktan sonra gozukur */}
                        <div>
                            <h2 className="text-lg font-black text-[#1a1a1a] mb-4">Fatura Bilgileri</h2>
                            {sellerOrderDetail.status === 'pending' ? (
                                <div className="bg-amber-50 border border-amber-100 rounded-2xl p-4 space-y-3">
                                    <div className="flex items-center gap-3 text-amber-700">
                                        <AlertCircle className="w-5 h-5 shrink-0" />
                                        <div>
                                            <p className="font-semibold">Sipariş Onay Bekliyor</p>
                                            <p className="text-sm text-amber-600 mt-1">
                                                Fatura bilgileri, siparişi onayladıktan sonra görüntülenebilir.
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        size="sm"
                                        onClick={() => handleUpdateOrderStatus('confirmed')}
                                        disabled={isUpdatingOrderStatus}
                                        className="w-full bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                    >
                                        {isUpdatingOrderStatus ? (
                                            <>
                                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                Onaylanıyor...
                                            </>
                                        ) : (
                                            <>
                                                <CheckCircle2 className="w-4 h-4 mr-2" />
                                                Siparişi Onayla
                                            </>
                                        )}
                                    </Button>
                                </div>
                            ) : sellerOrderDetail.buyer === null ? (
                                <div className="bg-amber-50 border border-amber-100 rounded-2xl p-4">
                                    <div className="flex items-center gap-3 text-amber-700">
                                        <AlertCircle className="w-5 h-5 shrink-0" />
                                        <div>
                                            <p className="font-semibold">Alıcı Bilgisi Gizli</p>
                                            <p className="text-sm text-amber-600 mt-1">
                                                Alıcı bilgileri, siparişi onayladığınızda görünür olacak.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="bg-white border border-[#f0eceb] rounded-2xl p-4 space-y-4">
                                    <div className="space-y-3">
                                        <div>
                                            <p className="text-xs text-[#6b7280]">Firma Adı</p>
                                            <p className="font-medium text-[#1a1a1a]">{sellerOrderDetail.buyer.invoice_name || sellerOrderDetail.buyer.name}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-[#6b7280]">Adres</p>
                                            <p className="text-sm text-[#1a1a1a]">{sellerOrderDetail.buyer.address || '-'}</p>
                                            <p className="text-sm text-[#1a1a1a]">{sellerOrderDetail.buyer.district}, {sellerOrderDetail.buyer.city}</p>
                                        </div>
                                        {sellerOrderDetail.buyer.phone && (
                                            <div>
                                                <p className="text-xs text-[#6b7280]">Telefon</p>
                                                <p className="font-medium text-[#1a1a1a]">{sellerOrderDetail.buyer.phone}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* E-fatura Yukle Butonu */}
                                    <div className="relative">
                                        <input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png,.xml"
                                            onChange={handleInvoiceFileSelect}
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            disabled={isUploadingInvoice}
                                        />
                                        <Button
                                            className="w-full bg-[#1a1a1a] hover:bg-[#333333] text-white rounded-xl pointer-events-none"
                                            disabled={isUploadingInvoice}
                                        >
                                            {isUploadingInvoice ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Yükleniyor...
                                                </>
                                            ) : (
                                                <>
                                                    <FileText className="w-4 h-4 mr-2" />
                                                    e-Fatura Yükle
                                                </>
                                            )}
                                        </Button>
                                    </div>

                                    {/* Fatura Kes Butonu - ERP entegrasyonu ile */}
                                    <Button
                                        onClick={handleCreateErpInvoice}
                                        disabled={isCreatingErpInvoice || !!sellerOrderDetail.invoice}
                                        className="w-full bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                    >
                                        {isCreatingErpInvoice ? (
                                            <>
                                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                Fatura Kesiliyor...
                                            </>
                                        ) : sellerOrderDetail.invoice ? (
                                            <>
                                                <CheckCircle2 className="w-4 h-4 mr-2" />
                                                Fatura Kesildi
                                            </>
                                        ) : (
                                            <>
                                                <FileText className="w-4 h-4 mr-2" />
                                                Fatura Kes
                                            </>
                                        )}
                                    </Button>
                                </div>
                            )}
                        </div>

                        {/* Kargo Bilgileri */}
                        <div>
                            <h2 className="text-lg font-black text-[#1a1a1a] mb-4">Kargo Bilgileri</h2>
                            <div className="bg-white border border-[#f0eceb] rounded-2xl p-4 space-y-4">
                                {/* Kargo Sirketi */}
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <Truck className="w-8 h-8 text-[#6b7280]" />
                                        <span className="font-medium uppercase text-[#1a1a1a]">
                                            {sellerOrderDetail.shipping_provider || 'Kargo Seçilmedi'}
                                        </span>
                                    </div>
                                </div>

                                {/* Kargo Takip Kodu */}
                                {sellerOrderDetail.tracking_number && (
                                    <div>
                                        <p className="text-xs text-[#6b7280]">Entegrasyon Kodu</p>
                                        <div className="flex items-center gap-2">
                                            <p className="font-mono font-medium text-[#1a1a1a]">{sellerOrderDetail.tracking_number}</p>
                                            <CopyButton text={sellerOrderDetail.tracking_number} />
                                        </div>
                                    </div>
                                )}

                                {/* Kargolanmasi Gereken Tarih */}
                                {(() => {
                                    const shippingDeadline = new Date(sellerOrderDetail.created_at);
                                    shippingDeadline.setDate(shippingDeadline.getDate() + 2);
                                    const isOverdue = new Date() > shippingDeadline && sellerOrderDetail.status !== 'shipped' && sellerOrderDetail.status !== 'delivered';
                                    return (
                                        <div>
                                            <p className="text-xs text-[#6b7280]">Kargolanması Gereken Tarih</p>
                                            <p className={cn(
                                                "font-medium",
                                                isOverdue ? "text-red-600" : "text-[#1a1a1a]"
                                            )}>
                                                {shippingDeadline.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' })}
                                            </p>
                                            {isOverdue && (
                                                <p className="text-xs text-red-600 mt-1">
                                                    Kargo süresi geçmiş! Lütfen en kısa sürede kargoya verin.
                                                </p>
                                            )}
                                        </div>
                                    );
                                })()}

                                {/* Kargo Çıktısı Oluştur Butonu */}
                                <Button
                                    onClick={handleCreateShipment}
                                    disabled={isCreatingShipment || sellerOrderDetail.status === 'shipped' || sellerOrderDetail.status === 'delivered' || sellerOrderDetail.status === 'cancelled'}
                                    className="w-full bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                >
                                    {isCreatingShipment ? (
                                        <>
                                            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                            Oluşturuluyor...
                                        </>
                                    ) : sellerOrderDetail.tracking_number ? (
                                        <>
                                            <Eye className="w-4 h-4 mr-2" />
                                            Kargo Etiketini Gör
                                        </>
                                    ) : (
                                        <>
                                            <Truck className="w-4 h-4 mr-2" />
                                            Kargo Çıktısı Oluştur
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* İade Talepleri */}
                    {returnRequests.length > 0 && (
                        <div className="bg-red-50 border border-red-200 rounded-2xl overflow-hidden">
                            <div className="px-4 py-3 border-b border-red-200 bg-red-100/50">
                                <h3 className="font-bold text-red-800 flex items-center gap-2 text-sm">
                                    <RotateCcw className="w-4 h-4" />
                                    İade Talepleri ({returnRequests.length})
                                </h3>
                            </div>
                            <div className="divide-y divide-red-100">
                                {returnRequests.map((request) => {
                                    const statusColors: Record<string, string> = {
                                        pending: 'bg-amber-100 text-amber-800',
                                        approved: 'bg-green-100 text-green-800',
                                        rejected: 'bg-red-100 text-red-800',
                                        shipped: 'bg-blue-100 text-blue-800',
                                        received: 'bg-blue-100 text-blue-800',
                                        refunded: 'bg-accent-soft text-accent-hover',
                                        cancelled: 'bg-slate-100 text-slate-600',
                                    };
                                    const imageUrl = request.product?.image
                                        ? (request.product.image.startsWith('http') ? request.product.image : `/storage/${request.product.image}`)
                                        : null;
                                    return (
                                        <div key={request.id} className="p-3 sm:p-4">
                                            {/* Ürün bilgisi + durum + tutar */}
                                            <div className="flex items-start justify-between gap-3 mb-2">
                                                <div className="flex items-center gap-2 min-w-0">
                                                    <div className="relative w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-lg border border-red-200 overflow-hidden flex items-center justify-center shrink-0">
                                                        {imageUrl ? (
                                                            <Image src={imageUrl} alt="" fill className="object-contain p-0.5" sizes="48px" loading="lazy" />
                                                        ) : (
                                                            <Box className="w-5 h-5 text-[#d1ccc9]" />
                                                        )}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-xs sm:text-sm font-medium text-[#1a1a1a] truncate">{request.product?.name || 'Ürün'}</p>
                                                        <p className="text-xs text-red-700">{request.quantity} adet — {request.reason_label}</p>
                                                    </div>
                                                </div>
                                                <div className="text-right shrink-0">
                                                    <Badge className={cn("text-[10px] px-1.5 py-0.5", statusColors[request.status] || 'bg-slate-100 text-slate-600')}>
                                                        {request.status_label}
                                                    </Badge>
                                                    <p className="text-xs font-bold text-red-700 mt-1">{request.formatted_refund}</p>
                                                </div>
                                            </div>

                                            {/* Alıcı bilgisi */}
                                            {request.buyer && (
                                                <p className="text-xs text-[#6b7280] mb-1">Alıcı: <span className="font-medium text-[#1a1a1a]">{request.buyer.business_name}</span></p>
                                            )}

                                            {/* Açıklama */}
                                            {request.reason_detail && (
                                                <p className="text-xs text-red-600 bg-red-100/50 rounded-lg px-2 py-1.5 mt-1">{request.reason_detail}</p>
                                            )}

                                            {/* Satıcı notu */}
                                            {request.seller_note && (
                                                <p className="text-xs text-slate-600 bg-slate-50 rounded-lg px-2 py-1.5 mt-1">
                                                    <span className="font-medium">Notunuz:</span> {request.seller_note}
                                                </p>
                                            )}

                                            {/* Tarih */}
                                            <p className="text-[10px] text-[#6b7280] mt-2">{request.created_at}</p>

                                            {/* Onayla / Reddet butonları */}
                                            {request.status === 'pending' && (
                                                <div className="flex gap-2 mt-3">
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleApproveReturn(request.id)}
                                                        className="flex-1 bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                                    >
                                                        <Check className="w-4 h-4 mr-1" />
                                                        Onayla
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handleRejectReturn(request.id)}
                                                        className="flex-1 text-red-600 border-red-200 hover:bg-red-50 rounded-xl"
                                                    >
                                                        <X className="w-4 h-4 mr-1" />
                                                        Reddet
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {/* Hakediş Özeti */}
                    <div className="bg-[#fbeede] border border-[#fbeede] rounded-2xl p-4">
                        <h3 className="font-black text-[#b8651a] mb-4 flex items-center gap-2">
                            <Wallet className="w-5 h-5" />
                            Hakediş Özeti
                        </h3>
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span className="text-[#6b7280]">{sellerOrderDetail.financials.subtotal.label}</span>
                                <span className="font-medium">{sellerOrderDetail.financials.subtotal.formatted}</span>
                            </div>
                            {sellerOrderDetail.financials.deductions
                                .filter(d => d.visible !== false && d.value > 0)
                                .map((d, i) => (
                                    <div key={i} className="flex justify-between text-sm">
                                        <span className="text-[#6b7280]">{d.label} {d.rate && `(%${d.rate})`}</span>
                                        <span className="text-red-600">-{d.formatted}</span>
                                    </div>
                                ))
                            }
                            <div className="border-t border-[#fbeede] pt-2 mt-2">
                                <div className="flex justify-between text-lg font-bold">
                                    <span className="text-[#b8651a]">{sellerOrderDetail.financials.net_amount.label}</span>
                                    <span className="text-[#b8651a]">{sellerOrderDetail.financials.net_amount.formatted}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // Buyer order detail view
    if (!isSeller && order) {
        return (
            <div className="flex flex-col lg:flex-row gap-6">
                {/* Sol Sidebar - Siparis Ozeti */}
                <div className="lg:w-72 shrink-0">
                    <div className="lg:sticky lg:top-4 space-y-4">
                        {/* Geri Butonu */}
                        <button
                            onClick={onBack}
                            className="flex items-center gap-2 text-[#6b7280] hover:text-[#1a1a1a] transition-colors"
                        >
                            <ChevronRight className="w-4 h-4 rotate-180" />
                            <span className="text-sm font-medium">Geri</span>
                        </button>

                        {/* Durum */}
                        <div className="flex items-center gap-2">
                            <StatusIcon status={order.status} />
                            <span className={cn(
                                "font-semibold",
                                order.status === 'shipped' && "text-[#b8651a]",
                                order.status === 'delivered' && "text-[#b8651a]",
                                order.status === 'cancelled' && "text-red-600",
                                order.status === 'pending' && "text-amber-600"
                            )}>
                                {getStatusLabel(order.status)}
                            </span>
                        </div>

                        {/* Teslimat ozeti */}
                        {order.sub_orders && order.sub_orders.length > 1 && (
                            <p className="text-sm text-[#6b7280]">
                                {order.sub_orders.length} Teslimat, {order.items?.length || 0} Ürün
                            </p>
                        )}

                        {/* Referans Numarası */}
                        <div>
                            <p className="text-xs text-[#6b7280]">Referans Numarası</p>
                            <div className="flex items-center gap-2">
                                <span className="font-mono font-medium text-[#1a1a1a]">{order.order_number}</span>
                                <CopyButton text={order.order_number} />
                            </div>
                        </div>

                        {/* Sipariş Tarihi */}
                        <div>
                            <p className="text-xs text-[#6b7280]">Sipariş Tarihi</p>
                            <p className="font-medium text-[#1a1a1a]">{formatDate(order.created_at)}</p>
                        </div>

                        {/* Teslimat Tarihi */}
                        {order.delivered_at && (
                            <div>
                                <p className="text-xs text-[#6b7280]">Teslim Tarihi</p>
                                <p className="font-medium text-[#b8651a]">{formatDate(order.delivered_at)}</p>
                            </div>
                        )}

                        {/* Teslimat Onay Durumu (sidebar ozet) */}
                        {(() => {
                            const subs = order.sub_orders || [];
                            const confirmed = subs.filter((so: any) => so.buyer_confirmed_at);
                            const awaitingConfirm = subs.filter((so: any) => so.status === 'delivered' && !so.buyer_confirmed_at);
                            if (order.buyer_confirmed_at) {
                                return (
                                    <div className="bg-[#fbeede] border border-[#fbeede] rounded-2xl p-3 flex items-center gap-2 text-[#b8651a]">
                                        <CheckCircle2 className="w-5 h-5 shrink-0" />
                                        <div>
                                            <p className="font-medium text-sm">Tüm Teslimatlar Onaylandı</p>
                                            <p className="text-xs text-[#b8651a]">{formatDate(order.buyer_confirmed_at)}</p>
                                        </div>
                                    </div>
                                );
                            }
                            if (confirmed.length > 0 || awaitingConfirm.length > 0) {
                                return (
                                    <div className="bg-[#faf8f6] border border-[#f0eceb] rounded-2xl p-3 text-sm text-[#6b7280]">
                                        {confirmed.length > 0 && (
                                            <p className="text-[#b8651a]">{confirmed.length} teslimat onaylandı</p>
                                        )}
                                        {awaitingConfirm.length > 0 && (
                                            <p className="text-amber-600">{awaitingConfirm.length} teslimat onay bekliyor</p>
                                        )}
                                    </div>
                                );
                            }
                            return null;
                        })()}

                        {/* Iptal ve Iade butonlari artik her Teslimat section'inda */}
                    </div>
                </div>

                {/* Sag Ana Icerik */}
                <div className="flex-1 min-w-0 space-y-6">
                    {/* Teslimat Sections */}
                    {(() => {
                        const subOrders = order.sub_orders || [];
                        const hasMultipleDeliveries = subOrders.length > 1;

                        const renderItemRow = (item: any, index: number) => {
                            const imageUrl = item.product?.image_url || getImageUrl(item.product?.image);
                            const expiryDate = item.expiry_date
                                ? new Date(item.expiry_date).toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' })
                                : null;
                            return (
                                <div key={index} className="flex items-start gap-3 px-4 py-4">
                                    {/* Ürün Resmi */}
                                    <div className="w-14 h-14 bg-[#faf8f6] rounded-xl flex items-center justify-center shrink-0 border border-[#f0eceb] overflow-hidden">
                                        {imageUrl ? (
                                            <img
                                                src={imageUrl}
                                                alt={item.product?.name || `Ürün #${item.product_id}`}
                                                className="w-full h-full object-contain"
                                                onError={(e) => {
                                                    (e.target as HTMLImageElement).style.display = 'none';
                                                    (e.target as HTMLImageElement).nextElementSibling?.classList.remove('hidden');
                                                }}
                                            />
                                        ) : null}
                                        <Box className={cn("w-6 h-6 text-[#d1ccc9]", imageUrl && "hidden")} />
                                    </div>
                                    {/* Ürün Bilgisi (dikey) */}
                                    <div className="flex-1 min-w-0 space-y-0.5">
                                        <p className="font-semibold text-sm text-[#1a1a1a] leading-snug">
                                            {item.quantity > 1 && (
                                                <span className="text-[#9ca3af] font-normal mr-1">{item.quantity}x</span>
                                            )}
                                            {item.product?.name || `Ürün #${item.product_id}`}
                                        </p>
                                        {expiryDate && (
                                            <p className="text-xs text-[#b8651a]">Miat: {expiryDate}</p>
                                        )}
                                        <p className="text-sm font-medium text-[#1a1a1a]">
                                            Fiyat: {formatPrice(item.total_price)} TL
                                        </p>
                                    </div>
                                </div>
                            );
                        };

                        return (
                            <div className="space-y-4">
                                {subOrders.length > 0 ? (
                                    subOrders.map((subOrder: any, idx: number) => {
                                        const subOrderItems = order.items?.filter(
                                            (item: any) => item.sub_order_id === subOrder.id
                                        ) || [];

                                        return (
                                            <div key={subOrder.id} className="bg-white border border-[#f0eceb] rounded-2xl overflow-hidden">
                                                {/* Teslimat Header */}
                                                <div className="px-4 py-3 bg-[#faf8f6] border-b border-[#f0eceb]">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        {hasMultipleDeliveries && (
                                                            <span className="text-xs font-medium text-[#9ca3af] shrink-0">
                                                                {idx + 1}/{subOrders.length}
                                                            </span>
                                                        )}
                                                        <div className="flex items-center gap-1.5 flex-1 min-w-0">
                                                            <Store className="w-4 h-4 text-[#b8651a] shrink-0" />
                                                            <span className="text-sm font-semibold text-[#1a1a1a] truncate">
                                                                {subOrder.seller_name}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-1.5 shrink-0">
                                                            <StatusIcon status={subOrder.status} />
                                                            <span className={cn(
                                                                "text-sm font-medium whitespace-nowrap",
                                                                subOrder.status === 'shipped' && "text-[#b8651a]",
                                                                subOrder.status === 'delivered' && "text-[#b8651a]",
                                                                subOrder.status === 'returned' && "text-red-600",
                                                                subOrder.status === 'cancelled' && "text-red-600",
                                                                subOrder.status === 'pending' && "text-amber-600",
                                                                subOrder.status === 'processing' && "text-blue-600",
                                                                subOrder.status === 'confirmed' && "text-blue-600"
                                                            )}>
                                                                {subOrder.status_label}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Kargo Bilgisi - belirgin banner */}
                                                {subOrder.tracking_number && (
                                                    <div className="px-4 py-2.5 bg-[#fbeede] border-b border-[#fbeede] flex items-center gap-3 flex-wrap">
                                                        <div className="w-8 h-8 bg-white rounded-xl flex items-center justify-center border border-[#fbeede] shrink-0">
                                                            <Truck className="w-4 h-4 text-[#b8651a]" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <span className="text-xs font-bold text-[#934f12] uppercase block">
                                                                {subOrder.shipping_provider || 'Kargo'}
                                                            </span>
                                                            <div className="flex items-center gap-1.5">
                                                                <span className="text-sm font-mono font-medium text-[#1a1a1a] truncate">
                                                                    {subOrder.tracking_number}
                                                                </span>
                                                                <CopyButton text={subOrder.tracking_number} />
                                                            </div>
                                                        </div>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="text-[#934f12] border-[#fbeede] bg-white hover:bg-[#fbeede] text-xs rounded-xl shrink-0"
                                                        >
                                                            <Truck className="w-3.5 h-3.5 mr-1" />
                                                            Takip Et
                                                        </Button>
                                                    </div>
                                                )}

                                                {/* Items in this Teslimat */}
                                                <div className="divide-y divide-[#f0eceb]">
                                                    {subOrderItems.map((item: any, i: number) => renderItemRow(item, i))}
                                                    {subOrderItems.length === 0 && (
                                                        <div className="p-4 text-center text-sm text-[#6b7280]">
                                                            Bu teslimat için ürün bilgisi bulunamadı
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Fatura Bilgisi per sub_order */}
                                                {subOrder.invoice && (
                                                    <div className="px-4 py-3 bg-[#fbeede] border-t border-[#fbeede] flex items-center justify-between">
                                                        <div className="flex items-center gap-2.5">
                                                            <div className="w-8 h-8 bg-white rounded-xl flex items-center justify-center border border-[#fbeede]">
                                                                <FileText className="w-4 h-4 text-[#b8651a]" />
                                                            </div>
                                                            <div>
                                                                <p className="text-xs font-medium text-[#b8651a]">
                                                                    Fatura #{subOrder.invoice.invoice_number}
                                                                </p>
                                                                <p className="text-xs text-[#b8651a]">
                                                                    {subOrder.invoice.formatted_total || `${formatPrice(subOrder.invoice.total_amount)} TL`}
                                                                    {' '}&middot;{' '}{subOrder.invoice.created_at}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        {subOrder.invoice.pdf_path && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="text-[#b8651a] border-[#fbeede] bg-white hover:bg-[#fbeede] text-xs rounded-xl"
                                                                onClick={async () => {
                                                                    try {
                                                                        const response = await api.getBlob(`/invoices/${subOrder.invoice.id}/download`);
                                                                        if (response.error || !response.blob) {
                                                                            toast.error(response.error || 'Fatura indirilemedi');
                                                                            return;
                                                                        }
                                                                        const url = URL.createObjectURL(response.blob);
                                                                        window.open(url, '_blank');
                                                                    } catch (error) {
                                                                        console.error('Failed to download invoice:', error);
                                                                        toast.error('Fatura indirilemedi');
                                                                    }
                                                                }}
                                                            >
                                                                <Eye className="w-3.5 h-3.5 mr-1" />
                                                                Görüntüle
                                                            </Button>
                                                        )}
                                                    </div>
                                                )}

                                                {/* Satis Sozlesmesi per sub_order */}
                                                {subOrder.status !== 'pending' && subOrder.status !== 'cancelled' && (
                                                    <div className="px-4 py-3 bg-[#faf8f6] border-t border-[#f0eceb] space-y-2">
                                                        {/* Başlık satırı */}
                                                        <div className="flex items-center gap-2">
                                                            <FileText className="w-4 h-4 text-[#b8651a] shrink-0" />
                                                            <div>
                                                                <p className="text-xs font-semibold text-[#1a1a1a]">Mesafeli Satış Sözleşmesi</p>
                                                                <p className="text-[11px] text-[#6b7280]">{subOrder.seller_name}</p>
                                                            </div>
                                                        </div>
                                                        {/* Butonlar */}
                                                        <div className="flex gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="flex-1 text-[#6b7280] border-[#f0eceb] bg-white hover:bg-white text-xs rounded-xl h-8"
                                                                onClick={async () => {
                                                                    try {
                                                                        const response = await contractsApi.downloadSalesContract(order.id, subOrder.seller_id);
                                                                        if (response.blob) {
                                                                            const url = window.URL.createObjectURL(response.blob);
                                                                            window.open(url, '_blank');
                                                                        }
                                                                    } catch (error) {
                                                                        toast.error('Sözleşme açılamadı');
                                                                    }
                                                                }}
                                                            >
                                                                <Eye className="w-3.5 h-3.5 mr-1.5" />
                                                                Görüntüle
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="flex-1 text-[#b8651a] border-[#fbeede] bg-white hover:bg-[#fbeede] text-xs rounded-xl h-8"
                                                                onClick={async () => {
                                                                    try {
                                                                        const response = await contractsApi.downloadSalesContract(order.id, subOrder.seller_id);
                                                                        if (response.blob) {
                                                                            const url = window.URL.createObjectURL(response.blob);
                                                                            const a = document.createElement('a');
                                                                            a.href = url;
                                                                            a.download = `satis-sozlesmesi-${order.order_number}.pdf`;
                                                                            document.body.appendChild(a);
                                                                            a.click();
                                                                            document.body.removeChild(a);
                                                                            window.URL.revokeObjectURL(url);
                                                                        }
                                                                    } catch (error) {
                                                                        toast.error('Sözleşme indirilemedi');
                                                                    }
                                                                }}
                                                            >
                                                                <Download className="w-3.5 h-3.5 mr-1.5" />
                                                                İndir
                                                            </Button>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Per-sub_order Teslim Aldim butonu */}
                                                {subOrder.status === 'delivered' && !subOrder.buyer_confirmed_at && (
                                                    <div className="px-4 py-3 bg-[#fbeede] border-t border-[#fbeede]">
                                                        <Button
                                                            onClick={() => openDeliveryConfirmDialog(subOrder.id)}
                                                            disabled={isConfirmingDelivery}
                                                            className="w-full bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                                                        >
                                                            {confirmingSubOrderId === subOrder.id ? (
                                                                <>
                                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                                    Onaylanıyor...
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <CheckCircle2 className="w-4 h-4 mr-2" />
                                                                    Teslim Aldım
                                                                </>
                                                            )}
                                                        </Button>
                                                    </div>
                                                )}

                                                {/* Onaylandi badge per sub_order */}
                                                {subOrder.buyer_confirmed_at && (
                                                    <div className="px-4 py-2.5 bg-[#fbeede] border-t border-[#fbeede] flex items-center gap-2 text-[#b8651a]">
                                                        <CheckCircle2 className="w-4 h-4 shrink-0" />
                                                        <span className="text-xs font-medium">Teslimat Onaylandı</span>
                                                    </div>
                                                )}

                                                {/* Per-sub_order Iptal butonu */}
                                                {(['pending', 'confirmed', 'processing'].includes(subOrder.status)) && (
                                                    <div className="px-4 py-2.5 border-t border-[#f0eceb] flex justify-end">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleCancelBuyerOrder(subOrder.id)}
                                                            disabled={isCancellingBuyerOrder}
                                                            className="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700 text-xs rounded-xl"
                                                        >
                                                            {cancellingSubOrderId === subOrder.id ? (
                                                                <>
                                                                    <Loader2 className="w-3.5 h-3.5 mr-1 animate-spin" />
                                                                    İptal Ediliyor...
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <XCircle className="w-3.5 h-3.5 mr-1" />
                                                                    Teslimatı İptal Et
                                                                </>
                                                            )}
                                                        </Button>
                                                    </div>
                                                )}

                                                {/* Per-sub_order Iade Talebi butonu - sadece shipped/delivered ve iade edilmemis */}
                                                {(subOrder.status === 'shipped' || subOrder.status === 'delivered') && subOrder.status !== 'returned' && (
                                                    <div className="px-4 py-2.5 border-t border-[#f0eceb] flex justify-end">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleRequestReturn(subOrder.id)}
                                                            className="text-[#b8651a] border-[#fbeede] hover:bg-[#fbeede] hover:text-[#934f12] text-xs rounded-xl"
                                                        >
                                                            <Box className="w-3.5 h-3.5 mr-1" />
                                                            İade Talebi Oluştur
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })
                                ) : (
                                    /* Fallback: flat list for orders without sub_orders */
                                    <div className="bg-white border border-[#f0eceb] rounded-2xl overflow-hidden">
                                        <div className="divide-y divide-[#f0eceb]">
                                            {order.items?.map((item: any, i: number) => renderItemRow(item, i))}
                                        </div>
                                    </div>
                                )}

                                {/* İade Bilgileri */}
                                {returnRequests.length > 0 && (
                                    <div className="bg-red-50 border border-red-200 rounded-2xl overflow-hidden">
                                        <div className="px-4 py-3 border-b border-red-200 bg-red-100/50">
                                            <h3 className="font-bold text-red-800 flex items-center gap-2 text-sm">
                                                <RotateCcw className="w-4 h-4" />
                                                İade Talepleri ({returnRequests.length})
                                            </h3>
                                        </div>
                                        <div className="divide-y divide-red-100">
                                            {returnRequests.map((req) => {
                                                const statusColors: Record<string, string> = {
                                                    pending: 'bg-amber-100 text-amber-800',
                                                    approved: 'bg-green-100 text-green-800',
                                                    rejected: 'bg-red-100 text-red-800',
                                                    shipped: 'bg-blue-100 text-blue-800',
                                                    received: 'bg-blue-100 text-blue-800',
                                                    refunded: 'bg-accent-soft text-accent-hover',
                                                    cancelled: 'bg-slate-100 text-slate-600',
                                                };
                                                const statusLabels: Record<string, string> = {
                                                    pending: 'Beklemede',
                                                    approved: 'Onaylandı',
                                                    rejected: 'Reddedildi',
                                                    shipped: 'Kargoya Verildi',
                                                    received: 'Teslim Alındı',
                                                    refunded: 'İade Edildi',
                                                    cancelled: 'İptal Edildi',
                                                };
                                                const reasonLabels: Record<string, string> = {
                                                    wrong_product: 'Yanlış Ürün',
                                                    damaged: 'Hasarlı Ürün',
                                                    not_as_described: 'Açıklamaya Uymuyor',
                                                    quality_issue: 'Kalite Sorunu',
                                                    expired: 'Son Kullanma Tarihi Geçmiş',
                                                    changed_mind: 'Fikir Değişikliği',
                                                    other: 'Diğer',
                                                };
                                                const item = order.items?.find((it: any) => it.id === req.order_item_id);
                                                return (
                                                    <div key={req.id} className="p-3 sm:p-4">
                                                        <div className="flex items-start justify-between gap-3 mb-2">
                                                            <div className="flex items-center gap-2 min-w-0">
                                                                {item && (
                                                                    <div className="relative w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-lg border border-red-200 overflow-hidden flex items-center justify-center shrink-0">
                                                                        {item.product?.image_url ? (
                                                                            <Image src={item.product.image_url} alt="" fill className="object-contain p-0.5" sizes="48px" loading="lazy" />
                                                                        ) : (
                                                                            <Box className="w-5 h-5 text-[#d1ccc9]" />
                                                                        )}
                                                                    </div>
                                                                )}
                                                                <div className="min-w-0">
                                                                    <p className="text-xs sm:text-sm font-medium text-[#1a1a1a] truncate">{item?.product?.name || 'Ürün'}</p>
                                                                    <p className="text-xs text-red-700">{req.quantity} adet — {reasonLabels[req.reason] || req.reason}</p>
                                                                </div>
                                                            </div>
                                                            <div className="text-right shrink-0">
                                                                <Badge className={cn("text-[10px] px-1.5 py-0.5", statusColors[req.status] || 'bg-slate-100 text-slate-600')}>
                                                                    {statusLabels[req.status] || req.status}
                                                                </Badge>
                                                                {(req.refund_amount ?? 0) > 0 && (
                                                                    <p className="text-xs font-bold text-red-700 mt-1">{formatPrice(req.refund_amount ?? 0)} TL</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                        {req.reason_detail && (
                                                            <p className="text-xs text-red-600 bg-red-100/50 rounded-lg px-2 py-1.5 mt-1">{req.reason_detail}</p>
                                                        )}
                                                        {req.seller_note && (
                                                            <p className="text-xs text-slate-600 bg-slate-50 rounded-lg px-2 py-1.5 mt-1">
                                                                <span className="font-medium">Satıcı notu:</span> {req.seller_note}
                                                            </p>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* Sipariş Toplamı */}
                                <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                    <div className="flex justify-end">
                                        <div className="text-right space-y-1">
                                            <div className="flex justify-between gap-8">
                                                <span className="text-sm text-[#6b7280]">Ürünler</span>
                                                <span className="font-medium text-[#1a1a1a]">{formatPrice(order.subtotal)} TL</span>
                                            </div>
                                            {order.shipping_cost && order.shipping_cost > 0 && (
                                                <div className="flex justify-between gap-8">
                                                    <span className="text-sm text-[#6b7280]">Kargo</span>
                                                    <span className="font-medium text-[#1a1a1a]">{formatPrice(order.shipping_cost)} TL</span>
                                                </div>
                                            )}
                                            {returnRequests.filter(r => r.status === 'approved' || r.status === 'refunded').length > 0 && (
                                                <div className="flex justify-between gap-8 text-red-600">
                                                    <span className="text-sm">İade Tutarı</span>
                                                    <span className="font-medium">-{formatPrice(returnRequests.filter(r => r.status === 'approved' || r.status === 'refunded').reduce((sum, r) => sum + (r.refund_amount ?? 0), 0))} TL</span>
                                                </div>
                                            )}
                                            {(() => {
                                                const refundTotal = returnRequests.filter(r => r.status === 'approved' || r.status === 'refunded').reduce((sum, r) => sum + (r.refund_amount ?? 0), 0);
                                                const netTotal = order.total_amount - refundTotal;
                                                return (
                                                    <div className="flex justify-between gap-8 pt-2 border-t border-[#f0eceb]">
                                                        <span className="font-medium text-[#1a1a1a]">Sipariş Toplamı</span>
                                                        <span className="text-xl font-black text-[#1a1a1a]">{formatPrice(refundTotal > 0 ? netTotal : order.total_amount)} TL</span>
                                                    </div>
                                                );
                                            })()}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })()}

                    {/* Teslimat Adresi */}
                    <div>
                        <h2 className="text-lg font-black text-[#1a1a1a] mb-4">Teslimat Adresi</h2>
                        <div className="bg-white border border-[#f0eceb] rounded-2xl p-4 space-y-3">
                            {typeof order.shipping_address === 'object' && (
                                <>
                                    <div>
                                        <p className="text-xs text-[#6b7280]">Alıcı</p>
                                        <p className="font-medium text-[#1a1a1a]">{order.shipping_address.name}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-[#6b7280]">Adres</p>
                                        <p className="text-sm text-[#1a1a1a]">{order.shipping_address.address}</p>
                                        <p className="text-sm text-[#1a1a1a]">{order.shipping_address.district}, {order.shipping_address.city}</p>
                                        {order.shipping_address.postal_code && (
                                            <p className="text-sm text-[#6b7280]">{order.shipping_address.postal_code}</p>
                                        )}
                                    </div>
                                    {order.shipping_address.phone && (
                                        <div>
                                            <p className="text-xs text-[#6b7280]">Telefon</p>
                                            <p className="font-medium text-[#1a1a1a]">{order.shipping_address.phone}</p>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                    {/* Fatura bilgileri artik her Teslimat section'inda gosteriliyor */}
                </div>

                {/* Teslim Aldım Onay Dialog */}
                <Dialog open={deliveryConfirmDialog.open} onOpenChange={(open) => setDeliveryConfirmDialog(prev => ({ ...prev, open }))}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <CheckCircle2 className="w-5 h-5 text-[#b8651a]" />
                                Teslim Aldım
                            </DialogTitle>
                            <DialogDescription>
                                {deliveryConfirmDialog.sellerName
                                    ? `"${deliveryConfirmDialog.sellerName}" teslimatını onaylıyorsunuz.`
                                    : 'Siparişinizin teslimatını onaylıyorsunuz.'}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <label className="flex items-start gap-3 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    checked={deliveryConfirmChecked}
                                    onChange={(e) => setDeliveryConfirmChecked(e.target.checked)}
                                    className="mt-0.5 w-5 h-5 rounded border-[#f0eceb] text-[#b8651a] focus:ring-[#b8651a] cursor-pointer"
                                />
                                <span className="text-sm text-[#6b7280] leading-relaxed group-hover:text-[#1a1a1a]">
                                    Ürünü eksiksiz ve sorunsuz aldığımı kabul ediyorum.
                                </span>
                            </label>
                        </div>
                        <DialogFooter className="gap-2 sm:gap-0">
                            <Button
                                variant="outline"
                                onClick={() => setDeliveryConfirmDialog({ open: false })}
                            >
                                Vazgeç
                            </Button>
                            <Button
                                onClick={handleConfirmDelivery}
                                disabled={!deliveryConfirmChecked}
                                className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl disabled:opacity-50"
                            >
                                <CheckCircle2 className="w-4 h-4 mr-2" />
                                Teslim Aldım — Onayla
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Iade Talebi Modal */}
                <Dialog open={showReturnModal} onOpenChange={setShowReturnModal}>
                    <DialogContent className="sm:max-w-lg max-h-[85vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>İade Talebi Oluştur</DialogTitle>
                            <DialogDescription>
                                Sipariş #{order.order_number}
                                {returnSubOrderId && order.sub_orders?.find((so: any) => so.id === returnSubOrderId)
                                    ? ` — ${order.sub_orders.find((so: any) => so.id === returnSubOrderId)?.seller_name}`
                                    : ''
                                } için iade talebi oluşturun.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-5 py-4">
                            {/* Section 1: Ürün Secimi */}
                            {(() => {
                                const subOrderItems = returnSubOrderId
                                    ? order.items?.filter((item: any) => item.sub_order_id === returnSubOrderId) || []
                                    : order.items || [];
                                return subOrderItems.length > 0 && (
                                    <div className="space-y-3">
                                        <Label className="text-sm font-semibold">İade Edilecek Ürünler</Label>
                                        <div className="space-y-2 border border-[#f0eceb] rounded-2xl p-3">
                                            {subOrderItems.map((item: any) => {
                                                const isSelected = selectedItems.some(s => s.itemId === item.id);
                                                const selectedQty = selectedItems.find(s => s.itemId === item.id)?.quantity || item.quantity;
                                                const itemImageUrl = getImageUrl(item?.product?.image || item?.image);

                                                return (
                                                    <div key={item.id} className={cn(
                                                        "flex items-center gap-3 p-2.5 rounded-xl border transition-colors cursor-pointer",
                                                        isSelected ? "border-[#fbeede] bg-[#fbeede]" : "border-[#f0eceb] hover:bg-[#faf8f6]"
                                                    )}
                                                    onClick={() => {
                                                        if (isSelected) {
                                                            setSelectedItems(prev => prev.filter(s => s.itemId !== item.id));
                                                        } else {
                                                            setSelectedItems(prev => [...prev, { itemId: item.id, quantity: item.quantity }]);
                                                        }
                                                    }}
                                                    >
                                                        {/* Checkbox */}
                                                        <div className={cn(
                                                            "w-5 h-5 rounded border-2 flex items-center justify-center shrink-0",
                                                            isSelected ? "bg-[#b8651a] border-[#b8651a]" : "border-[#d1ccc9]"
                                                        )}>
                                                            {isSelected && <Check className="w-3.5 h-3.5 text-white" />}
                                                        </div>

                                                        {/* Ürün Görseli */}
                                                        <div className="relative w-10 h-10 bg-[#faf8f6] rounded-lg border border-[#f0eceb] overflow-hidden flex items-center justify-center shrink-0">
                                                            {itemImageUrl ? (
                                                                <Image src={itemImageUrl} alt={item.product?.name || item.product_name} fill className="object-contain" sizes="40px" loading="lazy" />
                                                            ) : (
                                                                <Box className="w-5 h-5 text-[#d1ccc9]" />
                                                            )}
                                                        </div>

                                                        {/* Ürün Bilgisi */}
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-[#1a1a1a] line-clamp-1">{item.product?.name || item.product_name}</p>
                                                            <p className="text-xs text-[#6b7280]">{formatPrice(item.unit_price)} TL x {item.quantity}</p>
                                                        </div>

                                                        {/* Miktar Secici — sadece secili iken */}
                                                        {isSelected && item.quantity > 1 && (
                                                            <div className="flex items-center gap-1.5" onClick={(e) => e.stopPropagation()}>
                                                                <button
                                                                    type="button"
                                                                    className="w-6 h-6 rounded-lg bg-[#faf8f6] border border-[#f0eceb] hover:bg-[#f0eceb] flex items-center justify-center"
                                                                    onClick={() => {
                                                                        setSelectedItems(prev =>
                                                                            prev.map(s => s.itemId === item.id
                                                                                ? { ...s, quantity: Math.max(1, s.quantity - 1) }
                                                                                : s
                                                                            )
                                                                        );
                                                                    }}
                                                                >
                                                                    <Minus className="w-3 h-3" />
                                                                </button>
                                                                <span className="text-sm font-semibold w-6 text-center">{selectedQty}</span>
                                                                <button
                                                                    type="button"
                                                                    className="w-6 h-6 rounded-lg bg-[#faf8f6] border border-[#f0eceb] hover:bg-[#f0eceb] flex items-center justify-center"
                                                                    onClick={() => {
                                                                        setSelectedItems(prev =>
                                                                            prev.map(s => s.itemId === item.id
                                                                                ? { ...s, quantity: Math.min(item.quantity, s.quantity + 1) }
                                                                                : s
                                                                            )
                                                                        );
                                                                    }}
                                                                >
                                                                    <Plus className="w-3 h-3" />
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        {/* Tahmini iade tutarı */}
                                        {selectedItems.length > 0 && (
                                            <div className="flex items-center justify-between px-3 py-2 bg-[#fbeede] border border-[#fbeede] rounded-xl">
                                                <span className="text-sm text-[#934f12]">Tahmini iade tutarı</span>
                                                <span className="text-sm font-bold text-[#b8651a]">{formatPrice(calculateRefundTotal())} TL</span>
                                            </div>
                                        )}
                                    </div>
                                );
                            })()}

                            {/* Section 2: Sebep + Açıklama */}
                            <div className="space-y-2">
                                <Label htmlFor="return-reason">İade Nedeni *</Label>
                                <select
                                    id="return-reason"
                                    value={selectedReturnReason}
                                    onChange={(e) => setSelectedReturnReason(e.target.value)}
                                    className="w-full px-3 py-2 border border-[#f0eceb] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#fbeede]"
                                >
                                    <option value="">Neden seçin...</option>
                                    {returnReasons.map((reason) => (
                                        <option key={reason.value} value={reason.value}>
                                            {reason.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="return-detail">Açıklama (Opsiyonel)</Label>
                                <Textarea
                                    id="return-detail"
                                    placeholder="İade talebinizle ilgili ek açıklama yazabilirsiniz..."
                                    value={returnReasonDetail}
                                    onChange={(e) => setReturnReasonDetail(e.target.value)}
                                    rows={3}
                                />
                            </div>

                            {/* Section 3: Görsel Yukleme */}
                            <div className="space-y-2">
                                <Label>Görseller (Opsiyonel, max 5)</Label>
                                <div className="flex flex-wrap gap-2">
                                    {returnImages.map((file, i) => (
                                        <div key={i} className="relative w-16 h-16 rounded-xl border border-[#f0eceb] overflow-hidden group">
                                            <img
                                                src={URL.createObjectURL(file)}
                                                alt={`Görsel ${i + 1}`}
                                                className="w-full h-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setReturnImages(prev => prev.filter((_, idx) => idx !== i))}
                                                className="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white rounded-bl-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <X className="w-3 h-3" />
                                            </button>
                                        </div>
                                    ))}
                                    {returnImages.length < 5 && (
                                        <label className="w-16 h-16 rounded-xl border-2 border-dashed border-[#d1ccc9] hover:border-[#fbeede] flex items-center justify-center cursor-pointer transition-colors">
                                            <Camera className="w-5 h-5 text-[#6b7280]" />
                                            <input
                                                type="file"
                                                accept="image/*"
                                                className="hidden"
                                                onChange={(e) => {
                                                    const file = e.target.files?.[0];
                                                    if (file) {
                                                        if (file.size > 5 * 1024 * 1024) {
                                                            toast.error('Görsel boyutu 5MB\'dan küçük olmalıdır');
                                                            return;
                                                        }
                                                        setReturnImages(prev => [...prev, file]);
                                                    }
                                                    e.target.value = '';
                                                }}
                                            />
                                        </label>
                                    )}
                                </div>
                                {returnImages.length === 0 && (
                                    <p className="text-xs text-[#6b7280]">Hasar veya sorunlu ürüne ait fotoğraflar ekleyebilirsiniz</p>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowReturnModal(false)}>
                                İptal
                            </Button>
                            <Button
                                onClick={handleSubmitReturn}
                                disabled={isRequestingReturn || !selectedReturnReason}
                                className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl"
                            >
                                {isRequestingReturn ? (
                                    <>
                                        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                        Gönderiliyor...
                                    </>
                                ) : (
                                    selectedItems.length > 0
                                        ? `${selectedItems.length} Ürün İade Et`
                                        : 'Talep Oluştur'
                                )}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        );
    }

    // Not found
    return (
        <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
            <Box className="w-16 h-16 mx-auto text-[#d1ccc9] mb-4" />
            <p className="text-sm text-[#6b7280] mb-4">Sipariş bulunamadı</p>
            <Button variant="outline" onClick={onBack} className="rounded-xl border-[#f0eceb] hover:border-[#fbeede]">Geri Dön</Button>
        </div>
    );
}
