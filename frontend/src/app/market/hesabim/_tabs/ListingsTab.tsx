'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import { offersApi, Offer, CreateOfferData, UpdateOfferData, productsApi, Product } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Box,
    Plus,
    Search,
    Filter,
    Eye,
    Edit,
    CheckCircle2,
    XCircle,
    X,
    Loader2,
    ChevronLeft,
    ChevronRight,
    Calendar,
    TrendingDown,
    Package,
    Tag,
    Hash,
    FileText,
    BarChart3,
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { toast } from 'sonner';

// Month/Year Picker Component
const MONTHS = [
    'Oca', 'Sub', 'Mar', 'Nis', 'May', 'Haz',
    'Tem', 'Agu', 'Eyl', 'Eki', 'Kas', 'Ara',
];

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
        if (value) {
            const d = new Date(value);
            return d.getFullYear();
        }
        return currentYear;
    });
    const [isOpen, setIsOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const selectedMonth = value ? new Date(value).getMonth() : null;
    const selectedYear = value ? new Date(value).getFullYear() : null;

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setIsOpen(false);
            }
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
        const dateStr = `${pickerYear}-${String(monthIdx + 1).padStart(2, '0')}-01`;
        onChange(dateStr);
        setIsOpen(false);
    };

    const displayValue = value
        ? `${MONTHS[selectedMonth!]} ${selectedYear}`
        : '';

    return (
        <div className="relative" ref={ref}>
            <div
                className="flex items-center h-9 w-full rounded-xl border border-[#f0eceb] bg-white px-3 text-sm cursor-pointer hover:border-[#ea580c] transition-colors"
                onClick={() => setIsOpen(!isOpen)}
            >
                <Calendar className="w-3.5 h-3.5 text-[#6b7280] mr-2 flex-shrink-0" />
                <span className={displayValue ? 'text-[#1a1a1a]' : 'text-[#6b7280]'}>
                    {displayValue || 'Ay / Yil secin'}
                </span>
                {value && (
                    <button
                        type="button"
                        onClick={(e) => { e.stopPropagation(); onClear(); }}
                        className="ml-auto text-[#6b7280] hover:text-[#1a1a1a]"
                    >
                        <X className="w-3.5 h-3.5" />
                    </button>
                )}
            </div>

            {isOpen && (
                <div className="absolute z-50 top-full mt-1 left-0 w-[240px] bg-white border border-[#f0eceb] rounded-2xl p-3 animate-in fade-in-0 zoom-in-95 duration-150">
                    <div className="flex items-center justify-between mb-2">
                        <button
                            type="button"
                            onClick={() => setPickerYear(y => y - 1)}
                            disabled={pickerYear <= currentYear}
                            className="p-1 rounded-lg hover:bg-[#faf8f6] disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <ChevronLeft className="w-4 h-4 text-[#1a1a1a]" />
                        </button>
                        <span className="text-sm font-bold text-[#1a1a1a]">{pickerYear}</span>
                        <button
                            type="button"
                            onClick={() => setPickerYear(y => y + 1)}
                            disabled={pickerYear >= currentYear + 10}
                            className="p-1 rounded-lg hover:bg-[#faf8f6] disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <ChevronRight className="w-4 h-4 text-[#1a1a1a]" />
                        </button>
                    </div>
                    <div className="grid grid-cols-4 gap-1">
                        {MONTHS.map((m, idx) => {
                            const disabled = isMonthDisabled(idx);
                            const selected = selectedMonth === idx && selectedYear === pickerYear;
                            return (
                                <button
                                    key={idx}
                                    type="button"
                                    disabled={disabled}
                                    onClick={() => handleSelect(idx)}
                                    className={`
                                        text-xs py-1.5 rounded-lg font-medium transition-all
                                        ${disabled
                                            ? 'text-[#f0eceb] cursor-not-allowed'
                                            : selected
                                                ? 'bg-[#1e3a8a] text-white'
                                                : 'text-[#6b7280] hover:bg-[#faf8f6] hover:text-[#1a1a1a]'
                                        }
                                    `}
                                >
                                    {m}
                                </button>
                            );
                        })}
                    </div>
                    <p className="text-[10px] text-[#6b7280] mt-2 text-center">Miatsiz urun icin bos birakin</p>
                </div>
            )}
        </div>
    );
}

// Offer status mapping
const OFFER_STATUS_MAP: Record<string, string> = {
    'aktif-ilanlar': 'active',
    'bekleyen-ilanlar': 'pending',
    'pasif-ilanlar': 'inactive',
    'reddedilen-ilanlar': 'rejected',
};

const OFFER_STATUS_LABELS: Record<string, string> = {
    'pending': 'Onay Bekliyor',
    'active': 'Aktif',
    'inactive': 'Pasif',
    'sold_out': 'Tukendi',
    'rejected': 'Reddedildi',
};

const OFFER_STATUS_COLORS: Record<string, string> = {
    'pending': 'bg-amber-50 text-amber-700',
    'active': 'bg-[#ffedd5] text-[#c2410c]',
    'inactive': 'bg-slate-100 text-slate-600',
    'sold_out': 'bg-red-50 text-red-700',
    'rejected': 'bg-red-50 text-red-700',
};

// Filter pill config
const STATUS_FILTERS = [
    { key: 'all', label: 'Tumu' },
    { key: 'active', label: 'Aktif' },
    { key: 'inactive', label: 'Pasif' },
    { key: 'sold_out', label: 'Tukenmis' },
];

// Listings Content
export function ListingsContent({ subNav }: { subNav: string }) {
    const router = useRouter();
    const [offers, setOffers] = useState<Offer[]>([]);
    const [loading, setLoading] = useState(true);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<Product[]>([]);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [isSearching, setIsSearching] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editingOffer, setEditingOffer] = useState<Offer | null>(null);

    // Form state
    const [formData, setFormData] = useState({
        price: '',
        stock: '',
        expiry_date: '',
        batch_number: '',
        notes: '',
    });

    // Load offers on mount and when subNav changes
    useEffect(() => {
        loadOffers();
    }, [subNav]);

    const loadOffers = async () => {
        setLoading(true);
        try {
            const status = OFFER_STATUS_MAP[subNav];
            const response = await offersApi.getMyOffers({ status });
            if (response.data) {
                setOffers(response.data.offers);
            }
        } catch (error) {
            console.error('Failed to load offers:', error);
            toast.error('İlanlar yuklenirken hata olustu');
        } finally {
            setLoading(false);
        }
    };

    // Toggle offer status (active/inactive)
    const handleToggleStatus = async (offerId: number) => {
        try {
            const response = await offersApi.toggleStatus(offerId);
            if (response.data) {
                toast.success(response.data.message);
                loadOffers(); // Reload the list
            }
        } catch (error) {
            console.error('Failed to toggle status:', error);
            toast.error('Durum degistirilemedi');
        }
    };

    // Debounced search
    const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    const handleProductSearch = useCallback((query: string) => {
        setSearchQuery(query);
        if (searchTimeout.current) clearTimeout(searchTimeout.current);

        if (query.length < 2) {
            setSearchResults([]);
            setIsSearching(false);
            return;
        }

        setIsSearching(true);
        searchTimeout.current = setTimeout(async () => {
            try {
                const response = await productsApi.search(query);
                if (response.data) {
                    setSearchResults(response.data.products);
                }
            } catch (error) {
                console.error('Search failed:', error);
            } finally {
                setIsSearching(false);
            }
        }, 300);
    }, []);

    // Create offer
    // Edit offer handler
    const handleEditOffer = (offer: Offer) => {
        setEditingOffer(offer);
        setFormData({
            price: String(offer.price),
            stock: String(offer.stock),
            expiry_date: offer.expiry_date || '',
            batch_number: offer.batch_number || '',
            notes: offer.notes || '',
        });
        setSelectedProduct(offer.product || null);
        setIsDialogOpen(true);
    };

    const handleCreateOffer = async () => {
        if (!editingOffer && !selectedProduct) {
            toast.error('Lutfen bir urun secin');
            return;
        }

        if (!formData.price || !formData.stock) {
            toast.error('Lutfen fiyat ve stok bilgilerini doldurun');
            return;
        }

        setIsSubmitting(true);
        try {
            if (editingOffer) {
                // Update existing offer
                const updateData: UpdateOfferData = {
                    price: parseFloat(formData.price),
                    stock: parseInt(formData.stock),
                    expiry_date: formData.expiry_date || undefined,
                    batch_number: formData.batch_number || undefined,
                    notes: formData.notes || undefined,
                };

                const response = await offersApi.update(editingOffer.id, updateData);
                if (response.data) {
                    toast.success('Ilan basariyla guncellendi!');
                    setIsDialogOpen(false);
                    resetForm();
                    loadOffers();
                } else if (response.error) {
                    toast.error(response.error);
                }
            } else {
                // Create new offer
                const offerData: CreateOfferData = {
                    product_id: selectedProduct!.id,
                    price: parseFloat(formData.price),
                    stock: parseInt(formData.stock),
                    expiry_date: formData.expiry_date || undefined,
                    batch_number: formData.batch_number || undefined,
                    notes: formData.notes || undefined,
                };

                const response = await offersApi.create(offerData);
                if (response.data) {
                    toast.success('Ilan basariyla olusturuldu ve yayinlandi!', { position: 'bottom-right' });
                    setIsDialogOpen(false);
                    resetForm();
                    loadOffers();
                } else if (response.error) {
                    toast.error(response.error);
                }
            }
        } catch (error) {
            toast.error(editingOffer ? 'Ilan guncellenirken hata olustu' : 'Ilan olusturulurken hata olustu');
        } finally {
            setIsSubmitting(false);
        }
    };

    const resetForm = () => {
        setEditingOffer(null);
        setSelectedProduct(null);
        setSearchQuery('');
        setSearchResults([]);
        setFormData({
            price: '',
            stock: '',
            expiry_date: '',
            batch_number: '',
            notes: '',
        });
    };

    // Delete offer
    const handleDeleteOffer = async (offerId: number) => {
        if (!confirm('Bu ilani silmek istediginize emin misiniz?')) return;

        try {
            await offersApi.delete(offerId);
            toast.success('Ilan silindi');
            loadOffers();
        } catch (error) {
            toast.error('Ilan silinirken hata olustu');
        }
    };

    // Format price
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(price);
    };

    // Format date
    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('tr-TR');
    };

    return (
        <div className="space-y-6">
            {/* Top Bar: Filter Pills + Search + Add */}
            <div className="flex flex-col gap-4">
                {/* Inline pill filters */}
                <div className="flex items-center gap-2 flex-wrap">
                    {STATUS_FILTERS.map((filter) => {
                        const isActive = (filter.key === 'all' && !OFFER_STATUS_MAP[subNav]) ||
                            OFFER_STATUS_MAP[subNav] === filter.key;
                        return (
                            <span
                                key={filter.key}
                                className={`px-4 py-1.5 rounded-full text-sm cursor-pointer transition-colors select-none ${
                                    isActive
                                        ? 'bg-[#1a1a1a] text-white font-semibold'
                                        : 'bg-[#faf8f6] text-[#6b7280] hover:bg-[#f0eceb]'
                                }`}
                            >
                                {filter.label}
                            </span>
                        );
                    })}
                </div>

                {/* Search + Add button */}
                <div className="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <div className="relative flex-1 sm:flex-none">
                        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#6b7280]" />
                        <Input
                            placeholder="Ilan ara..."
                            className="pl-9 w-full sm:w-64 border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                        />
                    </div>

                    <Link href="/market/hesabim/ilanlarim/yeni">
                        <Button className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl font-semibold w-full sm:w-auto">
                            <Plus className="w-4 h-4 mr-2" />
                            Yeni İlan Ekle
                        </Button>
                    </Link>

                    <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
                        <DialogTrigger asChild>
                            <span className="hidden" />
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[540px] max-h-[90vh] flex flex-col p-0 gap-0 overflow-hidden rounded-2xl border-[#f0eceb]">
                            {/* Header */}
                            <div className="px-5 pt-5 pb-3 border-b border-[#f0eceb] bg-white flex-shrink-0">
                                <DialogHeader>
                                    <DialogTitle className="text-lg font-black text-[#1a1a1a] flex items-center gap-2">
                                        {editingOffer ? (
                                            <><Edit className="w-4 h-4 text-[#ea580c]" /> İlanı Düzenle</>
                                        ) : (
                                            <><Plus className="w-4 h-4 text-[#ea580c]" /> Yeni İlan Oluştur</>
                                        )}
                                    </DialogTitle>
                                    <DialogDescription className="text-xs text-[#6b7280]">
                                        {editingOffer ? 'Fiyat, stok ve diger bilgileri guncelleyin.' : 'Ürün secin, fiyat ve stok bilgilerini girin.'}
                                    </DialogDescription>
                                </DialogHeader>
                            </div>

                            {/* Body */}
                            <div className="px-5 py-4 overflow-y-auto flex-1 space-y-4">

                                {/* STEP 1: Product Search / Selection */}
                                {!selectedProduct ? (
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a] flex items-center gap-1.5">
                                            <Search className="w-3 h-3" /> Ürün Ara
                                        </Label>
                                        <div className="relative">
                                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#6b7280]" />
                                            <Input
                                                placeholder="Ürün adi veya barkod ile arayin..."
                                                value={searchQuery}
                                                onChange={(e) => handleProductSearch(e.target.value)}
                                                className="pl-9 h-10 bg-[#faf8f6] border-[#f0eceb] rounded-xl focus:bg-white focus:border-[#ea580c] focus:ring-[#ea580c] transition-colors"
                                                autoFocus
                                            />
                                            {isSearching && (
                                                <Loader2 className="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 animate-spin text-[#ea580c]" />
                                            )}
                                        </div>

                                        {/* Search Results */}
                                        {searchResults.length > 0 && (
                                            <div className="border border-[#f0eceb] rounded-2xl overflow-hidden">
                                                <div className="max-h-[220px] overflow-y-auto">
                                                    {searchResults.map((product, idx) => (
                                                        <button
                                                            key={product.id}
                                                            type="button"
                                                            onClick={() => {
                                                                setSelectedProduct(product);
                                                                setSearchQuery('');
                                                                setSearchResults([]);
                                                            }}
                                                            className={`w-full p-2.5 text-left hover:bg-[#faf8f6] flex items-center gap-3 transition-colors ${
                                                                idx !== searchResults.length - 1 ? 'border-b border-[#f0eceb]' : ''
                                                            }`}
                                                        >
                                                            {(product.image_url || product.image) ? (
                                                                <Image src={(product.image_url || product.image) as string} alt="" width={40} height={40} className="w-10 h-10 object-cover rounded-xl flex-shrink-0 border border-[#f0eceb]" loading="lazy" />
                                                            ) : (
                                                                <div className="w-10 h-10 bg-[#faf8f6] rounded-xl flex items-center justify-center flex-shrink-0">
                                                                    <Package className="w-4 h-4 text-[#6b7280]" />
                                                                </div>
                                                            )}
                                                            <div className="flex-1 min-w-0">
                                                                <p className="font-medium text-sm text-[#1a1a1a] truncate">{product.name}</p>
                                                                <div className="flex items-center gap-2 mt-0.5">
                                                                    <span className="text-[11px] text-[#6b7280] font-mono">{product.barcode}</span>
                                                                    {product.brand && (
                                                                        <span className="text-[10px] text-[#ea580c] uppercase font-semibold">{product.brand}</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                            <div className="flex-shrink-0 text-right">
                                                                {product.lowest_price != null && product.lowest_price > 0 ? (
                                                                    <div>
                                                                        <p className="text-xs font-semibold text-[#ea580c]">{formatPrice(product.lowest_price)}</p>
                                                                        <p className="text-[10px] text-[#6b7280]">{product.offers_count || 0} ilan</p>
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-[10px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded-full font-medium">Ilan Yok</span>
                                                                )}
                                                            </div>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {searchQuery.length >= 2 && !isSearching && searchResults.length === 0 && (
                                            <div className="text-center py-6 text-[#6b7280] text-sm">
                                                <Package className="w-8 h-8 mx-auto mb-2 opacity-50" />
                                                <p>Ürün bulunamadi</p>
                                            </div>
                                        )}

                                        {searchQuery.length < 2 && (
                                            <div className="text-center py-8 text-[#6b7280]">
                                                <Search className="w-10 h-10 mx-auto mb-2 opacity-30" />
                                                <p className="text-sm">İlan olusturmak icin ürün arayin</p>
                                                <p className="text-[11px] mt-1">En az 2 karakter girin</p>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <>
                                        {/* Selected Product Card */}
                                        <div className="rounded-2xl border border-[#f0eceb] bg-[#faf8f6] overflow-hidden">
                                            <div className="p-3 flex items-center gap-3">
                                                {(selectedProduct.image_url || selectedProduct.image) ? (
                                                    <Image src={(selectedProduct.image_url || selectedProduct.image) as string} alt="" width={48} height={48} className="w-12 h-12 object-cover rounded-xl flex-shrink-0 border border-[#f0eceb]" loading="lazy" />
                                                ) : (
                                                    <div className="w-12 h-12 bg-white rounded-xl flex items-center justify-center flex-shrink-0 border border-[#f0eceb]">
                                                        <Package className="w-5 h-5 text-[#ea580c]" />
                                                    </div>
                                                )}
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium text-sm text-[#1a1a1a] truncate">{selectedProduct.name}</p>
                                                    <div className="flex items-center gap-2 mt-0.5">
                                                        <span className="text-[11px] text-[#6b7280] font-mono">{selectedProduct.barcode}</span>
                                                        {selectedProduct.brand && (
                                                            <span className="text-[10px] text-[#ea580c] uppercase font-semibold">{selectedProduct.brand}</span>
                                                        )}
                                                    </div>
                                                </div>
                                                {!editingOffer && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setSelectedProduct(null)}
                                                        className="p-1.5 rounded-xl hover:bg-white text-[#6b7280] hover:text-[#1a1a1a] transition-colors"
                                                    >
                                                        <X className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>

                                            {/* Competitive Price Indicator */}
                                            {selectedProduct.lowest_price != null && selectedProduct.lowest_price > 0 && (
                                                <div className="px-3 py-2 border-t border-[#f0eceb] bg-white flex items-center gap-2">
                                                    <TrendingDown className="w-3.5 h-3.5 text-[#ea580c] flex-shrink-0" />
                                                    <p className="text-xs text-[#6b7280]">
                                                        En dusuk fiyat: <span className="font-bold text-[#ea580c]">{formatPrice(selectedProduct.lowest_price)}</span>
                                                        <span className="text-[#6b7280] ml-1">({selectedProduct.offers_count || 0} aktif ilan)</span>
                                                    </p>
                                                </div>
                                            )}
                                        </div>

                                        {/* STEP 2: Price & Stock */}
                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-[#1a1a1a] flex items-center gap-1.5">
                                                <Tag className="w-3 h-3" /> Fiyat ve Stok
                                            </Label>
                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="space-y-1">
                                                    <Label htmlFor="price" className="text-[11px] text-[#6b7280]">Birim Fiyat *</Label>
                                                    <div className="relative">
                                                        <Input
                                                            id="price"
                                                            type="number"
                                                            step="0.01"
                                                            min="0.01"
                                                            placeholder="0.00"
                                                            value={formData.price}
                                                            onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                                                            className="h-10 pr-9 border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                                        />
                                                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#6b7280] font-medium">TL</span>
                                                    </div>
                                                    {/* Price comparison feedback */}
                                                    {formData.price && selectedProduct?.lowest_price != null && selectedProduct.lowest_price > 0 && (
                                                        <p className={`text-[10px] font-medium ${
                                                            parseFloat(formData.price) < selectedProduct.lowest_price
                                                                ? 'text-[#ea580c]'
                                                                : parseFloat(formData.price) === selectedProduct.lowest_price
                                                                    ? 'text-amber-600'
                                                                    : 'text-[#6b7280]'
                                                        }`}>
                                                            {parseFloat(formData.price) < selectedProduct.lowest_price
                                                                ? `En ucuz ilan sizinki olacak!`
                                                                : parseFloat(formData.price) === selectedProduct.lowest_price
                                                                    ? 'En dusuk fiyatla esit'
                                                                    : `En ucuz ilandan ${formatPrice(parseFloat(formData.price) - selectedProduct.lowest_price)} daha pahali`
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor="stock" className="text-[11px] text-[#6b7280]">Stok Adedi *</Label>
                                                    <div className="relative">
                                                        <Input
                                                            id="stock"
                                                            type="number"
                                                            min="1"
                                                            placeholder="0"
                                                            value={formData.stock}
                                                            onChange={(e) => setFormData({ ...formData, stock: e.target.value })}
                                                            className="h-10 pr-12 border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                                        />
                                                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#6b7280] font-medium">adet</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* STEP 3: Additional Details */}
                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-[#1a1a1a] flex items-center gap-1.5">
                                                <BarChart3 className="w-3 h-3" /> Ek Bilgiler
                                            </Label>
                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="space-y-1">
                                                    <Label className="text-[11px] text-[#6b7280] flex items-center gap-1">
                                                        <Calendar className="w-3 h-3" /> SKT (Ay/Yil)
                                                    </Label>
                                                    <MonthYearPicker
                                                        value={formData.expiry_date}
                                                        onChange={(val) => setFormData({ ...formData, expiry_date: val })}
                                                        onClear={() => setFormData({ ...formData, expiry_date: '' })}
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor="batch_number" className="text-[11px] text-[#6b7280] flex items-center gap-1">
                                                        <Hash className="w-3 h-3" /> Parti No
                                                    </Label>
                                                    <Input
                                                        id="batch_number"
                                                        placeholder="Opsiyonel"
                                                        value={formData.batch_number}
                                                        onChange={(e) => setFormData({ ...formData, batch_number: e.target.value })}
                                                        className="h-9 border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                                    />
                                                </div>
                                            </div>

                                            {/* Notes */}
                                            <div className="space-y-1">
                                                <Label htmlFor="notes" className="text-[11px] text-[#6b7280] flex items-center gap-1">
                                                    <FileText className="w-3 h-3" /> Not
                                                </Label>
                                                <Input
                                                    id="notes"
                                                    placeholder="Opsiyonel notlar..."
                                                    value={formData.notes}
                                                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                                    className="h-9 border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                                />
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>

                            {/* Footer */}
                            <div className="px-5 py-3 border-t border-[#f0eceb] bg-[#faf8f6] flex items-center justify-end gap-2 flex-shrink-0">
                                <Button variant="outline" size="sm" onClick={() => setIsDialogOpen(false)} className="border-[#f0eceb] hover:bg-white rounded-xl">
                                    Iptal
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={handleCreateOffer}
                                    disabled={!selectedProduct || isSubmitting || !formData.price || !formData.stock}
                                    className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl font-semibold min-w-[120px]"
                                >
                                    {isSubmitting ? (
                                        <>
                                            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                            {editingOffer ? 'Kaydediliyor...' : 'Olusturuluyor...'}
                                        </>
                                    ) : (
                                        editingOffer ? 'Degisiklikleri Kaydet' : 'Ilan Olustur'
                                    )}
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            {/* Offers List */}
            {loading ? (
                <div className="space-y-3">
                    {[1, 2, 3].map((i) => (
                        <Skeleton key={i} className="h-20 w-full rounded-2xl" />
                    ))}
                </div>
            ) : offers.length === 0 ? (
                <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                    <Box className="w-16 h-16 mx-auto text-[#f0eceb] mb-4" />
                    <p className="text-sm text-[#6b7280] mb-4">
                        {subNav === 'aktif-ilanlar' && 'Aktif ilaniniz bulunmuyor'}
                        {subNav === 'pasif-ilanlar' && 'Pasif ilaniniz bulunmuyor'}
                    </p>
                    <Link href="/market/hesabim/ilanlarim/yeni">
                        <Button variant="outline" className="border-[#f0eceb] hover:bg-white rounded-xl">
                            <Plus className="w-4 h-4 mr-2" />
                            İlk İlanınızı Oluşturun
                        </Button>
                    </Link>
                </div>
            ) : (
                <div>
                    {offers.map((offer) => (
                        <div
                            key={offer.id}
                            className="flex items-center gap-4 py-4 border-b border-[#f0eceb] last:border-0"
                        >
                            {/* Product Image */}
                            <div className="w-16 h-16 rounded-xl bg-[#faf8f6] overflow-hidden flex-shrink-0 flex items-center justify-center">
                                {(offer.product?.image_url || offer.product?.image) ? (
                                    <img
                                        src={offer.product.image_url || offer.product.image}
                                        alt={offer.product.name}
                                        className="w-full h-full object-contain"
                                    />
                                ) : (
                                    <Box className="w-6 h-6 text-[#f0eceb]" />
                                )}
                            </div>

                            {/* Middle: Product info */}
                            <div className="flex-1 min-w-0">
                                <h4 className="text-sm font-medium text-[#1a1a1a] truncate">
                                    {offer.product?.name || 'Urun'}
                                </h4>
                                {offer.product?.brand && (
                                    <p className="text-xs text-[#ea580c] uppercase font-semibold mt-0.5">{offer.product.brand}</p>
                                )}
                                <p className="text-sm font-black text-[#1a1a1a] mt-1">{formatPrice(offer.price)}</p>
                                <div className="flex flex-wrap gap-3 mt-1 text-xs text-[#6b7280]">
                                    <span>Stok: {offer.stock}</span>
                                    <span>SKT: {offer.expiry_date ? formatDate(offer.expiry_date) : 'Miatsiz'}</span>
                                    {offer.batch_number && <span>Parti: {offer.batch_number}</span>}
                                </div>
                                {/* Rejection reason */}
                                {offer.status === 'rejected' && offer.rejection_reason && (
                                    <div className="mt-2 p-2 bg-red-50 border border-red-100 rounded-xl">
                                        <p className="text-xs text-red-700">
                                            <strong>Ret Sebebi:</strong> {offer.rejection_reason}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Right: Status + Actions */}
                            <div className="flex flex-col items-end gap-2 flex-shrink-0">
                                <Badge className={`px-3 py-1 rounded-full text-xs font-semibold ${OFFER_STATUS_COLORS[offer.status]}`}>
                                    {OFFER_STATUS_LABELS[offer.status]}
                                </Badge>
                                <div className="flex items-center gap-1">
                                    {/* Toggle Active/Inactive */}
                                    {(offer.status === 'active' || offer.status === 'inactive') && (
                                        <button
                                            onClick={() => handleToggleStatus(offer.id)}
                                            className="p-1.5 rounded-lg hover:bg-[#faf8f6] text-[#6b7280] hover:text-[#1a1a1a] transition-colors"
                                            title={offer.status === 'active' ? 'Pasife Al' : 'Aktife Al'}
                                        >
                                            {offer.status === 'active' ? (
                                                <XCircle className="w-4 h-4" />
                                            ) : (
                                                <CheckCircle2 className="w-4 h-4" />
                                            )}
                                        </button>
                                    )}
                                    {/* Edit */}
                                    <button
                                        onClick={() => handleEditOffer(offer)}
                                        className="p-1.5 rounded-lg hover:bg-[#faf8f6] text-[#6b7280] hover:text-[#ea580c] transition-colors"
                                        title="Duzenle"
                                    >
                                        <Edit className="w-4 h-4" />
                                    </button>
                                    {/* View */}
                                    <Link
                                        href={`/market/product/${offer.product_id}`}
                                        className="p-1.5 rounded-lg hover:bg-[#faf8f6] text-[#6b7280] hover:text-[#1a1a1a] transition-colors"
                                        title="Urunu Gor"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Link>
                                    {/* Delete */}
                                    <button
                                        onClick={() => handleDeleteOffer(offer.id)}
                                        className="p-1.5 rounded-lg hover:bg-red-50 text-[#6b7280] hover:text-red-600 transition-colors"
                                        title="Sil"
                                    >
                                        <XCircle className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
