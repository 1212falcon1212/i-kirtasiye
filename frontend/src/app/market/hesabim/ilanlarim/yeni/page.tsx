'use client';

import { useState, useRef, useCallback, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { offersApi, productsApi, Product, CreateOfferData } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { BarcodeScanner } from '@/components/mobile/BarcodeScanner';
import { toast } from 'sonner';
import {
    ArrowLeft,
    Search,
    Package,
    Loader2,
    CheckCircle2,
    Tag,
    Calendar,
    Hash,
    FileText,
    TrendingDown,
    TrendingUp,
    ChevronLeft,
    ChevronRight,
    X,
    Sparkles,
    AlertCircle,
    Info,
    BarChart3,
    ShoppingCart,
    Boxes,
    ScanBarcode,
    ScanLine,
} from 'lucide-react';
import { cn } from '@/lib/utils';

type SearchMode = 'name' | 'barcode';

// Month/Year picker constants
const MONTHS = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

function MonthYearPicker({
    value,
    onChange,
    onClear,
}: {
    value: string;
    onChange: (val: string) => void;
    onClear: () => void;
}) {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    const [pickerYear, setPickerYear] = useState(() => {
        if (value) return new Date(value).getFullYear();
        return currentYear;
    });
    const [isOpen, setIsOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const selectedMonth = value ? new Date(value).getMonth() : null;
    const selectedYear = value ? new Date(value).getFullYear() : null;

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) setIsOpen(false);
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const isMonthDisabled = (monthIdx: number) => {
        if (pickerYear < currentYear) return true;
        if (pickerYear === currentYear && monthIdx <= currentMonth) return true;
        return false;
    };

    const handleSelect = (monthIdx: number) => {
        if (isMonthDisabled(monthIdx)) return;
        onChange(`${pickerYear}-${String(monthIdx + 1).padStart(2, '0')}-01`);
        setIsOpen(false);
    };

    const displayValue = value ? `${MONTHS[selectedMonth!]} ${selectedYear}` : '';

    return (
        <div className="relative" ref={ref}>
            <div
                role="button"
                tabIndex={0}
                onClick={() => setIsOpen(!isOpen)}
                onKeyDown={(e) => e.key === 'Enter' && setIsOpen(!isOpen)}
                className={cn(
                    'flex items-center w-full h-11 rounded-xl border px-4 text-sm transition-all cursor-pointer select-none',
                    isOpen
                        ? 'border-[#b8651a] ring-2 ring-[#b8651a]/20 bg-white'
                        : 'border-[#e5e1de] bg-white hover:border-[#b8651a]',
                    !displayValue && 'text-[#9ca3af]'
                )}
            >
                <Calendar className="w-4 h-4 text-[#b8651a] mr-2.5 flex-shrink-0" />
                <span className={displayValue ? 'text-[#1a1a1a]' : 'text-[#9ca3af]'}>
                    {displayValue || 'Ay / Yıl seçin'}
                </span>
                {value && (
                    <button
                        type="button"
                        onClick={(e) => { e.stopPropagation(); onClear(); }}
                        className="ml-auto text-[#9ca3af] hover:text-[#1a1a1a] transition-colors"
                    >
                        <X className="w-3.5 h-3.5" />
                    </button>
                )}
            </div>

            {isOpen && (
                <div className="absolute z-50 top-full mt-2 left-0 w-[260px] bg-white border border-[#e5e1de] rounded-2xl p-4 shadow-xl shadow-black/5 animate-in fade-in-0 zoom-in-95 duration-150">
                    <div className="flex items-center justify-between mb-3">
                        <button
                            type="button"
                            onClick={() => setPickerYear(y => y - 1)}
                            disabled={pickerYear <= currentYear}
                            className="p-1.5 rounded-lg hover:bg-[#faf8f6] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronLeft className="w-4 h-4 text-[#1a1a1a]" />
                        </button>
                        <span className="text-sm font-bold text-[#1a1a1a]">{pickerYear}</span>
                        <button
                            type="button"
                            onClick={() => setPickerYear(y => y + 1)}
                            disabled={pickerYear >= currentYear + 10}
                            className="p-1.5 rounded-lg hover:bg-[#faf8f6] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronRight className="w-4 h-4 text-[#1a1a1a]" />
                        </button>
                    </div>
                    <div className="grid grid-cols-4 gap-1.5">
                        {MONTHS.map((m, idx) => {
                            const disabled = isMonthDisabled(idx);
                            const selected = selectedMonth === idx && selectedYear === pickerYear;
                            return (
                                <button
                                    key={idx}
                                    type="button"
                                    disabled={disabled}
                                    onClick={() => handleSelect(idx)}
                                    className={cn(
                                        'text-xs py-2 rounded-lg font-medium transition-all',
                                        disabled
                                            ? 'text-[#d1ccc9] cursor-not-allowed'
                                            : selected
                                                ? 'bg-[#b8651a] text-white shadow-sm'
                                                : 'text-[#6b7280] hover:bg-[#faf8f6] hover:text-[#1a1a1a]'
                                    )}
                                >
                                    {m}
                                </button>
                            );
                        })}
                    </div>
                    <p className="text-[10px] text-[#9ca3af] mt-3 text-center">Miadı olmayan ürün için boş bırakın</p>
                </div>
            )}
        </div>
    );
}

// Price comparison badge
function PriceComparison({ entered, lowest }: { entered: number; lowest: number }) {
    const diff = entered - lowest;
    const pct = Math.abs(((diff) / lowest) * 100).toFixed(0);

    if (entered < lowest) {
        return (
            <div className="flex items-center gap-1.5 text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-lg px-2.5 py-1.5">
                <TrendingDown className="w-3.5 h-3.5" />
                <span className="text-xs font-semibold">En ucuz ilan sizinki olacak! (%{pct} daha ucuz)</span>
            </div>
        );
    }
    if (entered === lowest) {
        return (
            <div className="flex items-center gap-1.5 text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-1.5">
                <Info className="w-3.5 h-3.5" />
                <span className="text-xs font-semibold">En düşük fiyatla eşit</span>
            </div>
        );
    }
    return (
        <div className="flex items-center gap-1.5 text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1.5">
            <TrendingUp className="w-3.5 h-3.5" />
            <span className="text-xs font-medium">En ucuz ilandan %{pct} daha pahalı</span>
        </div>
    );
}

export default function YeniIlanPage() {
    const router = useRouter();

    // Product search
    const [searchMode, setSearchMode] = useState<SearchMode>('name');
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<Product[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [showScanner, setShowScanner] = useState(false);
    const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Form state
    const [price, setPrice] = useState('');
    const [stock, setStock] = useState('');
    const [expiryDate, setExpiryDate] = useState('');
    const [batchNumber, setBatchNumber] = useState('');
    const [notes, setNotes] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const formatPrice = (val: number) =>
        new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(val);

    const handleSearch = useCallback((q: string, mode: SearchMode = searchMode) => {
        setSearchQuery(q);
        if (searchTimeout.current) clearTimeout(searchTimeout.current);

        // Barcode mode: need at least 8 digits for exact match
        const minLength = mode === 'barcode' ? 8 : 2;
        if (q.length < minLength) { setSearchResults([]); setIsSearching(false); return; }

        // Barcode mode: skip non-numeric
        if (mode === 'barcode' && !/^\d+$/.test(q)) { setSearchResults([]); setIsSearching(false); return; }

        setIsSearching(true);
        searchTimeout.current = setTimeout(async () => {
            try {
                const res = await productsApi.search(q);
                if (res.data) {
                    setSearchResults(res.data.products);
                    // Barcode mode + exact single match → auto-select
                    if (mode === 'barcode' && res.data.products.length === 1) {
                        const match = res.data.products[0];
                        setSelectedProduct(match);
                        setSearchQuery('');
                        setSearchResults([]);
                        toast.success('Barkod eşleşti', { description: match.name });
                    }
                }
            } catch {
                /* silent */
            } finally {
                setIsSearching(false);
            }
        }, 300);
    }, [searchMode]);

    const handleSelectProduct = (product: Product) => {
        setSelectedProduct(product);
        setSearchQuery('');
        setSearchResults([]);
    };

    const handleSwitchMode = (mode: SearchMode) => {
        setSearchMode(mode);
        setSearchQuery('');
        setSearchResults([]);
    };

    const handleScanResult = (code: string) => {
        setShowScanner(false);
        setSearchMode('barcode');
        handleSearch(code, 'barcode');
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedProduct) { toast.error('Lütfen bir ürün seçin'); return; }
        if (!price || parseFloat(price) <= 0) { toast.error('Geçerli bir fiyat girin'); return; }
        if (!stock || parseInt(stock) < 1) { toast.error('Geçerli bir stok miktarı girin'); return; }

        setIsSubmitting(true);
        try {
            const data: CreateOfferData = {
                product_id: selectedProduct.id,
                price: parseFloat(price),
                stock: parseInt(stock),
                expiry_date: expiryDate || undefined,
                batch_number: batchNumber || undefined,
                notes: notes || undefined,
            };
            const res = await offersApi.create(data);
            if (res.data) {
                toast.success('İlan başarıyla oluşturuldu!', {
                    description: `${selectedProduct.name} için ilanınız yayınlandı.`,
                });
                router.push('/market/hesabim?tab=ilanlarim&sub=aktif-ilanlar');
            } else if (res.error) {
                toast.error(res.error);
            }
        } catch {
            toast.error('İlan oluşturulurken bir hata oluştu');
        } finally {
            setIsSubmitting(false);
        }
    };

    const isFormValid = !!selectedProduct && !!price && parseFloat(price) > 0 && !!stock && parseInt(stock) >= 1;

    const enteredPrice = parseFloat(price);

    return (
        <div className="min-h-screen bg-[#f7f4f2]">
            {/* Top Bar */}
            <div className="sticky top-0 z-40 bg-white border-b border-[#e5e1de] shadow-sm">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/market/hesabim?tab=ilanlarim"
                            className="flex items-center justify-center w-9 h-9 rounded-xl border border-[#e5e1de] hover:border-[#b8651a] hover:text-[#b8651a] text-[#6b7280] transition-all"
                        >
                            <ArrowLeft className="w-4 h-4" />
                        </Link>
                        <div>
                            <h1 className="text-base font-black text-[#1a1a1a] leading-none">Yeni İlan Oluştur</h1>
                            <p className="text-xs text-[#9ca3af] mt-0.5">İlanlarım &gt; Yeni İlan</p>
                        </div>
                    </div>
                    <Button
                        onClick={handleSubmit}
                        disabled={!isFormValid || isSubmitting}
                        className="bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl font-bold px-5 h-10 disabled:opacity-50"
                    >
                        {isSubmitting ? (
                            <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Yayınlanıyor...</>
                        ) : (
                            <><Sparkles className="w-4 h-4 mr-2" />İlanı Yayınla</>
                        )}
                    </Button>
                </div>
            </div>

            {/* Main Content */}
            <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">

                        {/* LEFT: Product Selection */}
                        <div className="lg:col-span-3 space-y-5">
                            {/* Step 1: Product */}
                            <div className="bg-white rounded-2xl border border-[#e5e1de] overflow-hidden">
                                <div className="px-5 py-4 border-b border-[#f0eceb] flex items-center gap-2.5">
                                    <div className="w-7 h-7 rounded-lg bg-[#fbeede] flex items-center justify-center">
                                        <Package className="w-3.5 h-3.5 text-[#b8651a]" />
                                    </div>
                                    <div>
                                        <h2 className="text-sm font-bold text-[#1a1a1a]">Ürün Seçimi</h2>
                                        <p className="text-[11px] text-[#9ca3af]">İlan oluşturmak istediğiniz ürünü arayın</p>
                                    </div>
                                    {selectedProduct && (
                                        <div className="ml-auto">
                                            <span className="flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full font-semibold border border-emerald-100">
                                                <CheckCircle2 className="w-3 h-3" />
                                                Seçildi
                                            </span>
                                        </div>
                                    )}
                                </div>

                                <div className="p-5">
                                    {!selectedProduct ? (
                                        <div className="space-y-3">
                                            {/* Mode Toggle */}
                                            <div className="inline-flex rounded-xl bg-[#f7f4f2] p-1 border border-[#e5e1de]">
                                                <button
                                                    type="button"
                                                    onClick={() => handleSwitchMode('name')}
                                                    className={cn(
                                                        "flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold transition-all",
                                                        searchMode === 'name'
                                                            ? 'bg-white text-[#b8651a] shadow-sm'
                                                            : 'text-[#6b7280] hover:text-[#1a1a1a]'
                                                    )}
                                                >
                                                    <Search className="w-3.5 h-3.5" />
                                                    Ürün Adı
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleSwitchMode('barcode')}
                                                    className={cn(
                                                        "flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold transition-all",
                                                        searchMode === 'barcode'
                                                            ? 'bg-white text-[#b8651a] shadow-sm'
                                                            : 'text-[#6b7280] hover:text-[#1a1a1a]'
                                                    )}
                                                >
                                                    <ScanBarcode className="w-3.5 h-3.5" />
                                                    Barkod
                                                </button>
                                            </div>

                                            {/* Search Input */}
                                            <div className="relative">
                                                {searchMode === 'barcode' ? (
                                                    <ScanBarcode className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9ca3af]" />
                                                ) : (
                                                    <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9ca3af]" />
                                                )}
                                                <input
                                                    type={searchMode === 'barcode' ? 'tel' : 'text'}
                                                    inputMode={searchMode === 'barcode' ? 'numeric' : 'text'}
                                                    pattern={searchMode === 'barcode' ? '[0-9]*' : undefined}
                                                    maxLength={searchMode === 'barcode' ? 13 : undefined}
                                                    placeholder={searchMode === 'barcode'
                                                        ? 'Barkod girin (8–13 haneli)...'
                                                        : 'Ürün adı veya marka ile arayın...'}
                                                    value={searchQuery}
                                                    onChange={(e) => {
                                                        const v = searchMode === 'barcode' ? e.target.value.replace(/\D/g, '') : e.target.value;
                                                        handleSearch(v);
                                                    }}
                                                    className={cn(
                                                        "w-full h-11 pl-10 rounded-xl border bg-[#faf8f6] text-sm placeholder:text-[#9ca3af] focus:outline-none focus:border-[#b8651a] focus:ring-2 focus:ring-[#b8651a]/20 focus:bg-white transition-all",
                                                        searchMode === 'barcode' ? 'border-[#b8651a]/30 font-mono tracking-wider pr-20' : 'border-[#e5e1de] pr-10'
                                                    )}
                                                    autoFocus
                                                    key={searchMode}
                                                />
                                                {searchMode === 'barcode' && (
                                                    <button
                                                        type="button"
                                                        aria-label="Kamera ile barkod tara"
                                                        onClick={() => setShowScanner(true)}
                                                        className="absolute top-1/2 -translate-y-1/2 right-10 h-8 w-8 rounded-lg bg-white border border-[#b8651a]/30 text-[#b8651a] hover:bg-[#fbeede] transition-colors flex items-center justify-center"
                                                    >
                                                        <ScanLine className="w-4 h-4" />
                                                    </button>
                                                )}
                                                {isSearching && (
                                                    <Loader2 className="w-4 h-4 absolute right-3.5 top-1/2 -translate-y-1/2 animate-spin text-[#b8651a]" />
                                                )}
                                            </div>

                                            {searchMode === 'barcode' && searchQuery.length > 0 && searchQuery.length < 8 && (
                                                <p className="text-[11px] text-[#9ca3af] flex items-center gap-1 ml-1">
                                                    <Info className="w-3 h-3" />
                                                    En az 8 haneli barkod gerekli ({searchQuery.length}/8)
                                                </p>
                                            )}

                                            {/* Results */}
                                            {searchResults.length > 0 && (
                                                <div className="border border-[#e5e1de] rounded-xl overflow-hidden divide-y divide-[#f0eceb]">
                                                    {searchResults.map((product) => (
                                                        <button
                                                            key={product.id}
                                                            type="button"
                                                            onClick={() => handleSelectProduct(product)}
                                                            className="w-full p-3.5 text-left hover:bg-[#faf8f6] flex items-center gap-3.5 transition-colors group"
                                                        >
                                                            <div className="w-11 h-11 rounded-xl bg-[#faf8f6] border border-[#f0eceb] overflow-hidden flex items-center justify-center flex-shrink-0 group-hover:border-[#b8651a]/30 transition-colors">
                                                                {(product.image_url || product.image) ? (
                                                                    <Image
                                                                        src={(product.image_url || product.image) as string}
                                                                        alt=""
                                                                        width={44}
                                                                        height={44}
                                                                        className="w-full h-full object-contain"
                                                                    />
                                                                ) : (
                                                                    <Package className="w-5 h-5 text-[#d1ccc9]" />
                                                                )}
                                                            </div>
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-semibold text-[#1a1a1a] truncate group-hover:text-[#b8651a] transition-colors">
                                                                    {product.name}
                                                                </p>
                                                                <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                                                                    {product.barcode && (
                                                                        <span className="text-[11px] text-[#9ca3af] font-mono bg-[#f7f4f2] px-1.5 py-0.5 rounded">
                                                                            {product.barcode}
                                                                        </span>
                                                                    )}
                                                                    {product.brand && (
                                                                        <span className="text-[11px] text-[#b8651a] font-bold uppercase">
                                                                            {product.brand}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                            <div className="flex-shrink-0 text-right">
                                                                {product.lowest_price != null && product.lowest_price > 0 ? (
                                                                    <div>
                                                                        <p className="text-sm font-bold text-[#b8651a]">
                                                                            {formatPrice(product.lowest_price)}
                                                                        </p>
                                                                        <p className="text-[10px] text-[#9ca3af]">
                                                                            {product.offers_count || 0} ilan
                                                                        </p>
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-[10px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-semibold border border-amber-100">
                                                                        İlan yok
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}

                                            {/* Empty states */}
                                            {searchQuery.length >= (searchMode === 'barcode' ? 8 : 2) && !isSearching && searchResults.length === 0 && (
                                                <div className="text-center py-10">
                                                    <div className="w-14 h-14 rounded-2xl bg-[#faf8f6] border border-[#f0eceb] flex items-center justify-center mx-auto mb-3">
                                                        <Package className="w-6 h-6 text-[#d1ccc9]" />
                                                    </div>
                                                    <p className="text-sm font-semibold text-[#6b7280]">
                                                        {searchMode === 'barcode' ? 'Bu barkoda ait ürün bulunamadı' : 'Ürün bulunamadı'}
                                                    </p>
                                                    <p className="text-xs text-[#9ca3af] mt-1">
                                                        {searchMode === 'barcode' ? 'Barkodu kontrol edin veya ad ile arayın' : 'Farklı bir arama terimi deneyin'}
                                                    </p>
                                                </div>
                                            )}

                                            {searchQuery.length < (searchMode === 'barcode' ? 1 : 2) && (
                                                <div className="text-center py-10">
                                                    <div className="w-14 h-14 rounded-2xl bg-[#fbeede] border border-[#b2e8f3] flex items-center justify-center mx-auto mb-3">
                                                        {searchMode === 'barcode' ? (
                                                            <ScanBarcode className="w-6 h-6 text-[#b8651a]" />
                                                        ) : (
                                                            <Search className="w-6 h-6 text-[#b8651a]" />
                                                        )}
                                                    </div>
                                                    <p className="text-sm font-semibold text-[#6b7280]">
                                                        {searchMode === 'barcode' ? 'Barkod ile ürün bul' : 'Ürün arayın'}
                                                    </p>
                                                    <p className="text-xs text-[#9ca3af] mt-1">
                                                        {searchMode === 'barcode'
                                                            ? '8–13 haneli barkod girin, eşleşen ürün otomatik seçilir'
                                                            : 'En az 2 karakter girerek ürün arayabilirsiniz'}
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        /* Selected Product Card */
                                        <div className="space-y-3">
                                            <div className="rounded-xl border-2 border-[#b8651a]/20 bg-gradient-to-br from-[#fbeede] to-white p-4 flex items-start gap-4">
                                                <div className="w-16 h-16 rounded-xl bg-white border border-[#e5e1de] overflow-hidden flex items-center justify-center flex-shrink-0 shadow-sm">
                                                    {(selectedProduct.image_url || selectedProduct.image) ? (
                                                        <Image
                                                            src={(selectedProduct.image_url || selectedProduct.image) as string}
                                                            alt=""
                                                            width={64}
                                                            height={64}
                                                            className="w-full h-full object-contain"
                                                        />
                                                    ) : (
                                                        <Package className="w-7 h-7 text-[#b8651a]" />
                                                    )}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-bold text-[#1a1a1a] leading-snug">
                                                        {selectedProduct.name}
                                                    </p>
                                                    <div className="flex items-center gap-2 mt-1 flex-wrap">
                                                        {selectedProduct.barcode && (
                                                            <span className="text-[11px] text-[#9ca3af] font-mono bg-white/80 px-1.5 py-0.5 rounded border border-[#e5e1de]">
                                                                {selectedProduct.barcode}
                                                            </span>
                                                        )}
                                                        {selectedProduct.brand && (
                                                            <span className="text-[11px] text-[#b8651a] font-bold uppercase">
                                                                {selectedProduct.brand}
                                                            </span>
                                                        )}
                                                    </div>
                                                    {selectedProduct.lowest_price != null && selectedProduct.lowest_price > 0 && (
                                                        <div className="flex items-center gap-1.5 mt-2">
                                                            <BarChart3 className="w-3.5 h-3.5 text-[#b8651a]" />
                                                            <p className="text-xs text-[#6b7280]">
                                                                En düşük fiyat:{' '}
                                                                <span className="font-bold text-[#b8651a]">
                                                                    {formatPrice(selectedProduct.lowest_price)}
                                                                </span>
                                                                <span className="text-[#9ca3af] ml-1">
                                                                    ({selectedProduct.offers_count || 0} aktif ilan)
                                                                </span>
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedProduct(null)}
                                                    className="p-1.5 rounded-lg hover:bg-white text-[#9ca3af] hover:text-[#1a1a1a] transition-colors flex-shrink-0"
                                                    title="Ürünü değiştir"
                                                >
                                                    <X className="w-4 h-4" />
                                                </button>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => setSelectedProduct(null)}
                                                className="text-xs text-[#b8651a] hover:underline flex items-center gap-1"
                                            >
                                                <Search className="w-3 h-3" />
                                                Farklı ürün seç
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Step 2: Price & Stock — shown only when product selected */}
                            {selectedProduct && (
                                <div className="bg-white rounded-2xl border border-[#e5e1de] overflow-hidden">
                                    <div className="px-5 py-4 border-b border-[#f0eceb] flex items-center gap-2.5">
                                        <div className="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                                            <Tag className="w-3.5 h-3.5 text-amber-600" />
                                        </div>
                                        <div>
                                            <h2 className="text-sm font-bold text-[#1a1a1a]">Fiyat & Stok</h2>
                                            <p className="text-[11px] text-[#9ca3af]">Satış fiyatı ve mevcut stok miktarı</p>
                                        </div>
                                    </div>

                                    <div className="p-5 space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            {/* Price */}
                                            <div className="space-y-1.5">
                                                <Label className="text-xs font-semibold text-[#6b7280] flex items-center gap-1">
                                                    <Tag className="w-3 h-3" />
                                                    Birim Fiyat *
                                                </Label>
                                                <div className="relative">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        placeholder="0.00"
                                                        value={price}
                                                        onChange={(e) => setPrice(e.target.value)}
                                                        className="w-full h-11 pl-4 pr-10 rounded-xl border border-[#e5e1de] bg-white text-sm font-semibold text-[#1a1a1a] placeholder:text-[#9ca3af] placeholder:font-normal focus:outline-none focus:border-[#b8651a] focus:ring-2 focus:ring-[#b8651a]/20 transition-all"
                                                    />
                                                    <span className="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-[#9ca3af]">
                                                        ₺
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Stock */}
                                            <div className="space-y-1.5">
                                                <Label className="text-xs font-semibold text-[#6b7280] flex items-center gap-1">
                                                    <Boxes className="w-3 h-3" />
                                                    Stok Adedi *
                                                </Label>
                                                <div className="relative">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        placeholder="0"
                                                        value={stock}
                                                        onChange={(e) => setStock(e.target.value)}
                                                        className="w-full h-11 pl-4 pr-14 rounded-xl border border-[#e5e1de] bg-white text-sm font-semibold text-[#1a1a1a] placeholder:text-[#9ca3af] placeholder:font-normal focus:outline-none focus:border-[#b8651a] focus:ring-2 focus:ring-[#b8651a]/20 transition-all"
                                                    />
                                                    <span className="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-[#9ca3af]">
                                                        adet
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Price comparison */}
                                        {price &&
                                            !isNaN(enteredPrice) &&
                                            enteredPrice > 0 &&
                                            selectedProduct.lowest_price != null &&
                                            selectedProduct.lowest_price > 0 && (
                                                <PriceComparison
                                                    entered={enteredPrice}
                                                    lowest={selectedProduct.lowest_price}
                                                />
                                            )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* RIGHT: Additional Info + Summary */}
                        <div className="lg:col-span-2 space-y-5">
                            {/* Additional Details */}
                            <div className="bg-white rounded-2xl border border-[#e5e1de] overflow-hidden">
                                <div className="px-5 py-4 border-b border-[#f0eceb] flex items-center gap-2.5">
                                    <div className="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center">
                                        <FileText className="w-3.5 h-3.5 text-purple-500" />
                                    </div>
                                    <div>
                                        <h2 className="text-sm font-bold text-[#1a1a1a]">Ek Bilgiler</h2>
                                        <p className="text-[11px] text-[#9ca3af]">İsteğe bağlı detaylar</p>
                                    </div>
                                </div>

                                <div className="p-5 space-y-4">
                                    {/* Expiry Date */}
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold text-[#6b7280] flex items-center gap-1">
                                            <Calendar className="w-3 h-3" />
                                            SKT (Son Kullanma Tarihi)
                                        </Label>
                                        <MonthYearPicker
                                            value={expiryDate}
                                            onChange={setExpiryDate}
                                            onClear={() => setExpiryDate('')}
                                        />
                                    </div>

                                    {/* Batch Number */}
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold text-[#6b7280] flex items-center gap-1">
                                            <Hash className="w-3 h-3" />
                                            Parti Numarası
                                        </Label>
                                        <input
                                            type="text"
                                            placeholder="Opsiyonel"
                                            value={batchNumber}
                                            onChange={(e) => setBatchNumber(e.target.value)}
                                            className="w-full h-11 px-4 rounded-xl border border-[#e5e1de] bg-white text-sm text-[#1a1a1a] placeholder:text-[#9ca3af] focus:outline-none focus:border-[#b8651a] focus:ring-2 focus:ring-[#b8651a]/20 transition-all"
                                        />
                                    </div>

                                    {/* Notes */}
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold text-[#6b7280] flex items-center gap-1">
                                            <FileText className="w-3 h-3" />
                                            Notlar
                                        </Label>
                                        <textarea
                                            placeholder="Alıcıya iletmek istediğiniz notlar..."
                                            value={notes}
                                            onChange={(e) => setNotes(e.target.value)}
                                            rows={3}
                                            className="w-full px-4 py-3 rounded-xl border border-[#e5e1de] bg-white text-sm text-[#1a1a1a] placeholder:text-[#9ca3af] focus:outline-none focus:border-[#b8651a] focus:ring-2 focus:ring-[#b8651a]/20 transition-all resize-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Summary Card */}
                            <div className="bg-white rounded-2xl border border-[#e5e1de] overflow-hidden">
                                <div className="px-5 py-4 border-b border-[#f0eceb] flex items-center gap-2.5">
                                    <div className="w-7 h-7 rounded-lg bg-[#fbeede] flex items-center justify-center">
                                        <ShoppingCart className="w-3.5 h-3.5 text-[#b8651a]" />
                                    </div>
                                    <h2 className="text-sm font-bold text-[#1a1a1a]">Özet</h2>
                                </div>
                                <div className="p-5 space-y-3">
                                    <SummaryRow
                                        label="Ürün"
                                        value={selectedProduct?.name || '—'}
                                        highlight={!!selectedProduct}
                                    />
                                    <SummaryRow
                                        label="Fiyat"
                                        value={price && parseFloat(price) > 0 ? formatPrice(parseFloat(price)) : '—'}
                                        highlight={!!(price && parseFloat(price) > 0)}
                                    />
                                    <SummaryRow
                                        label="Stok"
                                        value={stock && parseInt(stock) > 0 ? `${parseInt(stock)} adet` : '—'}
                                        highlight={!!(stock && parseInt(stock) > 0)}
                                    />
                                    <SummaryRow
                                        label="SKT"
                                        value={expiryDate
                                            ? `${MONTHS[new Date(expiryDate).getMonth()]} ${new Date(expiryDate).getFullYear()}`
                                            : 'Miadı yok'}
                                    />
                                    {batchNumber && (
                                        <SummaryRow label="Parti No" value={batchNumber} />
                                    )}

                                    <div className="pt-3 border-t border-[#f0eceb]">
                                        {isFormValid ? (
                                            <div className="flex items-center gap-2 text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2.5">
                                                <CheckCircle2 className="w-4 h-4 flex-shrink-0" />
                                                <p className="text-xs font-semibold">İlan yayınlanmaya hazır</p>
                                            </div>
                                        ) : (
                                            <div className="flex items-center gap-2 text-[#9ca3af] bg-[#faf8f6] border border-[#f0eceb] rounded-xl px-3 py-2.5">
                                                <AlertCircle className="w-4 h-4 flex-shrink-0" />
                                                <p className="text-xs">Zorunlu alanları doldurun</p>
                                            </div>
                                        )}
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={!isFormValid || isSubmitting}
                                        className="w-full bg-[#b8651a] hover:bg-[#934f12] text-white rounded-xl font-bold h-11 disabled:opacity-50 mt-1"
                                    >
                                        {isSubmitting ? (
                                            <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Yayınlanıyor...</>
                                        ) : (
                                            <><Sparkles className="w-4 h-4 mr-2" />İlanı Yayınla</>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {showScanner && (
                <BarcodeScanner
                    onScan={handleScanResult}
                    onClose={() => setShowScanner(false)}
                />
            )}
        </div>
    );
}

function SummaryRow({
    label,
    value,
    highlight = false,
}: {
    label: string;
    value: string;
    highlight?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-3">
            <span className="text-xs text-[#9ca3af] flex-shrink-0">{label}</span>
            <span className={cn(
                'text-xs font-semibold text-right truncate max-w-[160px]',
                highlight ? 'text-[#1a1a1a]' : 'text-[#9ca3af]'
            )}>
                {value}
            </span>
        </div>
    );
}
