'use client';

import { useEffect, useMemo, useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import { toast } from 'sonner';
import { ChevronRight, Plus, Check, MapPin, Truck, CreditCard, Loader2, X, Tag, Shield, ChevronLeft, Package } from 'lucide-react';
import {
    ordersApi,
    CreateOrderData,
    ShippingAddress,
    shippingApi,
    ShippingOption,
    couponsApi,
    ApplyCouponResponse,
    addressApi,
    Address,
    paymentsApi,
    PaymentConfig,
} from '@/lib/api';
import { useCartStore } from '@/stores/useCartStore';
import { useAuth } from '@/contexts/AuthContext';
import { EmbeddedCardForm, type CardFormRef } from '@/components/payment/CardForm';
import { CityDistrictSelect } from '@/components/forms/CityDistrictSelect';

type Step = 'info' | 'shipping' | 'payment';

const STEPS: { key: Step; label: string; icon: typeof MapPin }[] = [
    { key: 'info', label: 'Bilgi', icon: MapPin },
    { key: 'shipping', label: 'Kargo', icon: Truck },
    { key: 'payment', label: 'Ödeme', icon: CreditCard },
];

const MIN_ORDER_AMOUNT = 2000;

const formatPrice = (price: number) =>
    new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(price);

export default function CheckoutPage() {
    const router = useRouter();
    const { user, isAuthenticated, isLoading: authLoading } = useAuth();
    const {
        itemCount,
        fetchCart,
        validateCart,
        validationIssues,
        selectedItemsBySeller,
        selectedTotal,
    } = useCartStore();

    const activeItemsBySeller = selectedItemsBySeller();
    const activeTotal = selectedTotal();

    const [currentStep, setCurrentStep] = useState<Step>('info');
    const [isLoading, setIsLoading] = useState(false);

    // Address
    const [address, setAddress] = useState<ShippingAddress>({
        name: '', phone: '', address: '', city: '', district: '', postal_code: '',
    });
    const [savedAddresses, setSavedAddresses] = useState<Address[]>([]);
    const [selectedAddressId, setSelectedAddressId] = useState<number | 'profile' | 'new' | null>(null);
    const [loadingAddresses, setLoadingAddresses] = useState(true);
    const [newAddress, setNewAddress] = useState<{
        title: string;
        name: string;
        phone: string;
        address: string;
        city: string;
        district: string;
        city_id?: number;
        district_id?: number;
    }>({ title: '', name: '', phone: '', address: '', city: '', district: '' });
    const [savingNewAddress, setSavingNewAddress] = useState(false);
    const [saveNewAddressFlag, setSaveNewAddressFlag] = useState(true);
    const [notes, setNotes] = useState('');

    // Payment
    const [paymentConfig, setPaymentConfig] = useState<PaymentConfig | null>(null);
    const [selectedPaymentMethod] = useState<string>('credit_card');
    const [loadingPayment, setLoadingPayment] = useState(true);
    const cardFormRef = useRef<CardFormRef>(null);
    const [threeDSecureHtml, setThreeDSecureHtml] = useState<string | null>(null);

    // Shipping
    const shippingDepsKey = useMemo(
        () => activeItemsBySeller.map(g => `${g.seller?.id}:${g.subtotal}:${g.items.length}`).join(','),
        [activeItemsBySeller]
    );
    const [sellerShipping, setSellerShipping] = useState<Record<number, {
        options: ShippingOption[]; selected: string; cost: number; loading: boolean; desi: number; subtotal: number;
    }>>({});

    // Coupon
    const [couponCode, setCouponCode] = useState('');
    const [appliedCoupon, setAppliedCoupon] = useState<ApplyCouponResponse['coupon'] | null>(null);
    const [couponDiscount, setCouponDiscount] = useState(0);
    const [couponLoading, setCouponLoading] = useState(false);
    const [couponError, setCouponError] = useState<string | null>(null);

    // Profile address (verified)
    const profileAddress: ShippingAddress = {
        name: user?.business_name || '',
        phone: user?.phone || '',
        address: user?.address || '',
        city: user?.city || '',
        district: user?.district || '',
        postal_code: '',
    };

    const handleAddressSelect = (addressId: number | 'profile' | 'new', addressList?: Address[]) => {
        setSelectedAddressId(addressId);
        if (addressId === 'profile') {
            setAddress(profileAddress);
        } else if (addressId === 'new') {
            setAddress({ name: '', phone: '', address: '', city: '', district: '', postal_code: '' });
        } else {
            const list = addressList || savedAddresses;
            const sel = list.find(a => a.id === addressId);
            if (sel) {
                setAddress({
                    name: sel.name, phone: sel.phone, address: sel.address,
                    city: sel.city, district: sel.district || '', postal_code: sel.postal_code || '',
                    city_id: sel.city_id, district_id: sel.district_id,
                });
            }
        }
    };

    // Init
    useEffect(() => { fetchCart(); validateCart(); }, [fetchCart, validateCart]);

    useEffect(() => {
        if (!authLoading && itemCount > 0 && activeItemsBySeller.length === 0) {
            router.push('/market/sepet');
        }
    }, [authLoading, itemCount, activeItemsBySeller.length, router]);

    // Load addresses
    useEffect(() => {
        const load = async () => {
            if (!isAuthenticated) return;
            setLoadingAddresses(true);
            try {
                const res = await addressApi.getAll();
                const addrs = res.data?.data || [];
                setSavedAddresses(addrs);
                if (selectedAddressId === null) {
                    const def = addrs.find(a => a.is_default);
                    if (def) handleAddressSelect(def.id, addrs);
                    else if (addrs.length > 0) handleAddressSelect(addrs[0].id, addrs);
                    else handleAddressSelect('profile');
                }
            } catch {
                if (selectedAddressId === null) handleAddressSelect('profile');
            } finally {
                setLoadingAddresses(false);
            }
        };
        load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isAuthenticated]);

    // Sync when profile selected
    useEffect(() => {
        if (selectedAddressId === 'profile' && user) setAddress(profileAddress);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [user, selectedAddressId]);

    useEffect(() => {
        if (selectedAddressId === 'new') {
            setAddress({
                name: newAddress.name, phone: newAddress.phone, address: newAddress.address,
                city: newAddress.city, district: newAddress.district, postal_code: '',
                city_id: newAddress.city_id, district_id: newAddress.district_id,
            });
        }
    }, [selectedAddressId, newAddress]);

    // Payment config
    useEffect(() => {
        const load = async () => {
            setLoadingPayment(true);
            try {
                const res = await paymentsApi.getConfig();
                if (res.data) setPaymentConfig(res.data);
            } finally { setLoadingPayment(false); }
        };
        load();
    }, []);

    // Shipping fetch
    useEffect(() => {
        const fetchShipping = async () => {
            if (activeTotal <= 0 || activeItemsBySeller.length === 0) return;
            const newMap: typeof sellerShipping = {};
            await Promise.all(activeItemsBySeller.map(async (group) => {
                const sellerId = group.seller?.id;
                if (!sellerId) return;
                const sellerDesi = group.items.reduce((sum, item) => {
                    const desi = (item.product as unknown as { desi?: number }).desi || 0.5;
                    return sum + (desi * item.quantity);
                }, 0) || 1;
                newMap[sellerId] = { options: [], selected: '', cost: 0, loading: true, desi: sellerDesi, subtotal: group.subtotal };
                try {
                    const res = await shippingApi.getOptions(sellerDesi, group.subtotal);
                    if (res.data?.options && res.data.options.length > 0) {
                        newMap[sellerId] = {
                            ...newMap[sellerId],
                            options: res.data.options,
                            selected: res.data.options[0].provider,
                            cost: res.data.options[0].price,
                            loading: false,
                        };
                    } else {
                        newMap[sellerId] = {
                            ...newMap[sellerId],
                            options: [{ provider: 'free', name: 'Ücretsiz Kargo', price: 0, original_price: 0, formatted_price: 'Ücretsiz', is_free: true, remaining_for_free: 0, remaining_for_free_formatted: null }],
                            selected: 'free', cost: 0, loading: false,
                        };
                    }
                } catch {
                    newMap[sellerId] = {
                        ...newMap[sellerId],
                        options: [{ provider: 'free', name: 'Ücretsiz Kargo', price: 0, original_price: 0, formatted_price: 'Ücretsiz', is_free: true, remaining_for_free: 0, remaining_for_free_formatted: null }],
                        selected: 'free', cost: 0, loading: false,
                    };
                }
            }));
            setSellerShipping(newMap);
        };
        fetchShipping();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [shippingDepsKey]);

    const handleShippingChange = (sellerId: number, provider: string) => {
        setSellerShipping(prev => {
            const data = prev[sellerId];
            if (!data) return prev;
            const opt = data.options.find(o => o.provider === provider);
            return { ...prev, [sellerId]: { ...data, selected: provider, cost: opt?.price || 0 } };
        });
    };

    const totalShippingCost = Object.values(sellerShipping).reduce((sum, s) => sum + s.cost, 0);
    const allShippingSelected = activeItemsBySeller.every(g => g.seller?.id && sellerShipping[g.seller.id]?.selected);
    const anyShippingLoading = Object.values(sellerShipping).some(s => s.loading);

    // Coupon handlers
    const handleApplyCoupon = async () => {
        if (!couponCode.trim()) { setCouponError('Lütfen bir kupon kodu girin.'); return; }
        setCouponLoading(true); setCouponError(null);
        try {
            const res = await couponsApi.apply(couponCode, activeTotal);
            if (res.data?.valid) {
                setAppliedCoupon(res.data.coupon || null);
                setCouponDiscount(res.data.discount_amount || 0);
                setCouponCode('');
                toast.success(res.data.message);
            } else {
                setCouponError(res.data?.message || res.error || 'Kupon uygulanamadı.');
            }
        } catch {
            setCouponError('Kupon kontrol edilirken hata oluştu.');
        } finally {
            setCouponLoading(false);
        }
    };
    const handleRemoveCoupon = () => {
        setAppliedCoupon(null); setCouponDiscount(0); setCouponError(null);
    };

    const hasCriticalIssues = validationIssues.some(i => i.type === 'unavailable' || i.type === 'stock');
    const isBelowMinOrder = activeTotal < MIN_ORDER_AMOUNT;
    const remainingForMinOrder = MIN_ORDER_AMOUNT - activeTotal;
    const grandTotal = activeTotal + totalShippingCost - couponDiscount;

    // Validation per step
    const validateInfoStep = (): string | null => {
        if (!address.name || !address.phone || !address.address || !address.city) return 'Lütfen adres bilgilerinizi tam doldurun.';
        if (!address.city_id) return 'Lütfen il seçin.';
        const phoneDigits = address.phone.replace(/\D/g, '').replace(/^90/, '').replace(/^0/, '');
        if (phoneDigits.length !== 10 || !phoneDigits.startsWith('5')) return 'Geçerli bir cep telefonu girin (5XX XXX XX XX).';
        return null;
    };

    const handleNext = async () => {
        if (currentStep === 'info') {
            const err = validateInfoStep();
            if (err) { toast.error(err); return; }
            // Save new address if user ticked the box
            if (selectedAddressId === 'new' && saveNewAddressFlag && newAddress.name && newAddress.address) {
                setSavingNewAddress(true);
                try {
                    const res = await addressApi.create({
                        title: newAddress.title || newAddress.name,
                        name: newAddress.name, phone: newAddress.phone, address: newAddress.address,
                        city: newAddress.city, district: newAddress.district,
                        city_id: newAddress.city_id, district_id: newAddress.district_id,
                        is_default: savedAddresses.length === 0,
                    });
                    if (res.data?.data) {
                        const created = res.data.data;
                        setSavedAddresses(prev => [...prev, created]);
                        setSelectedAddressId(created.id);
                    }
                } catch {
                    toast.error('Adres kaydedilemedi, yine de devam ediliyor.');
                } finally {
                    setSavingNewAddress(false);
                }
            }
            setCurrentStep('shipping');
            return;
        }
        if (currentStep === 'shipping') {
            if (anyShippingLoading) { toast.error('Kargo seçenekleri yükleniyor...'); return; }
            if (!allShippingSelected) { toast.error('Lütfen tüm satıcılar için kargo firması seçin.'); return; }
            setCurrentStep('payment');
            return;
        }
        // payment: submit
        await handleSubmit();
    };

    const handleBack = () => {
        if (currentStep === 'payment') setCurrentStep('shipping');
        else if (currentStep === 'shipping') setCurrentStep('info');
        else router.push('/market/sepet');
    };

    const handleSubmit = async () => {
        if (hasCriticalIssues) { toast.error('Sepetinizdeki sorunları çözmeniz gerekiyor.'); return; }
        if (isBelowMinOrder) { toast.error(`Minimum sipariş tutarı ${formatPrice(MIN_ORDER_AMOUNT)}.`); return; }

        let cardPayload: ReturnType<CardFormRef['getPayload']> = null;
        if (paymentConfig?.enabled && selectedPaymentMethod === 'credit_card') {
            cardPayload = cardFormRef.current?.getPayload() ?? null;
            if (!cardPayload) { toast.error('Lütfen kart bilgilerinizi kontrol edin.'); return; }
        }

        setIsLoading(true);
        try {
            const firstSellerId = activeItemsBySeller[0]?.seller?.id;
            const primaryShipping = firstSellerId ? sellerShipping[firstSellerId]?.selected : '';
            const data: CreateOrderData = {
                shipping_address: address,
                notes: notes || undefined,
                shipping_provider: primaryShipping,
                shipping_cost: 0,
                payment_method: selectedPaymentMethod,
            };
            const res = await ordersApi.create(data);
            if (res.data) {
                const order = res.data.order;
                if (paymentConfig?.enabled && selectedPaymentMethod === 'credit_card' && cardPayload) {
                    toast.success('Sipariş oluşturuldu, ödeme işleniyor...');
                    const payRes = await paymentsApi.process({ order_id: order.id, ...cardPayload });
                    if ((payRes.data?.status === '3d_redirect' || payRes.data?.status === 'redirect') && payRes.data.html) {
                        setThreeDSecureHtml(payRes.data.html);
                    } else if (payRes.data?.status === 'success') {
                        toast.success('Ödeme başarılı!');
                        router.push('/market/odeme/sonuc?status=success');
                    } else {
                        toast.error(payRes.data?.error || payRes.error || 'Ödeme başarısız.');
                        router.push(`/market/odeme/${order.id}`);
                    }
                } else {
                    toast.success('Siparişiniz oluşturuldu!');
                    router.push('/market/hesabim?tab=siparislerim&sub=satin-aldiklarim');
                }
            } else if (res.error) {
                toast.error(res.error);
            }
        } catch {
            toast.error('Sipariş oluşturulurken hata.');
        } finally {
            setIsLoading(false);
        }
    };

    // Auth loading
    if (authLoading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-white">
                <Loader2 className="w-6 h-6 animate-spin text-slate-400" />
            </div>
        );
    }

    if (itemCount === 0) {
        return (
            <div className="min-h-screen bg-white flex items-center justify-center px-4">
                <div className="text-center max-w-md">
                    <Package className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                    <h2 className="text-2xl font-bold text-slate-900 mb-2">Sepetiniz boş</h2>
                    <p className="text-slate-500 mb-6">Sipariş vermek için önce sepetinize ürün ekleyin.</p>
                    <Link href="/market" className="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-full px-6 py-3 transition-colors">
                        Alışverişe Devam Et
                    </Link>
                </div>
            </div>
        );
    }

    const currentStepIdx = STEPS.findIndex(s => s.key === currentStep);

    return (
        <div className="min-h-screen bg-white">
            <div className="max-w-[1200px] mx-auto grid lg:grid-cols-[minmax(0,1fr)_420px]">
                {/* LEFT: Scrollable steps */}
                <div className="px-5 md:px-10 py-8 md:py-12 lg:min-h-screen lg:max-h-screen lg:overflow-y-auto lg:no-scrollbar">
                    {/* Brand */}
                    <Link href="/market" className="inline-block mb-8">
                        <div className="flex items-center gap-2.5">
                            <div className="w-10 h-10 bg-[#1e3a8a] rounded-xl flex items-center justify-center">
                                <span className="text-white font-black text-lg leading-none">i</span>
                            </div>
                            <div className="flex flex-col -space-y-0.5">
                                <span className="font-black text-2xl text-slate-900 tracking-tighter leading-none">i-kirtasiye</span>
                                <span className="text-[8px] font-bold text-[#ea580c] tracking-[2px] uppercase">B2B Kırtasiye Pazaryeri</span>
                            </div>
                        </div>
                    </Link>

                    {/* Breadcrumb */}
                    <nav className="flex items-center gap-1.5 text-sm mb-8 flex-wrap">
                        <Link href="/market/sepet" className="text-slate-400 hover:text-slate-600 transition-colors">Sepet</Link>
                        <ChevronRight className="w-3.5 h-3.5 text-slate-300" />
                        {STEPS.map((step, i) => {
                            const isActive = step.key === currentStep;
                            const isPast = i < currentStepIdx;
                            return (
                                <div key={step.key} className="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        onClick={() => isPast && setCurrentStep(step.key)}
                                        disabled={!isPast}
                                        className={`transition-colors ${isActive ? 'text-slate-900 font-bold' : isPast ? 'text-slate-400 hover:text-slate-600 cursor-pointer' : 'text-slate-300 cursor-default'}`}
                                    >
                                        {step.label}
                                    </button>
                                    {i < STEPS.length - 1 && <ChevronRight className="w-3.5 h-3.5 text-slate-300" />}
                                </div>
                            );
                        })}
                    </nav>

                    {/* Step content */}
                    {currentStep === 'info' && (
                        <InfoStep
                            user={user}
                            profileAddress={profileAddress}
                            savedAddresses={savedAddresses}
                            selectedAddressId={selectedAddressId}
                            onSelect={handleAddressSelect}
                            loadingAddresses={loadingAddresses}
                            newAddress={newAddress}
                            setNewAddress={setNewAddress}
                            saveNewAddressFlag={saveNewAddressFlag}
                            setSaveNewAddressFlag={setSaveNewAddressFlag}
                            notes={notes}
                            setNotes={setNotes}
                        />
                    )}

                    {currentStep === 'shipping' && (
                        <ShippingStep
                            address={address}
                            activeItemsBySeller={activeItemsBySeller}
                            sellerShipping={sellerShipping}
                            onChange={handleShippingChange}
                        />
                    )}

                    {currentStep === 'payment' && (
                        <PaymentStep
                            address={address}
                            loadingPayment={loadingPayment}
                            paymentConfig={paymentConfig}
                            cardFormRef={cardFormRef}
                            grandTotal={grandTotal}
                        />
                    )}

                    {/* Action bar */}
                    <div className="flex items-center justify-between mt-10 pt-6 border-t border-slate-100">
                        <button
                            type="button"
                            onClick={handleBack}
                            className="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            <ChevronLeft className="w-4 h-4" />
                            {currentStep === 'info' ? 'Sepete geri dön' : currentStep === 'shipping' ? 'Bilgiye geri dön' : 'Kargoya geri dön'}
                        </button>
                        <button
                            type="button"
                            onClick={handleNext}
                            disabled={isLoading || savingNewAddress || hasCriticalIssues || isBelowMinOrder || (currentStep === 'shipping' && (anyShippingLoading || !allShippingSelected))}
                            className="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl px-8 h-12 min-w-[160px] transition-colors"
                        >
                            {isLoading || savingNewAddress ? (
                                <Loader2 className="w-4 h-4 animate-spin" />
                            ) : currentStep === 'payment' ? (
                                <>Ödemeyi Tamamla</>
                            ) : (
                                <>Devam Et <ChevronRight className="w-4 h-4" /></>
                            )}
                        </button>
                    </div>
                </div>

                {/* RIGHT: Sticky summary — Shopify style */}
                <aside className="bg-[#f6f6f7] border-l border-[#e3e3e3] lg:sticky lg:top-0 lg:self-start lg:h-screen lg:overflow-y-auto lg:no-scrollbar shadow-[inset_1px_0_0_rgba(0,0,0,0.04)]">
                    <div className="px-5 md:px-8 py-8 md:py-10 space-y-5">
                        {/* Products */}
                        <div className="space-y-4">
                            {activeItemsBySeller.map(group => (
                                <div key={group.seller?.id || 'x'} className="space-y-4">
                                    {group.items.map(item => {
                                        const img = item.product.image_url || item.product.image;
                                        return (
                                            <div key={item.id} className="flex items-start gap-3.5">
                                                <div className="relative flex-shrink-0">
                                                    <div className="relative w-[64px] h-[64px] rounded-lg bg-white border border-[#e3e3e3] overflow-hidden shadow-sm">
                                                        {img ? (
                                                            <Image src={img} alt={item.product.name} fill sizes="64px" className="object-contain p-1.5" />
                                                        ) : (
                                                            <div className="w-full h-full flex items-center justify-center"><Package className="w-5 h-5 text-slate-300" /></div>
                                                        )}
                                                    </div>
                                                    <span className="absolute -top-[9px] -right-[9px] min-w-[22px] h-[22px] px-1.5 bg-[#5c5f62] text-white rounded-full flex items-center justify-center text-[11px] font-semibold border-2 border-[#f6f6f7]">
                                                        {item.quantity}
                                                    </span>
                                                </div>
                                                <div className="flex-1 min-w-0 pt-0.5">
                                                    <p className="text-[13px] font-semibold text-[#202223] line-clamp-2 leading-snug">{item.product.name}</p>
                                                    {item.product.brand && (
                                                        <p className="text-[12px] text-[#6d7175] mt-1">{item.product.brand}</p>
                                                    )}
                                                </div>
                                                <div className="text-[13px] font-semibold text-[#202223] whitespace-nowrap pt-0.5">
                                                    {formatPrice(item.price_at_addition * item.quantity)}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ))}
                        </div>

                        {/* Divider */}
                        <div className="h-px bg-[#e3e3e3]" />

                        {/* Coupon */}
                        <div>
                            {appliedCoupon ? (
                                <div className="flex items-center justify-between p-3 bg-white rounded-lg border border-[#e3e3e3]">
                                    <div className="flex items-center gap-2">
                                        <Tag className="w-4 h-4 text-[#ea580c]" />
                                        <div>
                                            <p className="text-[13px] font-bold text-[#202223]">{appliedCoupon.code}</p>
                                            <p className="text-[11px] text-[#ea580c]">{appliedCoupon.formatted_discount} indirim</p>
                                        </div>
                                    </div>
                                    <button onClick={handleRemoveCoupon} className="p-1 text-[#6d7175] hover:text-[#202223]"><X className="w-4 h-4" /></button>
                                </div>
                            ) : (
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        value={couponCode}
                                        onChange={e => { setCouponCode(e.target.value); setCouponError(null); }}
                                        placeholder="İndirim kodu veya hediye kartı"
                                        className="flex-1 h-10 px-3 rounded-lg border border-[#c9cccf] bg-white text-[13px] text-[#202223] placeholder:text-[#8c9196] focus:outline-none focus:border-[#202223] focus:ring-1 focus:ring-[#202223] transition-colors"
                                    />
                                    <button
                                        onClick={handleApplyCoupon}
                                        disabled={couponLoading || !couponCode.trim()}
                                        className="h-10 px-4 rounded-lg border border-[#c9cccf] bg-white hover:bg-[#f6f6f7] disabled:opacity-50 text-[13px] font-semibold text-[#202223] transition-colors"
                                    >
                                        {couponLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Uygula'}
                                    </button>
                                </div>
                            )}
                            {couponError && <p className="text-[11px] text-red-600 mt-2">{couponError}</p>}
                        </div>

                        {/* Divider */}
                        <div className="h-px bg-[#e3e3e3]" />

                        {/* Totals */}
                        <div className="space-y-2 text-[13px]">
                            <div className="flex justify-between items-center">
                                <span className="text-[#202223]">Ara toplam</span>
                                <span className="text-[#202223] font-medium">{formatPrice(activeTotal)}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-[#202223]">Kargo</span>
                                <span className="text-[#202223] font-medium">
                                    {totalShippingCost === 0 ? 'Ücretsiz' : formatPrice(totalShippingCost)}
                                </span>
                            </div>
                            {couponDiscount > 0 && (
                                <div className="flex justify-between items-center">
                                    <span className="text-[#202223]">İndirim</span>
                                    <span className="text-[#ea580c] font-semibold">-{formatPrice(couponDiscount)}</span>
                                </div>
                            )}
                        </div>

                        {/* Grand Total */}
                        <div className="pt-4 border-t border-[#e3e3e3]">
                            <div className="flex justify-between items-baseline gap-3">
                                <span className="text-[15px] font-semibold text-[#202223]">Toplam</span>
                                <div className="text-right">
                                    <span className="text-[11px] text-[#6d7175] mr-1.5">TRY</span>
                                    <span className="text-[24px] font-bold text-[#202223] tracking-tight">{formatPrice(grandTotal)}</span>
                                </div>
                            </div>
                            <p className="text-[11px] text-[#6d7175] mt-1 text-right">KDV dahil</p>
                        </div>

                        {isBelowMinOrder && (
                            <div className="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <Shield className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                                <p className="text-[12px] text-amber-800 leading-relaxed">
                                    Minimum sipariş tutarı <strong>{formatPrice(MIN_ORDER_AMOUNT)}</strong>. {formatPrice(remainingForMinOrder)} daha eklemeniz gerekiyor.
                                </p>
                            </div>
                        )}
                    </div>
                </aside>
            </div>

            {/* 3D Secure */}
            {threeDSecureHtml && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div className="px-4 py-3 bg-slate-50 border-b flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Shield className="h-5 w-5 text-[#ea580c]" />
                                <span className="font-semibold text-slate-900">3D Secure Doğrulama</span>
                            </div>
                            <button onClick={() => setThreeDSecureHtml(null)} className="text-slate-400 hover:text-slate-600">
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <iframe
                            srcDoc={threeDSecureHtml}
                            className="w-full h-[500px] border-0"
                            title="3D Secure"
                            sandbox="allow-forms allow-scripts allow-same-origin allow-top-navigation"
                        />
                    </div>
                </div>
            )}
        </div>
    );
}

/* ═══════════════════ STEP COMPONENTS ═══════════════════ */

function InfoStep(props: {
    user: ReturnType<typeof useAuth>['user'];
    profileAddress: ShippingAddress;
    savedAddresses: Address[];
    selectedAddressId: number | 'profile' | 'new' | null;
    onSelect: (id: number | 'profile' | 'new') => void;
    loadingAddresses: boolean;
    newAddress: { title: string; name: string; phone: string; address: string; city: string; district: string; city_id?: number; district_id?: number };
    setNewAddress: (a: { title: string; name: string; phone: string; address: string; city: string; district: string; city_id?: number; district_id?: number }) => void;
    saveNewAddressFlag: boolean;
    setSaveNewAddressFlag: (v: boolean) => void;
    notes: string;
    setNotes: (v: string) => void;
}) {
    const { user, profileAddress, savedAddresses, selectedAddressId, onSelect, loadingAddresses, newAddress, setNewAddress, saveNewAddressFlag, setSaveNewAddressFlag, notes, setNotes } = props;

    return (
        <>
            {/* Contact */}
            <section className="mb-8">
                <div className="flex items-baseline justify-between mb-4">
                    <h2 className="text-xl font-bold text-slate-900">İletişim</h2>
                </div>
                <div className="rounded-xl border border-slate-900 p-4 bg-white">
                    <label className="text-[11px] font-medium text-slate-500 uppercase tracking-wide">E-posta</label>
                    <p className="text-[15px] text-slate-900 mt-0.5">{user?.email || '—'}</p>
                </div>
            </section>

            {/* Shipping Address */}
            <section className="mb-8">
                <h2 className="text-xl font-bold text-slate-900 mb-4">Teslimat Adresi</h2>

                {loadingAddresses ? (
                    <div className="flex items-center justify-center py-8"><Loader2 className="w-5 h-5 animate-spin text-slate-400" /></div>
                ) : (
                    <div className="space-y-3">
                        {/* Profile */}
                        {profileAddress.address && (
                            <AddressOption
                                selected={selectedAddressId === 'profile'}
                                onClick={() => onSelect('profile')}
                                title={profileAddress.name || 'Kayıt Adresi'}
                                subtitle={`${profileAddress.district ? profileAddress.district + ', ' : ''}${profileAddress.city}`}
                                body={profileAddress.address}
                                badge="Kayıt Adresi"
                            />
                        )}

                        {/* Saved */}
                        {savedAddresses.map(addr => (
                            <AddressOption
                                key={addr.id}
                                selected={selectedAddressId === addr.id}
                                onClick={() => onSelect(addr.id)}
                                title={addr.title}
                                subtitle={`${addr.name} · ${addr.district ? addr.district + ', ' : ''}${addr.city}`}
                                body={addr.address}
                                badge={addr.is_default ? 'Varsayılan' : undefined}
                            />
                        ))}

                        {/* New */}
                        <button
                            type="button"
                            onClick={() => onSelect('new')}
                            className={`w-full flex items-center gap-3 p-4 rounded-xl border-2 border-dashed transition-all ${selectedAddressId === 'new' ? 'border-slate-900 bg-slate-50' : 'border-slate-200 hover:border-slate-400'}`}
                        >
                            <Plus className="w-5 h-5 text-slate-500" />
                            <span className="text-sm font-semibold text-slate-900">Yeni adres ekle</span>
                        </button>

                        {selectedAddressId === 'new' && (
                            <div className="mt-4 p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                                <FormRow>
                                    <TextField label="Adres Başlığı" value={newAddress.title} onChange={v => setNewAddress({ ...newAddress, title: v })} placeholder="Mağaza, Depo, Şube..." />
                                </FormRow>
                                <div className="grid grid-cols-2 gap-3">
                                    <TextField label="Ad / Firma *" value={newAddress.name} onChange={v => setNewAddress({ ...newAddress, name: v })} />
                                    <TextField label="Telefon *" value={newAddress.phone} onChange={v => setNewAddress({ ...newAddress, phone: v })} />
                                </div>
                                <TextField label="Adres *" value={newAddress.address} onChange={v => setNewAddress({ ...newAddress, address: v })} />
                                <CityDistrictSelect
                                    required
                                    value={{
                                        cityId: newAddress.city_id,
                                        cityName: newAddress.city,
                                        districtId: newAddress.district_id,
                                        districtName: newAddress.district,
                                    }}
                                    onChange={(v) => setNewAddress({
                                        ...newAddress,
                                        city: v.cityName ?? '',
                                        district: v.districtName ?? '',
                                        city_id: v.cityId,
                                        district_id: v.districtId,
                                    })}
                                />
                                <label className="flex items-center gap-2 pt-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={saveNewAddressFlag}
                                        onChange={e => setSaveNewAddressFlag(e.target.checked)}
                                        className="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    <span className="text-sm text-slate-600">Bir sonraki işlem için bu bilgileri kaydet</span>
                                </label>
                            </div>
                        )}
                    </div>
                )}
            </section>

            {/* Notes */}
            <section className="mb-8">
                <h2 className="text-xl font-bold text-slate-900 mb-4">Sipariş Notu</h2>
                <textarea
                    value={notes}
                    onChange={e => setNotes(e.target.value)}
                    placeholder="İsteğe bağlı"
                    rows={3}
                    className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:border-slate-900 transition-colors resize-none"
                />
            </section>
        </>
    );
}

function ShippingStep(props: {
    address: ShippingAddress;
    activeItemsBySeller: ReturnType<typeof useCartStore.getState>['itemsBySeller'];
    sellerShipping: Record<number, { options: ShippingOption[]; selected: string; cost: number; loading: boolean; desi: number; subtotal: number }>;
    onChange: (sellerId: number, provider: string) => void;
}) {
    const { address, activeItemsBySeller, sellerShipping, onChange } = props;

    return (
        <>
            {/* Address recap */}
            <section className="mb-8">
                <h2 className="text-xl font-bold text-slate-900 mb-4">Teslim Adresi</h2>
                <div className="rounded-xl border border-slate-200 p-4 bg-slate-50">
                    <p className="text-sm font-semibold text-slate-900">{address.name}</p>
                    <p className="text-sm text-slate-600 mt-1">{address.address}</p>
                    <p className="text-sm text-slate-600">{address.district ? `${address.district}, ` : ''}{address.city}</p>
                    <p className="text-sm text-slate-500 mt-1">{address.phone}</p>
                </div>
            </section>

            {/* Shipping per seller */}
            <section className="mb-8">
                <h2 className="text-xl font-bold text-slate-900 mb-4">Kargo Yöntemi</h2>
                <div className="space-y-5">
                    {activeItemsBySeller.map(group => {
                        const sellerId = group.seller?.id;
                        if (!sellerId) return null;
                        const data = sellerShipping[sellerId];
                        const sellerName = group.seller?.nickname || group.seller?.business_name || 'Satıcı';
                        return (
                            <div key={sellerId} className="rounded-xl border border-slate-200 p-4 bg-white">
                                <div className="flex items-center justify-between mb-3">
                                    <p className="text-sm font-bold text-slate-900">{sellerName}</p>
                                    <span className="text-[11px] font-semibold px-2 py-0.5 bg-[#ffedd5] text-[#ea580c] rounded-full">Ücretsiz Kargo</span>
                                </div>
                                {!data || data.loading ? (
                                    <div className="flex items-center gap-2 py-3 text-slate-400 text-sm">
                                        <Loader2 className="w-4 h-4 animate-spin" /> Yükleniyor...
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {data.options.map(opt => (
                                            <button
                                                key={opt.provider}
                                                type="button"
                                                onClick={() => onChange(sellerId, opt.provider)}
                                                className={`w-full flex items-center justify-between p-3 rounded-lg border-2 transition-all ${data.selected === opt.provider ? 'border-slate-900 bg-slate-50' : 'border-slate-200 hover:border-slate-400'}`}
                                            >
                                                <div className="flex items-center gap-2.5">
                                                    <span className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${data.selected === opt.provider ? 'border-slate-900' : 'border-slate-300'}`}>
                                                        {data.selected === opt.provider && <span className="w-2 h-2 rounded-full bg-slate-900" />}
                                                    </span>
                                                    <span className="text-sm font-medium text-slate-900">{opt.name}</span>
                                                </div>
                                                <span className="text-sm text-[#ea580c] font-semibold">Ücretsiz</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </section>
        </>
    );
}

function PaymentStep(props: {
    address: ShippingAddress;
    loadingPayment: boolean;
    paymentConfig: PaymentConfig | null;
    cardFormRef: React.RefObject<CardFormRef | null>;
    grandTotal: number;
}) {
    const { address, loadingPayment, paymentConfig, cardFormRef, grandTotal } = props;

    return (
        <>
            {/* Recap */}
            <section className="mb-8 space-y-3">
                <div className="rounded-xl border border-slate-200 p-4 bg-white">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Teslim Adresi</p>
                            <p className="text-sm text-slate-900 mt-1">
                                {address.name} · {address.district ? `${address.district}, ` : ''}{address.city}
                            </p>
                        </div>
                    </div>
                </div>
                <div className="rounded-xl border border-slate-200 p-4 bg-white">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Kargo</p>
                            <p className="text-sm text-slate-900 mt-1">Ücretsiz — Satıcı karşılar</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Payment */}
            <section className="mb-4">
                <h2 className="text-xl font-bold text-slate-900 mb-4">Ödeme</h2>
                <p className="text-sm text-slate-500 mb-5">Tüm işlemler güvenli ve şifrelidir.</p>

                {loadingPayment ? (
                    <div className="flex items-center justify-center py-10"><Loader2 className="w-5 h-5 animate-spin text-slate-400" /></div>
                ) : !paymentConfig?.enabled ? (
                    <div className="p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
                        Kredi kartı ödemesi şu anda aktif değil. Sipariş verdikten sonra havale/EFT ile ödeyebilirsiniz.
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-900 bg-white overflow-hidden">
                        <div className="px-5 py-4 bg-slate-50 border-b border-slate-200">
                            <div className="flex items-center gap-3">
                                <span className="w-4 h-4 rounded-full border-2 border-slate-900 flex items-center justify-center">
                                    <span className="w-2 h-2 rounded-full bg-slate-900" />
                                </span>
                                <CreditCard className="w-5 h-5 text-slate-700" />
                                <span className="text-sm font-semibold text-slate-900">Kredi Kartı / Banka Kartı</span>
                            </div>
                        </div>
                        <div className="p-5">
                            <EmbeddedCardForm ref={cardFormRef} totalAmount={grandTotal} embedded />
                        </div>
                    </div>
                )}

                {paymentConfig?.test_mode && (
                    <div className="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800">
                        <strong>Test Modu:</strong> Gerçek ödeme alınmayacak. Test kartı: 4355 0843 5508 4358 · 12/30 · 000
                    </div>
                )}
            </section>
        </>
    );
}

/* ═══════════════════ SMALL PRIMITIVES ═══════════════════ */

function AddressOption({ selected, onClick, title, subtitle, body, badge }: {
    selected: boolean;
    onClick: () => void;
    title: string;
    subtitle?: string;
    body?: string;
    badge?: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`w-full text-left p-4 rounded-xl border-2 transition-all ${selected ? 'border-slate-900 bg-slate-50' : 'border-slate-200 hover:border-slate-400 bg-white'}`}
        >
            <div className="flex items-start gap-3">
                <span className={`w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5 ${selected ? 'border-slate-900' : 'border-slate-300'}`}>
                    {selected && <span className="w-2.5 h-2.5 rounded-full bg-slate-900" />}
                </span>
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="text-sm font-bold text-slate-900">{title}</span>
                        {badge && <span className="text-[10px] font-semibold px-2 py-0.5 bg-[#ffedd5] text-[#ea580c] rounded-full uppercase tracking-wide">{badge}</span>}
                    </div>
                    {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
                    {body && <p className="text-sm text-slate-600 mt-1 line-clamp-2">{body}</p>}
                </div>
            </div>
        </button>
    );
}

function FormRow({ children }: { children: React.ReactNode }) { return <>{children}</>; }

function TextField({ label, value, onChange, placeholder }: { label: string; value: string; onChange: (v: string) => void; placeholder?: string }) {
    return (
        <label className="block relative">
            <span className="absolute left-4 top-2 text-[11px] font-medium text-slate-500">{label}</span>
            <input
                type="text"
                value={value}
                onChange={e => onChange(e.target.value)}
                placeholder={placeholder}
                className="w-full h-14 pt-6 pb-2 px-4 rounded-xl border border-slate-200 bg-white text-[15px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 transition-colors"
            />
        </label>
    );
}
