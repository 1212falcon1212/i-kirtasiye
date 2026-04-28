'use client';

import { useState, useEffect, Suspense } from 'react';
import Link from 'next/link';
import dynamic from 'next/dynamic';
import { useRouter, useSearchParams } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { ordersApi, Order, SellerOrder, api, integrationsApi, UserIntegration, sellerApi, SellerOrderDetail, SellerStatsResponse, distributorRetailerLinkApi, DistributorRetailerLink, RetailerListItem, invoiceApi, offersApi, Offer, CreateOfferData, UpdateOfferData, productsApi, Product, wishlistApi, campaignsApi, Campaign, CreateCampaignData, reviewsApi, Review, SellerRating, shippingApi, returnsApi, ReturnRequest, ReturnReason, walletApi, addressApi, Address, BankAccount, authApi, notificationsApi, NotificationSetting, platformApi, FeeInfo, paymentsApi, SavedCard } from '@/lib/api';
import { CityDistrictSelect } from '@/components/forms/CityDistrictSelect';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { blogApi, BlogPost as BlogPostType } from '@/lib/api';
import { BlogCard } from '@/components/market/BlogCard';
import { useNotificationStore } from '@/stores/useNotificationStore';


// Dynamic imports for heavy tab components
const ListingsContent = dynamic(
    () => import('./_tabs/ListingsTab').then(mod => ({ default: mod.ListingsContent })),
    { ssr: false, loading: () => <div className="space-y-3"><Skeleton className="h-32 w-full" /><Skeleton className="h-32 w-full" /></div> }
);

const DynamicOrdersContent = dynamic(
    () => import('./_tabs/OrdersTab').then(mod => ({ default: mod.OrdersContent })),
    { ssr: false, loading: () => <div className="space-y-3"><Skeleton className="h-32 w-full" /><Skeleton className="h-32 w-full" /></div> }
);

const DynamicOrderDetailView = dynamic(
    () => import('./_tabs/OrdersTab').then(mod => ({ default: mod.OrderDetailView })),
    { ssr: false, loading: () => <div className="space-y-3"><Skeleton className="h-32 w-full" /><Skeleton className="h-32 w-full" /></div> }
);

const SupportTicketsContent = dynamic(
    () => import('./_tabs/SupportTicketsTab').then(mod => ({ default: mod.SupportTicketsContent })),
    { ssr: false, loading: () => <div className="space-y-3"><Skeleton className="h-32 w-full" /><Skeleton className="h-32 w-full" /></div> }
);

const SettlementsContent = dynamic(
    () => import('./_tabs/SettlementsTab').then(mod => ({ default: mod.SettlementsContent })),
    { ssr: false, loading: () => <div className="space-y-3"><Skeleton className="h-32 w-full" /><Skeleton className="h-32 w-full" /></div> }
);
import {
    Box,
    ShoppingBag,
    Heart,
    Wallet,
    FileText,
    Settings,
    Store,
    Plus,
    ArrowRight,
    TrendingUp,
    TrendingDown,
    Clock,
    CheckCircle2,
    Truck,
    XCircle,
    Eye,
    Edit,
    MoreVertical,
    Search,
    Filter,
    Calendar,
    CreditCard,
    Building2,
    MapPin,
    Phone,
    Mail,
    LogOut,
    ChevronRight,
    ChevronDown,
    AlertCircle,
    Info,
    Star,
    Tag,
    Percent,
    Home,
    User,
    Link2,
    Loader2,
    Handshake,
    Send,
    Check,
    X,
    Users,
    Trash2,
    Lock,
    MessageCircle
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';

import { toast } from 'sonner';
import { IntegrationCard } from '@/components/integrations/IntegrationCard';
import { SellerTypeBadge } from '@/components/ui/SellerTypeBadge';

// Tab definitions - base tabs for all users
const BASE_TABS = [
    { id: 'ilanlarim', label: 'İlanlarım', icon: Tag },
    { id: 'siparislerim', label: 'Siparişlerim', icon: ShoppingBag },
    { id: 'begendiklerim', label: 'Beğendiklerim', icon: Heart },
    { id: 'hesap-hareketlerim', label: 'Hesap Hareketlerim', icon: FileText },
    { id: 'destek', label: 'Destek', icon: MessageCircle },
    { id: 'ayarlarim', label: 'Ayarlarım & Bilgilerim', icon: Settings },
];

// Get tabs based on user role
const getTabs = (role?: string) => {
    const tabs = [...BASE_TABS];

    // Add role-specific tabs before 'ayarlarim'
    const insertIndex = tabs.findIndex(t => t.id === 'ayarlarim');

    if (role === 'retailer') {
        // Retailer users see "Tedarikçi İstekleri" tab
        tabs.splice(insertIndex, 0, { id: 'firma-istekleri', label: 'Tedarikçi İstekleri', icon: Handshake });
    } else if (role === 'seller') {
        // Seller users see "İstek Yolla" tab
        tabs.splice(insertIndex, 0, { id: 'istek-yolla', label: 'İstek Yolla', icon: Send });
    } else if (role === 'super-admin') {
        // Admin users see both tabs for management purposes
        tabs.splice(insertIndex, 0, { id: 'firma-istekleri', label: 'Tedarikçi İstekleri', icon: Handshake });
        tabs.splice(insertIndex + 1, 0, { id: 'istek-yolla', label: 'İstek Yolla', icon: Send });
    }

    return tabs;
};

// Default TABS for backward compatibility
const TABS = BASE_TABS;

// Sub-navigation for each tab
const TAB_SUBNAV: Record<string, { id: string; label: string; count?: number }[]> = {
    'ilanlarim': [
        { id: 'aktif-ilanlar', label: 'Aktif İlanlar' },
        { id: 'pasif-ilanlar', label: 'Pasif İlanlar' },
    ],
    'siparislerim': [
        { id: 'satin-aldiklarim', label: 'Satın Aldıklarım' },
        { id: 'sattiklarim', label: 'Sattıklarım' },
        { id: 'iade-talepleri', label: 'İade Talepleri' },
        { id: 'iptaller', label: 'İptaller / İadeler' },
        { id: 'iadelerim', label: 'İadelerim' },
    ],
    'begendiklerim': [
        { id: 'favoriler', label: 'Favori Ürünler' },
        { id: 'takip-ettiklerim', label: 'Takip Ettiklerim' },
    ],
    'hesap-hareketlerim': [
        { id: 'gelecek-odemeler', label: 'Gelecek Ödemeler' },
        { id: 'gecmis-odemeler', label: 'Geçmiş Ödemeler' },
        { id: 'bildirimler', label: 'Bildirimler' },
    ],
    'destek': [
        { id: 'taleplerim', label: 'Taleplerim' },
        { id: 'yeni-talep', label: 'Yeni Talep Oluştur' },
    ],
    'ayarlarim': [
        { id: 'kullanici-bilgilerim', label: 'Kullanıcı Bilgilerim' },
        { id: 'satis-bilgilerim', label: 'Satış Bilgilerim' },
        { id: 'adresler', label: 'Adreslerim' },
        { id: 'kartlarim', label: 'Kartlarım' },
        { id: 'bildirim-tercihleri', label: 'Bildirim Tercihleri' },
        { id: 'guvenlik', label: 'Güvenlik' },
        { id: 'erp-entegrasyonlari', label: 'ERP Entegrasyonları' },
    ],
    // For retailers - incoming distributor requests
    'firma-istekleri': [
        { id: 'bekleyen', label: 'Bekleyen İstekler' },
        { id: 'onaylanan', label: 'Onaylanan Bağlantılar' },
        { id: 'reddedilen', label: 'Reddedilen İstekler' },
    ],
    // For sellers (distributors) - send requests to retailers
    'istek-yolla': [
        { id: 'kirtasiye-ara', label: 'Kırtasiye Ara' },
        { id: 'gonderilen-istekler', label: 'Gönderilen İstekler' },
    ],
};


// SalesPanelContent - dynamically imported from ./_tabs/SalesPanelTab
// ListingsContent - dynamically imported from ./_tabs/ListingsTab
// OrdersContent, OrderDetailView - dynamically imported from ./_tabs/OrdersTab

// Kartlarim (Saved Cards) Content
function KartlarimContent({ subNav }: { subNav: string }) {
    const [cards, setCards] = useState<SavedCard[]>([]);
    const [loading, setLoading] = useState(true);
    const [deletingCtoken, setDeletingCtoken] = useState<string | null>(null);

    const fetchCards = async () => {
        setLoading(true);
        try {
            const res = await paymentsApi.getSavedCards();
            setCards(res.data?.cards || []);
        } catch {
            setCards([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCards();
    }, []);

    const handleDelete = async (ctoken: string) => {
        const confirmed = window.confirm('Bu karti silmek istediginize emin misiniz?');
        if (!confirmed) return;

        setDeletingCtoken(ctoken);
        try {
            await paymentsApi.deleteSavedCard(ctoken);
            setCards((prev) => prev.filter((c) => c.ctoken !== ctoken));
        } catch {
            // Silme basarisiz
        } finally {
            setDeletingCtoken(null);
        }
    };

    const getCardBrandLabel = (brand: string): string => {
        const b = brand.toLowerCase();
        if (b.includes('visa')) return 'VISA';
        if (b.includes('master')) return 'MC';
        if (b.includes('troy')) return 'TROY';
        if (b.includes('amex')) return 'AMEX';
        return brand.toUpperCase();
    };

    const getCardBrandColor = (brand: string): string => {
        const b = brand.toLowerCase();
        if (b.includes('visa')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        if (b.includes('master')) return 'bg-[#fbeede] text-[#934f12] dark:bg-[#934f12]/30 dark:text-[#fbeede]';
        if (b.includes('troy')) return 'bg-[#fbeede] text-[#b8651a] dark:bg-[#934f12]/30 dark:text-[#fbeede]';
        if (b.includes('amex')) return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
        return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400';
    };

    if (loading) {
        return (
            <div className="space-y-3">
                {[1, 2].map((i) => (
                    <div key={i} className="p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div className="flex items-center gap-4">
                            <Skeleton className="h-10 w-14 rounded" />
                            <div className="flex-1 space-y-2">
                                <Skeleton className="h-4 w-48" />
                                <Skeleton className="h-3 w-32" />
                            </div>
                            <Skeleton className="h-8 w-8 rounded" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (cards.length === 0) {
        return (
            <div className="text-center py-16">
                <div className="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <CreditCard className="h-8 w-8 text-slate-400 dark:text-slate-500" />
                </div>
                <h3 className="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                    Kayıtlı Kart Bulunamadi
                </h3>
                <p className="text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Henuz kayitli kartiniz yok. Ödeme sirasinda kartinizi kaydedebilirsiniz.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {cards.map((card) => (
                <div
                    key={card.ctoken}
                    className="flex items-center gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
                >
                    <div className={`w-14 h-10 rounded-lg flex items-center justify-center text-xs font-bold ${getCardBrandColor(card.c_brand)}`}>
                        {getCardBrandLabel(card.c_brand)}
                    </div>
                    <div className="flex-1 min-w-0">
                        <p className="font-medium text-slate-900 dark:text-white tracking-wider">
                            **** **** **** {card.last_4}
                        </p>
                        <div className="flex items-center gap-3 mt-0.5">
                            <span className="text-xs text-slate-500 dark:text-slate-400">
                                {card.c_bank}
                            </span>
                            <span className="text-xs text-slate-400 dark:text-slate-500">
                                {card.month}/{card.year}
                            </span>
                        </div>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleDelete(card.ctoken)}
                        disabled={deletingCtoken === card.ctoken}
                        className="text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 h-9 w-9 p-0"
                    >
                        {deletingCtoken === card.ctoken ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <Trash2 className="h-4 w-4" />
                        )}
                    </Button>
                </div>
            ))}
        </div>
    );
}

// Favorites Content
function FavoritesContent({ subNav }: { subNav: string }) {
    const [favorites, setFavorites] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [removingId, setRemovingId] = useState<number | null>(null);

    useEffect(() => {
        if (subNav === 'favoriler') {
            loadFavorites();
        }
    }, [subNav]);

    const loadFavorites = async () => {
        setLoading(true);
        try {
            const response = await wishlistApi.getAll();
            if (response.data?.data) {
                setFavorites(response.data.data);
            }
        } catch (error) {
            console.error('Failed to load favorites:', error);
            toast.error('Favoriler yüklenemedi');
        } finally {
            setLoading(false);
        }
    };

    const handleRemove = async (productId: number) => {
        setRemovingId(productId);
        try {
            await wishlistApi.toggle(productId);
            setFavorites(prev => prev.filter(item => item.product_id !== productId));
            toast.success('Favorilerden çıkarıldı');
        } catch (error) {
            console.error('Failed to remove from favorites:', error);
            toast.error('Bir hata oluştu');
        } finally {
            setRemovingId(null);
        }
    };

    const formatPrice = (price: number | string | undefined) => {
        const numPrice = Number(price) || 0;
        return numPrice.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    if (subNav === 'takip-ettiklerim') {
        return (
            <div className="text-center py-12 bg-slate-50 rounded-xl">
                <Heart className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                <p className="text-slate-500 mb-4">Takip ettiğiniz satıcı bulunmuyor</p>
                <Link href="/market">
                    <Button variant="outline">Ürünleri Keşfet</Button>
                </Link>
            </div>
        );
    }

    if (loading) {
        return (
            <div className="space-y-4">
                {[1, 2, 3].map((i) => (
                    <div key={i} className="bg-white border border-slate-200 rounded-xl p-4 animate-pulse">
                        <div className="flex gap-4">
                            <div className="w-20 h-20 bg-slate-200 rounded-lg" />
                            <div className="flex-1 space-y-2">
                                <div className="h-4 bg-slate-200 rounded w-3/4" />
                                <div className="h-3 bg-slate-200 rounded w-1/2" />
                                <div className="h-4 bg-slate-200 rounded w-1/4" />
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (favorites.length === 0) {
        return (
            <div className="text-center py-12 bg-slate-50 rounded-xl">
                <Heart className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                <p className="text-slate-500 mb-4">Favori ürün listeniz boş</p>
                <Link href="/market">
                    <Button variant="outline">Ürünleri Keşfet</Button>
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-slate-900">{favorites.length} ürün favorilerinizde</h3>
            </div>
            {favorites.map((item) => (
                <div key={item.id} className="bg-white border border-slate-200 rounded-xl p-4 hover:border-slate-300 transition-colors">
                    <div className="flex gap-4">
                        {/* Product Image */}
                        <Link href={`/market/product/${item.product_id}`} className="shrink-0">
                            <div className="w-20 h-20 bg-slate-50 rounded-lg flex items-center justify-center overflow-hidden border border-slate-100">
                                {item.product?.image ? (
                                    <img
                                        src={item.product.image.startsWith('http') ? item.product.image : `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/storage/${item.product.image}`}
                                        alt={item.product?.name || 'Ürün'}
                                        className="w-full h-full object-contain p-1"
                                    />
                                ) : (
                                    <Box className="w-8 h-8 text-slate-300" />
                                )}
                            </div>
                        </Link>

                        {/* Product Info */}
                        <div className="flex-1 min-w-0">
                            <Link href={`/market/product/${item.product_id}`} className="hover:text-[#b8651a] transition-colors">
                                <h4 className="font-semibold text-slate-900 line-clamp-2 mb-1">
                                    {item.product?.name || `Ürün #${item.product_id}`}
                                </h4>
                            </Link>
                            {item.product?.brand && (
                                <p className="text-sm text-slate-500 mb-1">{item.product.brand}</p>
                            )}
                            {item.product?.category?.name && (
                                <p className="text-xs text-slate-400">{item.product.category.name}</p>
                            )}
                            {item.product?.lowest_price && (
                                <p className="text-lg font-bold text-[#b8651a] mt-2">
                                    {formatPrice(item.product.lowest_price)} TL
                                </p>
                            )}
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col gap-2 shrink-0">
                            <Link href={`/market/product/${item.product_id}`}>
                                <Button size="sm" className="bg-[#b8651a] hover:bg-[#b8651a] text-white w-full">
                                    <Eye className="w-4 h-4 mr-1" />
                                    Görüntüle
                                </Button>
                            </Link>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleRemove(item.product_id)}
                                disabled={removingId === item.product_id}
                                className="text-red-500 border-red-200 hover:bg-red-50 hover:border-red-300"
                            >
                                {removingId === item.product_id ? (
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                ) : (
                                    <>
                                        <Trash2 className="w-4 h-4 mr-1" />
                                        Cikar
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// Wallet Content

// ERP Integration Data - Test edilmis entegrasyonlar
const AVAILABLE_INTEGRATIONS = [
    {
        id: 'bizimhesap',
        name: 'BizimHesap',
        description: 'BizimHesap muhasebe ve fatura entegrasyonu',
        logo: '/erp-logos/bizimhesap.png',
    },
    {
        id: 'parasut',
        name: 'Parasut',
        description: 'Parasut bulut muhasebe entegrasyonu',
        logo: '/erp-logos/parasut.png',
    },
    {
        id: 'entegra',
        name: 'Entegra',
        description: 'Entegra ERP entegrasyonu',
        logo: '/erp-logos/entegra.png',
    },
    {
        id: 'sentos',
        name: 'Sentos',
        description: 'Sentos ERP entegrasyonu (Basic Auth)',
        logo: '/erp-logos/sentos.png',
    },
    {
        id: 'stockmount',
        name: 'StockMount',
        description: 'StockMount sipariş ve ürün yönetimi',
        logo: '/erp-logos/stockmount.png',
    },
    {
        id: 'dopigo',
        name: 'Dopigo',
        description: 'Dopigo sipariş ve ürün yönetimi',
        logo: '/erp-logos/dopigo.png',
    },
    {
        id: 'kolaysoft',
        name: 'KolaySoft',
        description: 'KolaySoft E-Fatura ve E-Arsiv entegrasyonu',
        logo: '/erp-logos/kolaysoft.png',
    },
];

// Addresses Content
function AddressesContent({ user }: { user: import('@/lib/api').User | null }) {
    const [addresses, setAddresses] = useState<Address[]>([]);
    const [loading, setLoading] = useState(true);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [editingAddress, setEditingAddress] = useState<Address | null>(null);
    const [formData, setFormData] = useState<{
        title: string;
        name: string;
        phone: string;
        address: string;
        city: string;
        district: string;
        city_id?: number;
        district_id?: number;
        postal_code: string;
        is_default: boolean;
    }>({
        title: '',
        name: '',
        phone: '',
        address: '',
        city: '',
        district: '',
        postal_code: '',
        is_default: false,
    });

    useEffect(() => {
        loadAddresses();
    }, []);

    const loadAddresses = async () => {
        setLoading(true);
        try {
            const res = await addressApi.getAll();
            if (res.data?.data) {
                setAddresses(res.data.data);
            }
        } catch (error) {
            console.error('Failed to load addresses:', error);
            toast.error('Adresler yüklenirken hata oluştu');
        } finally {
            setLoading(false);
        }
    };

    const handleOpenDialog = (address?: Address) => {
        if (address) {
            setEditingAddress(address);
            setFormData({
                title: address.title,
                name: address.name,
                phone: address.phone,
                address: address.address,
                city: address.city,
                district: address.district,
                city_id: address.city_id,
                district_id: address.district_id,
                postal_code: address.postal_code || '',
                is_default: address.is_default,
            });
        } else {
            setEditingAddress(null);
            setFormData({
                title: '',
                name: '',
                phone: '',
                address: '',
                city: '',
                district: '',
                postal_code: '',
                is_default: false,
            });
        }
        setIsDialogOpen(true);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);
        try {
            if (editingAddress) {
                await addressApi.update(editingAddress.id, formData);
                toast.success('Adres güncellendi');
            } else {
                await addressApi.create(formData);
                toast.success('Adres eklendi');
            }
            setIsDialogOpen(false);
            loadAddresses();
        } catch (error) {
            console.error('Failed to save address:', error);
            toast.error('Adres kaydedilirken hata oluştu');
        } finally {
            setIsSaving(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm('Bu adresi silmek istediğinize emin misiniz?')) return;
        try {
            await addressApi.delete(id);
            toast.success('Adres silindi');
            loadAddresses();
        } catch (error) {
            console.error('Failed to delete address:', error);
            toast.error('Adres silinirken hata oluştu');
        }
    };

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-48 w-full" />
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Verified Registered Address Card */}
            {user?.address && user?.city && (
                <div className="bg-slate-50 rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 mb-3">
                        <Lock className="w-4 h-4 text-slate-400" />
                        <h4 className="font-semibold text-slate-700 text-sm">Vergi No Kayıtlı Adres</h4>
                    </div>
                    <div className="flex items-start gap-3">
                        <MapPin className="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" />
                        <div>
                            <p className="text-sm text-slate-700">{user.business_name}</p>
                            <p className="text-sm text-slate-600">{user.address}</p>
                            <p className="text-sm text-slate-600">{user.city}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="flex items-center justify-between">
                <h3 className="text-lg font-bold text-slate-900">Adreslerim</h3>
                <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                    <DialogTrigger asChild>
                        <Button onClick={() => handleOpenDialog()}>
                            <Plus className="w-4 h-4 mr-2" />
                            Yeni Adres Ekle
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>{editingAddress ? 'Adresi Düzenle' : 'Yeni Adres Ekle'}</DialogTitle>
                            <DialogDescription>Teslimat adres bilgilerinizi giriniz.</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">Adres Başlığı (Örn: Merkez, Depo)</Label>
                                <Input
                                    id="title"
                                    value={formData.title}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                    required
                                    placeholder="Merkez"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Ad Soyad</Label>
                                    <Input
                                        id="name"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        required
                                        placeholder="Ad Soyad"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Telefon</Label>
                                    <Input
                                        id="phone"
                                        value={formData.phone}
                                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        required
                                        placeholder="05..."
                                    />
                                </div>
                            </div>
                            <CityDistrictSelect
                                required
                                value={{
                                    cityId: formData.city_id,
                                    cityName: formData.city,
                                    districtId: formData.district_id,
                                    districtName: formData.district,
                                }}
                                onChange={(v) => setFormData({
                                    ...formData,
                                    city: v.cityName ?? '',
                                    district: v.districtName ?? '',
                                    city_id: v.cityId,
                                    district_id: v.districtId,
                                })}
                            />
                            <div className="space-y-2">
                                <Label htmlFor="address">Açık Adres</Label>
                                <Textarea
                                    id="address"
                                    value={formData.address}
                                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                                    required
                                    placeholder="Mahalle, sokak, no..."
                                />
                            </div>
                            <div className="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="is_default"
                                    checked={formData.is_default}
                                    onChange={(e) => setFormData({ ...formData, is_default: e.target.checked })}
                                    className="rounded border-slate-300"
                                />
                                <Label htmlFor="is_default">Varsayılan adres olarak işaretle</Label>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)} disabled={isSaving}>
                                    İptal
                                </Button>
                                <Button type="submit" disabled={isSaving}>
                                    {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Kaydet
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {addresses.length === 0 ? (
                <div className="text-center py-12 bg-slate-50 rounded-xl border-2 border-dashed border-slate-200">
                    <MapPin className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                    <p className="text-slate-500 mb-4">Kayıtlı adres bulunmuyor</p>
                    <Button variant="outline" onClick={() => handleOpenDialog()}>
                        İlk Adresinizi Ekleyin
                    </Button>
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {addresses.map((address) => (
                        <div key={address.id} className={cn(
                            "bg-white rounded-xl border p-4",
                            address.is_default && "border-[#fbeede] shadow-sm"
                        )}>
                            {address.is_default && (
                                <Badge className="bg-[#fbeede] text-[#b8651a] mb-2">Varsayılan</Badge>
                            )}
                            <div className="flex items-start gap-3">
                                <MapPin className="w-5 h-5 text-slate-400 mt-1 flex-shrink-0" />
                                <div className="flex-1">
                                    <h4 className="font-semibold text-slate-900">{address.title}</h4>
                                    <p className="text-sm text-slate-700 mt-1">{address.name}</p>
                                    <p className="text-sm text-slate-600">{address.address}</p>
                                    <p className="text-sm text-slate-600">{address.district}/{address.city}</p>
                                    <p className="text-sm text-slate-500 mt-1">{address.phone}</p>
                                </div>
                            </div>
                            <div className="flex gap-2 mt-4 pt-4 border-t">
                                <Button variant="outline" size="sm" className="flex-1" onClick={() => handleOpenDialog(address)}>
                                    <Edit className="w-3 h-3 mr-2" />
                                    Düzenle
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                    onClick={() => handleDelete(address.id)}
                                >
                                    <Trash2 className="w-3 h-3" />
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

// Security Settings Content
function SecuritySettingsContent() {
    const { logout } = useAuth();
    const router = useRouter();

    // Password change state
    const [isPasswordDialogOpen, setIsPasswordDialogOpen] = useState(false);
    const [passwordForm, setPasswordForm] = useState({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    });
    const [isPasswordSubmitting, setIsPasswordSubmitting] = useState(false);

    // Account deactivation state
    const [isDeactivateDialogOpen, setIsDeactivateDialogOpen] = useState(false);
    const [deactivateForm, setDeactivateForm] = useState({
        password: '',
        reason: '',
    });
    const [isDeactivateSubmitting, setIsDeactivateSubmitting] = useState(false);

    const handlePasswordChange = async () => {
        if (!passwordForm.current_password || !passwordForm.new_password || !passwordForm.new_password_confirmation) {
            toast.error('Tüm alanlar zorunludur');
            return;
        }

        if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
            toast.error('Yeni şifreler eşleşmiyor');
            return;
        }

        if (passwordForm.new_password.length < 8) {
            toast.error('Yeni şifre en az 8 karakter olmalıdır');
            return;
        }

        setIsPasswordSubmitting(true);
        try {
            const res = await api.post<{ success: boolean; message: string }>('/auth/change-password', passwordForm);
            if (res.data?.success) {
                toast.success('Şifreniz başarıyla değiştirildi');
                setIsPasswordDialogOpen(false);
                setPasswordForm({ current_password: '', new_password: '', new_password_confirmation: '' });
            } else {
                toast.error(res.data?.message || res.error || 'Şifre değiştirilemedi');
            }
        } catch (error) {
            toast.error('Bir hata oluştu');
        } finally {
            setIsPasswordSubmitting(false);
        }
    };

    const handleDeactivateAccount = async () => {
        if (!deactivateForm.password) {
            toast.error('Şifrenizi girin');
            return;
        }

        setIsDeactivateSubmitting(true);
        try {
            const res = await api.post<{ success: boolean; message: string }>('/auth/deactivate-account', deactivateForm);
            if (res.data?.success) {
                toast.success('Hesabınız devre dışı birakildi');
                logout();
                router.push('/');
            } else {
                toast.error(res.data?.message || res.error || 'Hesap kapatma başarısız');
            }
        } catch (error) {
            toast.error('Bir hata oluştu');
        } finally {
            setIsDeactivateSubmitting(false);
        }
    };

    return (
        <div className="bg-white rounded-xl border border-slate-200 p-6">
            <h3 className="text-lg font-bold text-slate-900 mb-6">Güvenlik Ayarları</h3>
            <div className="space-y-4">
                {/* Password Change */}
                <Dialog open={isPasswordDialogOpen} onOpenChange={setIsPasswordDialogOpen}>
                    <DialogTrigger asChild>
                        <Button variant="outline" className="w-full justify-start">
                            Şifre Değiştir
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Şifre Değiştir</DialogTitle>
                            <DialogDescription>
                                Hesabınızın güvenliğini sağlamak için şifrenizi düzenli olarak değiştirin.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>Mevcut Şifre</Label>
                                <Input
                                    type="password"
                                    value={passwordForm.current_password}
                                    onChange={(e) => setPasswordForm(prev => ({ ...prev, current_password: e.target.value }))}
                                    placeholder="Mevcut şifrenizi girin"
                                />
                            </div>
                            <div>
                                <Label>Yeni Şifre</Label>
                                <Input
                                    type="password"
                                    value={passwordForm.new_password}
                                    onChange={(e) => setPasswordForm(prev => ({ ...prev, new_password: e.target.value }))}
                                    placeholder="Yeni şifrenizi girin (en az 8 karakter)"
                                />
                            </div>
                            <div>
                                <Label>Yeni Şifre (Tekrar)</Label>
                                <Input
                                    type="password"
                                    value={passwordForm.new_password_confirmation}
                                    onChange={(e) => setPasswordForm(prev => ({ ...prev, new_password_confirmation: e.target.value }))}
                                    placeholder="Yeni şifrenizi tekrar girin"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsPasswordDialogOpen(false)}>
                                İptal
                            </Button>
                            <Button
                                onClick={handlePasswordChange}
                                disabled={isPasswordSubmitting}
                            >
                                {isPasswordSubmitting ? (
                                    <>
                                        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                        Değiştiriliyor...
                                    </>
                                ) : (
                                    'Şifreyi Değiştir'
                                )}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* 2FA - Coming Soon */}
                <div className="flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-slate-50">
                    <div className="flex items-center gap-3">
                        <span className="text-slate-600">İki Faktörlü Doğrulama</span>
                    </div>
                    <Badge variant="secondary" className="bg-amber-100 text-amber-800">
                        Yakinda
                    </Badge>
                </div>

                {/* Account Deactivation */}
                <Dialog open={isDeactivateDialogOpen} onOpenChange={setIsDeactivateDialogOpen}>
                    <DialogTrigger asChild>
                        <Button variant="outline" className="w-full justify-start text-red-600 hover:text-red-700 hover:bg-red-50">
                            Hesabı Kapat
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-red-600">Hesabı Kapat</DialogTitle>
                            <DialogDescription>
                                Bu işlem geri alınamaz. Hesabınız devre dışı bırakılacak ve tüm oturumlarınız sonlandırılacaktır.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div className="flex items-start gap-3">
                                    <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                    <div className="text-sm text-red-800">
                                        <p className="font-medium mb-1">Dikkat!</p>
                                        <ul className="list-disc list-inside space-y-1">
                                            <li>Aktif siparişleriniz iptal edilecektir</li>
                                            <li>Cüzdan bakiyeniz yatırılmış banka hesabiniza aktarilacaktir</li>
                                            <li>İlanlarınız kaldırılacaktır</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <Label>Şifreniz</Label>
                                <Input
                                    type="password"
                                    value={deactivateForm.password}
                                    onChange={(e) => setDeactivateForm(prev => ({ ...prev, password: e.target.value }))}
                                    placeholder="Hesabınızı kapatmak için şifrenizi girin"
                                />
                            </div>
                            <div>
                                <Label>Kapatma Sebebi (İsteğe Bağlı)</Label>
                                <Textarea
                                    value={deactivateForm.reason}
                                    onChange={(e) => setDeactivateForm(prev => ({ ...prev, reason: e.target.value }))}
                                    placeholder="Neden hesabinizi kapatmak istiyorsunuz?"
                                    rows={3}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsDeactivateDialogOpen(false)}>
                                Vazgec
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleDeactivateAccount}
                                disabled={isDeactivateSubmitting}
                            >
                                {isDeactivateSubmitting ? (
                                    <>
                                        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                        İşlem Yapılıyor...
                                    </>
                                ) : (
                                    'Hesabı Kalıcı Olarak Kapat'
                                )}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}

// Settings Content
// Kullanıcı Bilgilerim Content
function KullaniciBilgileriContent({ user }: { user: import('@/lib/api').User | null }) {
    const { setUser } = useAuth();
    const [profileForm, setProfileForm] = useState<{
        nickname: string;
        phone: string;
        address: string;
        city: string;
        district: string;
        city_id?: number;
        district_id?: number;
        trade_name: string;
        kep_address: string;
        mersis_no: string;
        tax_number: string;
        tax_office: string;
    }>({
        nickname: user?.nickname || '',
        phone: user?.phone || '',
        address: user?.address || '',
        city: user?.city || '',
        district: user?.district || '',
        city_id: user?.city_id,
        district_id: user?.district_id,
        trade_name: user?.trade_name || '',
        kep_address: user?.kep_address || '',
        mersis_no: user?.mersis_no || '',
        tax_number: user?.tax_number || '',
        tax_office: user?.tax_office || '',
    });
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        if (user) {
            setProfileForm({
                nickname: user.nickname || '',
                phone: user.phone || '',
                address: user.address || '',
                city: user.city || '',
                district: user.district || '',
                city_id: user.city_id,
                district_id: user.district_id,
                trade_name: user.trade_name || '',
                kep_address: user.kep_address || '',
                mersis_no: user.mersis_no || '',
                tax_number: user.tax_number || '',
                tax_office: user.tax_office || '',
            });
        }
    }, [user]);

    const isRetailerWithVergiNo = user?.role === 'retailer' && !!user?.vergi_no;

    // Ticari bilgiler: once filled, they become readonly
    const isTradeNameLocked = !!user?.trade_name;
    const isTaxNumberLocked = !!user?.tax_number;
    const isTaxOfficeLocked = !!user?.tax_office;
    const isMersisNoLocked = !!user?.mersis_no;
    const isKepAddressLocked = !!user?.kep_address;
    const hasAllTradeInfo = isTradeNameLocked && isTaxNumberLocked && isTaxOfficeLocked;
    const handleUpdateProfile = async () => {
        setIsSaving(true);
        try {
            // Build payload: exclude locked fields
            const payload: Record<string, string | number | undefined> = {
                nickname: profileForm.nickname,
                phone: profileForm.phone,
            };
            // Non-retailer: tüm adres alanları editable
            // Verified user: address/city/city_id locked, district + district_id editable (kargo için)
            if (!isRetailerWithVergiNo) {
                payload.address = profileForm.address;
                payload.city = profileForm.city;
                payload.district = profileForm.district;
                payload.city_id = profileForm.city_id;
                payload.district_id = profileForm.district_id;
            } else {
                payload.district = profileForm.district;
                payload.district_id = profileForm.district_id;
            }
            // Trade fields: only send unlocked ones
            if (!isTradeNameLocked) payload.trade_name = profileForm.trade_name;
            if (!isTaxNumberLocked) payload.tax_number = profileForm.tax_number;
            if (!isTaxOfficeLocked) payload.tax_office = profileForm.tax_office;
            if (!isMersisNoLocked) payload.mersis_no = profileForm.mersis_no;
            if (!isKepAddressLocked) payload.kep_address = profileForm.kep_address;

            const res = await authApi.updateProfile(payload);
            if (res.data?.success) {
                toast.success(res.data.message || 'Profil bilgileriniz güncellendi.');
                if (res.data.user) {
                    setUser(res.data.user);
                }
            } else {
                toast.error(res.error || 'Bir hata oluştu');
            }
        } catch (error) {
            toast.error('Profil güncellenirken hata oluştu');
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-xl border border-slate-200 p-6">
                <h3 className="text-lg font-bold text-slate-900 mb-6">Kullanıcı Bilgilerim</h3>

                {/* Verified-locked fields */}
                <div className="mb-6">
                    <p className="text-sm text-slate-500 mb-4 flex items-center gap-2">
                        <Lock className="w-4 h-4" />
                        Aşağıdaki alanlar sistem tarafından belirlenmiştir ve değiştirilemez.
                    </p>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">Firma Adı</label>
                            <div className="relative">
                                <Input value={user?.business_name || ''} readOnly className="bg-slate-50 pr-8" />
                                <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>
                        {isRetailerWithVergiNo && (
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">Vergi numarası</label>
                                <div className="relative">
                                    <Input value={user?.vergi_no || ''} readOnly className="bg-slate-50 font-mono pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            </div>
                        )}
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">E-posta</label>
                            <div className="relative">
                                <Input value={user?.email || ''} readOnly className="bg-slate-50 pr-8" />
                                <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>
                        {/* Address fields locked for verified users */}
                        {isRetailerWithVergiNo && (
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-slate-700 mb-2">Adres (kayıtlı)</label>
                                <div className="relative">
                                    <Input value={user?.address || ''} readOnly className="bg-slate-50 pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Retailer: city locked, district editable */}
                {isRetailerWithVergiNo && (
                    <div className="border-t border-slate-200 pt-6 mt-6">
                        <p className="text-sm text-slate-500 mb-1">Kargo Bilgileri</p>
                        <p className="text-xs text-slate-500 mb-4">İliniz onaylı kayıt ile gelir. <span className="text-slate-700 font-medium">İlçenizi seçmeniz gerekiyor</span> — Yurtici kargo etiketi için zorunludur.</p>
                        <CityDistrictSelect
                            required
                            lockCity
                            value={{
                                cityId: profileForm.city_id,
                                cityName: profileForm.city,
                                districtId: profileForm.district_id,
                                districtName: profileForm.district,
                            }}
                            onChange={(v) => setProfileForm({
                                ...profileForm,
                                district: v.districtName ?? '',
                                district_id: v.districtId,
                            })}
                        />
                    </div>
                )}

                {/* Editable Fields */}
                <div className="border-t border-slate-200 pt-6">
                    <p className="text-sm text-slate-500 mb-4">Aşağıdaki bilgilerinizi güncelleyebilirsiniz.</p>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">Takma Ad / Gösterim Adı</label>
                            <Input
                                value={profileForm.nickname}
                                onChange={(e) => setProfileForm({ ...profileForm, nickname: e.target.value })}
                                placeholder="Sitede görünecek isim"
                                maxLength={100}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">Telefon</label>
                            <Input
                                value={profileForm.phone}
                                onChange={(e) => setProfileForm({ ...profileForm, phone: e.target.value })}
                                placeholder="05XX XXX XX XX"
                                maxLength={20}
                            />
                        </div>
                        {/* Address fields editable only for non-retailer (seller) users */}
                        {!isRetailerWithVergiNo && (
                            <>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-slate-700 mb-2">Adres</label>
                                    <Input
                                        value={profileForm.address}
                                        onChange={(e) => setProfileForm({ ...profileForm, address: e.target.value })}
                                        placeholder="Açık adres (Mahalle, Cadde/Sokak, No)"
                                        maxLength={500}
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <CityDistrictSelect
                                        required
                                        value={{
                                            cityId: profileForm.city_id,
                                            cityName: profileForm.city,
                                            districtId: profileForm.district_id,
                                            districtName: profileForm.district,
                                        }}
                                        onChange={(v) => setProfileForm({
                                            ...profileForm,
                                            city: v.cityName ?? '',
                                            district: v.districtName ?? '',
                                            city_id: v.cityId,
                                            district_id: v.districtId,
                                        })}
                                    />
                                    <p className="text-xs text-slate-500 mt-2">Kargo etiketi bu il/ilçe bilgisi ile oluşturulur. Yurtici Kargo için zorunludur.</p>
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* Ticari Bilgiler */}
                <div className="border-t border-slate-200 pt-6 mt-6">
                    <h3 className="text-lg font-semibold text-slate-900 mb-1">Ticari Bilgiler</h3>
                    <p className="text-sm text-slate-500 mb-4">Bu bilgiler satış sözleşmelerinde kullanılacaktır.</p>
                    {hasAllTradeInfo && (
                        <div className="flex items-start gap-2 mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <Info className="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                            <p className="text-sm text-amber-700">
                                Ticari bilgiler bir kez girildikten sonra değiştirilemez. Değişiklik için lütfen destek ile iletişime geçin.
                            </p>
                        </div>
                    )}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-slate-700 mb-2">Ticari Ünvan</label>
                            {isTradeNameLocked ? (
                                <div className="relative">
                                    <Input value={user?.trade_name || ''} readOnly className="bg-slate-50 pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            ) : (
                                <Input
                                    value={profileForm.trade_name}
                                    onChange={(e) => setProfileForm({ ...profileForm, trade_name: e.target.value })}
                                    placeholder="Örnek Kırtasiye Ltd. Şti."
                                    maxLength={255}
                                />
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">Vergi No</label>
                            {isTaxNumberLocked ? (
                                <div className="relative">
                                    <Input value={user?.tax_number || ''} readOnly className="bg-slate-50 pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            ) : (
                                <Input
                                    value={profileForm.tax_number}
                                    onChange={(e) => setProfileForm({ ...profileForm, tax_number: e.target.value })}
                                    placeholder="1234567890"
                                    maxLength={20}
                                />
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">Vergi Dairesi</label>
                            {isTaxOfficeLocked ? (
                                <div className="relative">
                                    <Input value={user?.tax_office || ''} readOnly className="bg-slate-50 pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            ) : (
                                <Input
                                    value={profileForm.tax_office}
                                    onChange={(e) => setProfileForm({ ...profileForm, tax_office: e.target.value })}
                                    placeholder="Kadıköy"
                                    maxLength={100}
                                />
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">MERSIS No</label>
                            {isMersisNoLocked ? (
                                <div className="relative">
                                    <Input value={user?.mersis_no || ''} readOnly className="bg-slate-50 pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            ) : (
                                <Input
                                    value={profileForm.mersis_no}
                                    onChange={(e) => setProfileForm({ ...profileForm, mersis_no: e.target.value })}
                                    placeholder="0123456789012345"
                                    maxLength={20}
                                />
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">KEP Adresi</label>
                            {isKepAddressLocked ? (
                                <div className="relative">
                                    <Input value={user?.kep_address || ''} readOnly className="bg-slate-50 pr-8" />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                </div>
                            ) : (
                                <Input
                                    value={profileForm.kep_address}
                                    onChange={(e) => setProfileForm({ ...profileForm, kep_address: e.target.value })}
                                    placeholder="ornek@hs01.kep.tr"
                                    maxLength={255}
                                />
                            )}
                        </div>
                    </div>
                </div>

                <div className="mt-6 pt-6 border-t border-slate-200">
                    <Button onClick={handleUpdateProfile} disabled={isSaving}>
                        {isSaving && <Loader2 className="w-4 h-4 animate-spin mr-2" />}
                        Bilgileri Güncelle
                    </Button>
                </div>
            </div>

            {/* Info Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 mb-2">
                        <User className="w-4 h-4 text-blue-500" />
                        <p className="text-sm font-medium text-slate-700">Rol</p>
                    </div>
                    <p className="text-slate-900 font-semibold capitalize">
                        {user?.role === 'retailer' ? 'Kırtasiyeci' : user?.role === 'seller' ? 'Tedarikçi' : user?.role === 'super-admin' ? 'Yönetici' : user?.role || '-'}
                    </p>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 mb-2">
                        <Calendar className="w-4 h-4 text-[#b8651a]" />
                        <p className="text-sm font-medium text-slate-700">Kayıt Tarihi</p>
                    </div>
                    <p className="text-slate-900 font-semibold">
                        {user?.created_at ? new Date(user.created_at).toLocaleDateString('tr-TR') : '-'}
                    </p>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 mb-2">
                        <CheckCircle2 className="w-4 h-4 text-[#b8651a]" />
                        <p className="text-sm font-medium text-slate-700">Doğrulama Durumu</p>
                    </div>
                    <p className={cn("font-semibold", user?.is_verified ? 'text-[#b8651a]' : 'text-amber-600')}>
                        {user?.is_verified ? 'Doğrulanmış' : 'Doğrulanmamış'}
                    </p>
                </div>
            </div>
        </div>
    );
}

// IBAN helpers
function formatIban(raw: string): string {
    const cleaned = raw.replace(/\s/g, '').toUpperCase();
    // Group into: TR00 0000 0000 0000 0000 0000 00
    return cleaned.replace(/(.{4})(?=.)/g, '$1 ').trim();
}

function cleanIban(value: string): string {
    return value.replace(/\s/g, '').toUpperCase();
}

function validateIban(raw: string): string | null {
    const cleaned = cleanIban(raw);
    if (!cleaned) return 'IBAN gereklidir';
    if (!cleaned.startsWith('TR')) return 'IBAN "TR" ile başlamalıdır';
    if (!/^TR\d{24}$/.test(cleaned)) {
        if (cleaned.length < 26) return `IBAN 26 karakter olmalıdır (${cleaned.length}/26)`;
        if (cleaned.length > 26) return `IBAN 26 karakter olmalıdır (${cleaned.length}/26)`;
        return 'IBAN sadece "TR" ve ardindan 24 rakam içermelidir';
    }
    return null;
}

// Satış Bilgilerim Content
const EMPTY_BANK_FORM = {
    bank_name: '', iban: '', account_holder: '',
    tax_id: '', tax_office: '', kep_address: '', mersis_number: '', phone: '',
};

function BankAccountInlineForm({
    account,
    onSave,
    onCancel,
    onDelete,
}: {
    account?: BankAccount;
    onSave: () => void;
    onCancel?: () => void;
    onDelete?: (id: number) => void;
}) {
    const [form, setForm] = useState({
        bank_name: account?.bank_name || '',
        iban: account?.iban ? formatIban(account.iban) : '',
        account_holder: account?.account_holder || '',
        tax_id: account?.tax_id || '',
        tax_office: account?.tax_office || '',
        kep_address: account?.kep_address || '',
        mersis_number: account?.mersis_number || '',
        phone: account?.phone || '',
    });
    const [ibanError, setIbanError] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isDirty, setIsDirty] = useState(!account);

    const updateField = (field: string, value: string) => {
        setForm(prev => ({ ...prev, [field]: value }));
        setIsDirty(true);
    };

    const handleIbanChange = (value: string) => {
        const upper = value.toUpperCase();
        const allowed = upper.replace(/[^A-Z0-9\s]/g, '');
        const cleaned = cleanIban(allowed);
        const limited = cleaned.slice(0, 26);
        const formatted = formatIban(limited);
        setForm(prev => ({ ...prev, iban: formatted }));
        setIsDirty(true);
        if (ibanError) setIbanError(null);
    };

    const handleSave = async () => {
        if (!form.account_holder || !form.iban) {
            toast.error('Şirket adı ve IBAN zorunludur');
            return;
        }
        const ibanErr = validateIban(form.iban);
        if (ibanErr) {
            setIbanError(ibanErr);
            return;
        }
        if (form.tax_id && !/^\d{10,11}$/.test(form.tax_id.replace(/\s/g, ''))) {
            toast.error('Vergi kimlik no 10 veya 11 haneli olmalıdır');
            return;
        }
        setIsSubmitting(true);
        const submitData = {
            ...form,
            iban: cleanIban(form.iban),
            bank_name: form.bank_name || form.account_holder,
        };
        try {
            const res = account
                ? await walletApi.updateBankAccount(account.id, submitData)
                : await walletApi.addBankAccount(submitData);
            if (res.data?.success) {
                toast.success(account ? 'Bilgiler güncellendi' : 'Banka hesap bilgileri eklendi');
                setIsDirty(false);
                onSave();
            } else {
                toast.error(res.data?.error || res.error || 'Bir hata oluştu');
            }
        } catch {
            toast.error('Kaydetme sırasında hata oluştu');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className={cn(
            "bg-white rounded-xl border p-5 space-y-4",
            account?.is_default ? "border-[#fbeede]" : "border-slate-200",
            !account && "border-dashed border-[#fbeede] bg-[#fbeede]/30"
        )}>
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CreditCard className="w-5 h-5 text-slate-400" />
                    <h4 className="font-semibold text-slate-900">
                        {account ? (account.account_holder || 'Hesap Bilgileri') : 'Yeni Hesap Bilgileri'}
                    </h4>
                    {account?.is_default && <Badge className="bg-[#fbeede] text-[#b8651a] border-[#fbeede]">Varsayılan</Badge>}
                </div>
                {account && onDelete && (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-red-500 hover:text-red-600 hover:bg-red-50"
                        onClick={() => onDelete(account.id)}
                    >
                        <Trash2 className="w-4 h-4 mr-1" />
                        Sil
                    </Button>
                )}
            </div>

            {/* Form Fields */}
            <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium text-slate-600">Şirket Adı <span className="text-red-500">*</span></Label>
                        <Input
                            value={form.account_holder}
                            onChange={(e) => updateField('account_holder', e.target.value)}
                            placeholder="Örn: Istanbul Vitamin Kozmetik Tic. Ltd. Sti."
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium text-slate-600">Banka Adi</Label>
                        <Input
                            value={form.bank_name}
                            onChange={(e) => updateField('bank_name', e.target.value)}
                            placeholder="Örn: Ziraat Bankasi (opsiyonel)"
                        />
                    </div>
                </div>

                <div className="space-y-1.5">
                    <Label className="text-xs font-medium text-slate-600">Şirkete Ait IBAN <span className="text-red-500">*</span></Label>
                    <Input
                        value={form.iban}
                        onChange={(e) => handleIbanChange(e.target.value)}
                        placeholder="TR00 0000 0000 0000 0000 0000 00"
                        className={cn("font-mono", ibanError && "border-red-500 focus-visible:ring-red-500")}
                    />
                    {ibanError ? (
                        <p className="text-xs text-red-600">{ibanError}</p>
                    ) : (
                        <p className="text-xs text-slate-400">TR + 24 rakam (toplam 26 karakter)</p>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium text-slate-600">Vergi Kimlik No</Label>
                        <Input
                            value={form.tax_id}
                            onChange={(e) => {
                                const val = e.target.value.replace(/\D/g, '').slice(0, 11);
                                updateField('tax_id', val);
                            }}
                            placeholder="10 veya 11 haneli"
                            maxLength={11}
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium text-slate-600">Vergi Dairesi</Label>
                        <Input
                            value={form.tax_office}
                            onChange={(e) => updateField('tax_office', e.target.value)}
                            placeholder="Örn: Goztepe"
                            maxLength={100}
                        />
                    </div>
                </div>

                <div className="space-y-1.5">
                    <Label className="text-xs font-medium text-slate-600">KEP Adresi</Label>
                    <Input
                        value={form.kep_address}
                        onChange={(e) => updateField('kep_address', e.target.value)}
                        placeholder="ornek@hs01.kep.tr"
                        maxLength={255}
                    />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium text-slate-600">MERSIS Numarası</Label>
                        <Input
                            value={form.mersis_number}
                            onChange={(e) => {
                                const val = e.target.value.replace(/[^0-9-]/g, '').slice(0, 20);
                                updateField('mersis_number', val);
                            }}
                            placeholder="0000-0000-0000-0000"
                            className="font-mono"
                            maxLength={20}
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label className="text-xs font-medium text-slate-600">Cep Telefonu</Label>
                        <Input
                            value={form.phone}
                            onChange={(e) => updateField('phone', e.target.value)}
                            placeholder="0 (5XX) XXX XX XX"
                            maxLength={20}
                        />
                    </div>
                </div>
            </div>

            {/* Action Buttons */}
            <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                <div>
                    {account && !account.is_default && (
                        <Button variant="ghost" size="sm" className="text-slate-500" onClick={() => {
                            walletApi.setDefaultBankAccount(account.id).then(res => {
                                if (res.data?.success) {
                                    toast.success('Varsayılan hesap güncellendi');
                                    onSave();
                                }
                            }).catch(() => toast.error('Varsayılan hesap güncellenemedi'));
                        }}>
                            <Star className="w-4 h-4 mr-1" />
                            Varsayılan Yap
                        </Button>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {onCancel && (
                        <Button variant="outline" size="sm" onClick={onCancel}>İptal</Button>
                    )}
                    <Button size="sm" onClick={handleSave} disabled={isSubmitting || !isDirty}>
                        {isSubmitting && <Loader2 className="w-4 h-4 animate-spin mr-1" />}
                        {account ? 'Güncelle' : 'Kaydet'}
                    </Button>
                </div>
            </div>
        </div>
    );
}

function SatisBilgileriContent({ user }: { user: import('@/lib/api').User | null }) {
    const [bankAccounts, setBankAccounts] = useState<BankAccount[]>([]);
    const [loading, setLoading] = useState(true);
    const [showNewForm, setShowNewForm] = useState(false);
    const [feeInfo, setFeeInfo] = useState<FeeInfo | null>(null);

    useEffect(() => {
        loadBankAccounts();
        loadFeeInfo();
    }, []);

    const loadFeeInfo = async () => {
        try {
            const res = await platformApi.getFeeInfo();
            if (res.data) {
                setFeeInfo(res.data);
            }
        } catch (error) {
            console.error('Failed to load fee info:', error);
        }
    };

    const loadBankAccounts = async () => {
        setLoading(true);
        try {
            const res = await walletApi.getBankAccounts();
            if (res.data?.bank_accounts) {
                setBankAccounts(res.data.bank_accounts);
            }
        } catch (error) {
            console.error('Failed to load bank accounts:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteBankAccount = async (id: number) => {
        if (!confirm('Bu kaydi silmek istediğinize emin misiniz?')) return;
        try {
            const res = await walletApi.deleteBankAccount(id);
            if (res.data?.success) {
                toast.success('Kayıt silindi');
                loadBankAccounts();
            } else {
                toast.error(res.data?.error || 'Silinemedi');
            }
        } catch {
            toast.error('Silinirken hata oluştu');
        }
    };

    return (
        <div className="space-y-6">
            {/* Bank & Company Info Section */}
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900">Banka Hesap Bilgilerim</h3>
                        <p className="text-sm text-slate-500 mt-1">Sirket ve banka hesap bilgilerinizi yönetin.</p>
                    </div>
                    {!showNewForm && (
                        <Button onClick={() => setShowNewForm(true)}>
                            <Plus className="w-4 h-4 mr-2" />
                            Yeni Ekle
                        </Button>
                    )}
                </div>

                {loading ? (
                    <div className="flex items-center justify-center py-8">
                        <Loader2 className="w-6 h-6 animate-spin text-[#b8651a]" />
                    </div>
                ) : (
                    <div className="space-y-4">
                        {/* New account form */}
                        {showNewForm && (
                            <BankAccountInlineForm
                                onSave={() => { setShowNewForm(false); loadBankAccounts(); }}
                                onCancel={() => setShowNewForm(false)}
                            />
                        )}

                        {/* Existing accounts */}
                        {bankAccounts.map((account) => (
                            <BankAccountInlineForm
                                key={account.id}
                                account={account}
                                onSave={loadBankAccounts}
                                onDelete={handleDeleteBankAccount}
                            />
                        ))}

                        {bankAccounts.length === 0 && !showNewForm && (
                            <div className="text-center py-8 bg-slate-50 rounded-xl border-2 border-dashed border-slate-200">
                                <CreditCard className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                                <p className="text-slate-500">Kayıtlı banka hesap bilgisi bulunmuyor</p>
                                <p className="text-sm text-slate-400 mt-1">Ödeme almak için banka ve şirket bilgilerinizi ekleyin</p>
                                <Button variant="outline" className="mt-4" onClick={() => setShowNewForm(true)}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Hesap Bilgisi Ekle
                                </Button>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Fee Info Section */}
            <div className="bg-white rounded-xl border border-slate-200 p-6">
                <h3 className="text-lg font-bold text-slate-900 mb-4">Platform Kesinti Bilgileri</h3>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div className="bg-slate-50 rounded-lg p-4 text-center">
                        <span className="text-sm text-slate-500 block">
                            {feeInfo?.fee_mode === 'percentage' ? 'Komisyon Oranı' : feeInfo?.fee_mode === 'category' ? 'Komisyon Tipi' : 'Hizmet Bedeli (Sipariş Başına)'}
                        </span>
                        <span className="text-2xl font-bold text-slate-900 block mt-1">
                            {feeInfo ? (
                                feeInfo.fee_mode === 'percentage' ? `%${feeInfo.commission_percentage}` :
                                feeInfo.fee_mode === 'category' ? 'Kategori Bazli' :
                                `₺${feeInfo.flat_service_fee}`
                            ) : <Skeleton className="h-8 w-20 mx-auto" />}
                        </span>
                    </div>
                    <div className="bg-slate-50 rounded-lg p-4 text-center">
                        <span className="text-sm text-slate-500 block">KDV</span>
                        <span className="text-2xl font-bold text-slate-900 block mt-1">%20</span>
                    </div>
                    <div className="bg-slate-50 rounded-lg p-4 text-center">
                        <span className="text-sm text-slate-500 block">Stopaj</span>
                        <span className="text-2xl font-bold text-slate-900 block mt-1">
                            {feeInfo ? `%${feeInfo.withholding_tax_rate}` : <Skeleton className="h-8 w-16 mx-auto" />}
                        </span>
                    </div>
                </div>
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                    <Info className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                    <p className="text-sm text-amber-800">
                        {feeInfo?.fee_mode === 'percentage'
                            ? `Her sipariş için satış tutarının %${feeInfo.commission_percentage}'i komisyon olarak kesilir. Bu oranlar platform tarafindan belirlenmekte olup değiştirilemez.`
                            : feeInfo?.fee_mode === 'category'
                            ? 'Her ürün kategorisinin kendi komisyon oranı vardır. Komisyon, ürün kategorisine göre otomatik hesaplanır.'
                            : 'Her sipariş için satıcı bazinda sabit hizmet bedeli kesilir. Bu oranlar platform tarafindan belirlenmekte olup değiştirilemez.'}
                    </p>
                </div>
            </div>
        </div>
    );
}

// Bildirim Tercihleri Content
const NOTIFICATION_TYPES = [
    { type: 'order_updates', label: 'Sipariş Bildirimleri', description: 'Sipariş durumu değişiklikleri' },
    { type: 'campaigns', label: 'Kampanya Bildirimleri', description: 'Yeni kampanya ve fırsatlar' },
    { type: 'price_drops', label: 'Fiyat Düşüşleri', description: 'Takip ettiğiniz ürünlerde fiyat düşüşü' },
    { type: 'system', label: 'Sistem Bildirimleri', description: 'Hesap ve güvenlik bildirimleri' },
];

const NOTIFICATION_CHANNELS = [
    { channel: 'email', label: 'E-posta' },
    { channel: 'sms', label: 'SMS' },
];

function BildirimTercihleriContent() {
    const [settings, setSettings] = useState<NotificationSetting[]>([]);
    const [loading, setLoading] = useState(true);
    const [updatingKeys, setUpdatingKeys] = useState<Set<string>>(new Set());

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        setLoading(true);
        try {
            const res = await notificationsApi.getAll();
            if (res.data?.settings) {
                setSettings(res.data.settings);
            }
        } catch (error) {
            console.error('Failed to load notification settings:', error);
        } finally {
            setLoading(false);
        }
    };

    const isEnabled = (type: string, channel: string): boolean => {
        const setting = settings.find(s => s.type === type && s.channel === channel);
        return setting?.is_enabled ?? false;
    };

    const handleToggle = async (type: string, channel: string, enabled: boolean) => {
        const key = `${type}-${channel}`;
        setUpdatingKeys(prev => new Set(prev).add(key));
        try {
            const res = await notificationsApi.update({ channel, type, is_enabled: enabled });
            if (res.data?.setting) {
                setSettings(prev => {
                    const existing = prev.findIndex(s => s.type === type && s.channel === channel);
                    if (existing >= 0) {
                        const updated = [...prev];
                        updated[existing] = res.data!.setting;
                        return updated;
                    }
                    return [...prev, res.data!.setting];
                });
            } else {
                toast.error(res.error || 'Ayar güncellenemedi');
            }
        } catch (error) {
            toast.error('Bildirim ayari güncellenirken hata oluştu');
        } finally {
            setUpdatingKeys(prev => {
                const next = new Set(prev);
                next.delete(key);
                return next;
            });
        }
    };

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-48 w-full" />
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-slate-200 p-6">
            <div className="mb-6">
                <h3 className="text-lg font-bold text-slate-900">Bildirim Tercihleri</h3>
                <p className="text-sm text-slate-500 mt-1">Hangi bildirimleri almak istediğinizi seçin.</p>
            </div>

            {/* Header row */}
            <div className="hidden sm:grid grid-cols-[1fr_80px_80px] gap-4 pb-3 border-b border-slate-200 mb-2">
                <div />
                {NOTIFICATION_CHANNELS.map(ch => (
                    <div key={ch.channel} className="text-center text-sm font-medium text-slate-500">{ch.label}</div>
                ))}
            </div>

            <div className="space-y-1">
                {NOTIFICATION_TYPES.map((nt) => (
                    <div key={nt.type} className="grid grid-cols-1 sm:grid-cols-[1fr_80px_80px] gap-4 py-3 border-b border-slate-100 last:border-0 items-center">
                        <div>
                            <p className="font-medium text-slate-900">{nt.label}</p>
                            <p className="text-sm text-slate-500">{nt.description}</p>
                        </div>
                        {NOTIFICATION_CHANNELS.map(ch => {
                            const key = `${nt.type}-${ch.channel}`;
                            const isUpdating = updatingKeys.has(key);
                            return (
                                <div key={ch.channel} className="flex items-center justify-between sm:justify-center gap-2">
                                    <span className="sm:hidden text-sm text-slate-500">{ch.label}</span>
                                    {isUpdating ? (
                                        <Loader2 className="w-4 h-4 animate-spin text-slate-400" />
                                    ) : (
                                        <Switch
                                            checked={isEnabled(nt.type, ch.channel)}
                                            onCheckedChange={(checked) => handleToggle(nt.type, ch.channel, checked)}
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                ))}
            </div>
        </div>
    );
}

function SettingsContent({ subNav, user }: { subNav: string; user: import('@/lib/api').User | null }) {
    const [integrations, setIntegrations] = useState<UserIntegration[]>([]);
    const [loadingIntegrations, setLoadingIntegrations] = useState(false);

    useEffect(() => {
        if (subNav === 'erp-entegrasyonlari') {
            fetchIntegrations();
        }
    }, [subNav]);

    const fetchIntegrations = async () => {
        setLoadingIntegrations(true);
        try {
            const response = await integrationsApi.getAll();
            const payload = response.data as Record<string, unknown>;
            if (payload?.data) {
                setIntegrations(payload.data as UserIntegration[]);
            }
        } catch (error) {
            console.error('Entegrasyonlar yüklenirken hata:', error);
        } finally {
            setLoadingIntegrations(false);
        }
    };

    return (
        <div className="space-y-6">
            {subNav === 'kullanici-bilgilerim' && (
                <KullaniciBilgileriContent user={user} />
            )}

            {subNav === 'satis-bilgilerim' && (
                <SatisBilgileriContent user={user} />
            )}

            {subNav === 'adresler' && (
                <AddressesContent user={user} />
            )}

            {subNav === 'kartlarim' && (
                <KartlarimContent subNav={subNav} />
            )}

            {subNav === 'bildirim-tercihleri' && (
                <BildirimTercihleriContent />
            )}

            {subNav === 'guvenlik' && (
                <SecuritySettingsContent />
            )}

            {subNav === 'erp-entegrasyonlari' && (
                <div className="space-y-6">
                    <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                        <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                        <p className="text-sm text-blue-800">
                            Bu sayfadan ERP sistemlerinizin API bilgilerini girebilir ve yonetebilirsiniz.
                        </p>
                    </div>

                    <div>
                        <h3 className="text-lg font-bold text-slate-900 mb-1">ERP Entegrasyonlari</h3>
                        <p className="text-sm text-slate-500">
                            ERP ve muhasebe sistemleri ile entegre olun, ürünlerinizi otomatik senkronize edin.
                        </p>
                    </div>

                    {loadingIntegrations ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {AVAILABLE_INTEGRATIONS.map((erp) => {
                                const integration = integrations.find((i) => i.erp_type === erp.id);
                                return (
                                    <IntegrationCard
                                        key={erp.id}
                                        id={erp.id}
                                        name={erp.name}
                                        description={erp.description}
                                        logo={erp.logo}
                                        integration={integration}
                                        onUpdate={fetchIntegrations}
                                    />
                                );
                            })}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// Tedarikçi İstekleri Content (For Retailers)
function FirmaIstekleriContent({ subNav }: { subNav: string }) {
    const [links, setLinks] = useState<DistributorRetailerLink[]>([]);
    const [loading, setLoading] = useState(true);
    const [pendingCount, setPendingCount] = useState(0);
    const [processingId, setProcessingId] = useState<number | null>(null);

    const fetchLinks = async () => {
        setLoading(true);
        try {
            const status = subNav === 'bekleyen' ? 'pending' : subNav === 'onaylanan' ? 'approved' : subNav === 'reddedilen' ? 'rejected' : undefined;
            const res = await distributorRetailerLinkApi.myReceivedRequests(status);
            if (res.data?.data) {
                setLinks(res.data.data);
            }

            // Get pending count for badge
            const countRes = await distributorRetailerLinkApi.pendingCount();
            if (countRes.data?.count !== undefined) {
                setPendingCount(countRes.data.count);
            }
        } catch (error) {
            console.error('Failed to fetch links:', error);
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchLinks();
    }, [subNav]);

    const handleApprove = async (linkId: number) => {
        setProcessingId(linkId);
        try {
            const res = await distributorRetailerLinkApi.approveRequest(linkId);
            if (res.data) {
                fetchLinks();
            }
        } catch (error) {
            console.error('Failed to approve:', error);
        }
        setProcessingId(null);
    };

    const handleReject = async (linkId: number) => {
        setProcessingId(linkId);
        try {
            const res = await distributorRetailerLinkApi.rejectRequest(linkId);
            if (res.data) {
                fetchLinks();
            }
        } catch (error) {
            console.error('Failed to reject:', error);
        }
        setProcessingId(null);
    };

    const handleRevoke = async (linkId: number) => {
        if (!confirm('Bu bağlantıyı iptal etmek istediğinizden emin misiniz?')) return;
        setProcessingId(linkId);
        try {
            const res = await distributorRetailerLinkApi.revokeLink(linkId);
            if (res.data) {
                fetchLinks();
            }
        } catch (error) {
            console.error('Failed to revoke:', error);
        }
        setProcessingId(null);
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge className="bg-amber-100 text-amber-700 border-amber-200">Bekliyor</Badge>;
            case 'approved':
                return <Badge className="bg-[#fbeede] text-[#b8651a] border-[#fbeede]">Onaylandı</Badge>;
            case 'rejected':
                return <Badge className="bg-red-100 text-red-700 border-red-200">Reddedildi</Badge>;
            default:
                return null;
        }
    };

    return (
        <div className="space-y-6">
            <div className="bg-[#fbeede] border border-[#fbeede] rounded-xl p-4 flex items-start gap-3">
                <Info className="w-5 h-5 text-[#b8651a] flex-shrink-0 mt-0.5" />
                <div>
                    <p className="text-sm text-[#b8651a] font-medium">Tedarikçi Bağlantı İstekleri</p>
                    <p className="text-sm text-[#b8651a] mt-1">
                        Tedarikçiler sizinle bağlantı kurmak için istek gönderebilir.
                        Onayladığınız tedarikçilerden vadeli alışveriş ve özel fiyat avantajı sağlayabilirsiniz.
                    </p>
                </div>
            </div>

            {loading ? (
                <div className="flex items-center justify-center py-12">
                    <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
                </div>
            ) : links.length === 0 ? (
                <div className="text-center py-12 bg-slate-50 rounded-xl">
                    <Users className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                    <p className="text-slate-500">
                        {subNav === 'bekleyen' ? 'Bekleyen istek bulunmuyor' :
                         subNav === 'onaylanan' ? 'Onaylanmış bağlantı bulunmuyor' :
                         'Reddedilen istek bulunmuyor'}
                    </p>
                </div>
            ) : (
                <div className="space-y-4">
                    {links.map((link) => (
                        <div key={link.id} className="bg-white rounded-xl border border-slate-200 p-5">
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex items-start gap-4">
                                    <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center">
                                        <Building2 className="w-6 h-6 text-[#b8651a]" />
                                    </div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{link.distributor?.nickname || link.distributor?.business_name || 'Tedarikçi'}</h4>
                                        <p className="text-sm text-slate-500">{link.distributor?.email}</p>
                                        {link.distributor?.phone && (
                                            <p className="text-sm text-slate-500">{link.distributor.phone}</p>
                                        )}
                                        {link.message && (
                                            <p className="text-sm text-slate-600 mt-2 bg-slate-50 rounded-lg p-3">
                                                "{link.message}"
                                            </p>
                                        )}
                                        <p className="text-xs text-slate-400 mt-2">
                                            {new Date(link.created_at).toLocaleDateString('tr-TR')}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex flex-col items-end gap-2">
                                    {getStatusBadge(link.status)}
                                    {link.status === 'pending' && (
                                        <div className="flex gap-2 mt-2">
                                            <Button
                                                size="sm"
                                                onClick={() => handleApprove(link.id)}
                                                disabled={processingId === link.id}
                                                className="bg-[#b8651a] hover:bg-[#934f12]"
                                            >
                                                {processingId === link.id ? (
                                                    <Loader2 className="w-4 h-4 animate-spin" />
                                                ) : (
                                                    <Check className="w-4 h-4 mr-1" />
                                                )}
                                                Onayla
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleReject(link.id)}
                                                disabled={processingId === link.id}
                                                className="text-red-600 border-red-200 hover:bg-red-50"
                                            >
                                                <X className="w-4 h-4 mr-1" />
                                                Reddet
                                            </Button>
                                        </div>
                                    )}
                                    {link.status === 'approved' && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => handleRevoke(link.id)}
                                            disabled={processingId === link.id}
                                            className="text-red-600 border-red-200 hover:bg-red-50 mt-2"
                                        >
                                            {processingId === link.id ? (
                                                <Loader2 className="w-4 h-4 animate-spin" />
                                            ) : (
                                                <X className="w-4 h-4 mr-1" />
                                            )}
                                            Baglantiyi İptal Et
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

// İstek Yolla Content (For Sellers / Distributors)
function IstekYollaContent({ subNav }: { subNav: string }) {
    const [retailers, setRetailers] = useState<RetailerListItem[]>([]);
    const [myRequests, setMyRequests] = useState<DistributorRetailerLink[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [sendingTo, setSendingTo] = useState<number | null>(null);
    const [requestMessage, setRequestMessage] = useState('');
    const [showMessageModal, setShowMessageModal] = useState<number | null>(null);

    const fetchRetailers = async (search?: string) => {
        setLoading(true);
        try {
            const res = await distributorRetailerLinkApi.listRetailers(search);
            if (res.data?.data) {
                setRetailers(res.data.data);
            }
        } catch (error) {
            console.error('Failed to fetch retailers:', error);
        }
        setLoading(false);
    };

    const fetchMyRequests = async () => {
        setLoading(true);
        try {
            const res = await distributorRetailerLinkApi.mySentRequests();
            if (res.data?.data) {
                setMyRequests(res.data.data);
            }
        } catch (error) {
            console.error('Failed to fetch requests:', error);
        }
        setLoading(false);
    };

    useEffect(() => {
        if (subNav === 'kirtasiye-ara') {
            fetchRetailers();
        } else {
            fetchMyRequests();
        }
    }, [subNav]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        fetchRetailers(searchQuery);
    };

    const handleSendRequest = async (retailerId: number) => {
        setSendingTo(retailerId);
        try {
            const res = await distributorRetailerLinkApi.sendRequest(retailerId, requestMessage);
            if (res.data) {
                setRequestMessage('');
                setShowMessageModal(null);
                fetchRetailers(searchQuery); // Refresh to update status
            }
        } catch (error) {
            console.error('Failed to send request:', error);
        }
        setSendingTo(null);
    };

    const handleCancelRequest = async (linkId: number) => {
        if (!confirm('Bu isteği iptal etmek istediğinizden emin misiniz?')) return;
        setSendingTo(linkId);
        try {
            const res = await distributorRetailerLinkApi.cancelRequest(linkId);
            if (res.data) {
                fetchMyRequests();
            }
        } catch (error) {
            console.error('Failed to cancel:', error);
        }
        setSendingTo(null);
    };

    const getStatusBadge = (status: string | null) => {
        switch (status) {
            case 'pending':
                return <Badge className="bg-amber-100 text-amber-700 border-amber-200">Bekliyor</Badge>;
            case 'approved':
                return <Badge className="bg-[#fbeede] text-[#b8651a] border-[#fbeede]">Onaylandı</Badge>;
            case 'rejected':
                return <Badge className="bg-red-100 text-red-700 border-red-200">Reddedildi</Badge>;
            default:
                return null;
        }
    };

    return (
        <div className="space-y-6">
            <div className="bg-[#fbeede] border border-[#fbeede] rounded-xl p-4 flex items-start gap-3">
                <Info className="w-5 h-5 text-[#b8651a] flex-shrink-0 mt-0.5" />
                <div>
                    <p className="text-sm text-[#b8651a] font-medium">Kırtasiyelerle Bağlantı Kurma</p>
                    <p className="text-sm text-[#b8651a] mt-1">
                        Tedarikçi olarak kırtasiyelere bağlantı isteği gönderebilirsiniz.
                        Onaylanan kırtasiyeler özel fiyatlarınızı görüntüleyebilir ve vadeli alışveriş yapabilir.
                    </p>
                </div>
            </div>

            {subNav === 'kirtasiye-ara' && (
                <>
                    <form onSubmit={handleSearch} className="flex gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <Input
                                type="text"
                                placeholder="Kırtasiye adı, şehir veya vergi no ile ara..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        <Button type="submit" className="bg-[#b8651a] hover:bg-[#934f12]">
                            Ara
                        </Button>
                    </form>

                    {loading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
                        </div>
                    ) : retailers.length === 0 ? (
                        <div className="text-center py-12 bg-slate-50 rounded-xl">
                            <Store className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                            <p className="text-slate-500">Sonuç bulunamadı</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {retailers.map((retailer) => (
                                <div key={retailer.id} className="bg-white rounded-xl border border-slate-200 p-5">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex items-start gap-4">
                                            <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center">
                                                <Store className="w-6 h-6 text-[#b8651a]" />
                                            </div>
                                            <div>
                                                <h4 className="font-semibold text-slate-900">{retailer.nickname || retailer.business_name}</h4>
                                                {retailer.nickname && retailer.business_name && retailer.nickname !== retailer.business_name && (
                                                    <p className="text-xs text-slate-400">{retailer.business_name}</p>
                                                )}
                                                {retailer.city && (
                                                    <p className="text-sm text-slate-500 flex items-center gap-1">
                                                        <MapPin className="w-4 h-4" />
                                                        {retailer.city}
                                                    </p>
                                                )}
                                                {retailer.vergi_no && (
                                                    <p className="text-xs text-slate-400 mt-1">VKN: {retailer.vergi_no}</p>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex flex-col items-end gap-2">
                                            {retailer.link_status ? (
                                                <>
                                                    {getStatusBadge(retailer.link_status)}
                                                    {retailer.link_status === 'approved' && (
                                                        <Link href={`/market/satici/${retailer.id}`}>
                                                            <Button size="sm" className="bg-[#b8651a] hover:bg-[#934f12] mt-2">
                                                                <Store className="w-4 h-4 mr-1" />
                                                                İlanları Gör
                                                            </Button>
                                                        </Link>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {showMessageModal === retailer.id ? (
                                                        <div className="flex flex-col gap-2 w-64">
                                                            <Input
                                                                placeholder="Mesaj (opsiyonel)"
                                                                value={requestMessage}
                                                                onChange={(e) => setRequestMessage(e.target.value)}
                                                                className="text-sm"
                                                            />
                                                            <div className="flex gap-2">
                                                                <Button
                                                                    size="sm"
                                                                    onClick={() => handleSendRequest(retailer.id)}
                                                                    disabled={sendingTo === retailer.id}
                                                                    className="flex-1 bg-[#b8651a] hover:bg-[#934f12]"
                                                                >
                                                                    {sendingTo === retailer.id ? (
                                                                        <Loader2 className="w-4 h-4 animate-spin" />
                                                                    ) : (
                                                                        'Gönder'
                                                                    )}
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => {
                                                                        setShowMessageModal(null);
                                                                        setRequestMessage('');
                                                                    }}
                                                                >
                                                                    Vazgec
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <Button
                                                            size="sm"
                                                            onClick={() => setShowMessageModal(retailer.id)}
                                                            className="bg-[#b8651a] hover:bg-[#934f12]"
                                                        >
                                                            <Send className="w-4 h-4 mr-1" />
                                                            İstek Gönder
                                                        </Button>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </>
            )}

            {subNav === 'gonderilen-istekler' && (
                <>
                    {loading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="w-8 h-8 animate-spin text-[#b8651a]" />
                        </div>
                    ) : myRequests.length === 0 ? (
                        <div className="text-center py-12 bg-slate-50 rounded-xl">
                            <Send className="w-16 h-16 mx-auto text-slate-300 mb-4" />
                            <p className="text-slate-500">Gönderilmiş istek bulunmuyor</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {myRequests.map((link) => (
                                <div key={link.id} className="bg-white rounded-xl border border-slate-200 p-5">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex items-start gap-4">
                                            <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center">
                                                <Store className="w-6 h-6 text-[#b8651a]" />
                                            </div>
                                            <div>
                                                <h4 className="font-semibold text-slate-900">{link.retailer?.nickname || link.retailer?.business_name || 'Kırtasiye'}</h4>
                                                {link.retailer?.city && (
                                                    <p className="text-sm text-slate-500">{link.retailer.city}</p>
                                                )}
                                                {link.message && (
                                                    <p className="text-sm text-slate-600 mt-2 bg-slate-50 rounded-lg p-3">
                                                        "{link.message}"
                                                    </p>
                                                )}
                                                {link.rejection_reason && (
                                                    <p className="text-sm text-red-600 mt-2 bg-red-50 rounded-lg p-3">
                                                        Red sebebi: {link.rejection_reason}
                                                    </p>
                                                )}
                                                <p className="text-xs text-slate-400 mt-2">
                                                    {new Date(link.created_at).toLocaleDateString('tr-TR')}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex flex-col items-end gap-2">
                                            {getStatusBadge(link.status)}
                                            {link.status === 'pending' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleCancelRequest(link.id)}
                                                    disabled={sendingTo === link.id}
                                                    className="text-red-600 border-red-200 hover:bg-red-50 mt-2"
                                                >
                                                    {sendingTo === link.id ? (
                                                        <Loader2 className="w-4 h-4 animate-spin" />
                                                    ) : (
                                                        <X className="w-4 h-4 mr-1" />
                                                    )}
                                                    İptal Et
                                                </Button>
                                            )}
                                            {link.status === 'approved' && (
                                                <Link href={`/market/satici/${link.retailer_id}`}>
                                                    <Button size="sm" className="bg-[#b8651a] hover:bg-[#934f12] mt-2">
                                                        <Store className="w-4 h-4 mr-1" />
                                                        İlanları Gör
                                                    </Button>
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

// Loading Fallback for Suspense
function HesabimLoading() {
    return (
        <div className="min-h-screen">
            <div className="p-8">
                <Skeleton className="h-12 w-64 mb-8" />
                <Skeleton className="h-64 w-full" />
            </div>
        </div>
    );
}

// Main Content Component
function HesabimContent() {
    const { user, isAuthenticated, isLoading, logout } = useAuth();
    const { pendingOrdersCount } = useNotificationStore();
    const router = useRouter();
    const searchParams = useSearchParams();

    // Get dynamic tabs based on user role
    const userTabs = getTabs(user?.role);

    const [activeTab, setActiveTab] = useState('ilanlarim');
    const [activeSubNav, setActiveSubNav] = useState('aktif-ilanlar');
    const [buyerOrders, setBuyerOrders] = useState<Order[]>([]);
    const [sellerOrders, setSellerOrders] = useState<SellerOrder[]>([]);
    const [loadingOrders, setLoadingOrders] = useState(false);
    const [viewingOrderId, setViewingOrderId] = useState<number | null>(null);
    const [viewingOrderIsSeller, setViewingOrderIsSeller] = useState(false);
    const [orderStatusFilter, setOrderStatusFilter] = useState<string>('all');
    const [reviewCount, setReviewCount] = useState<number>(0);
    const [pendingReturnCount, setPendingReturnCount] = useState<number>(0);
    const [randomPosts, setRandomPosts] = useState<BlogPostType[]>([]);
    const [stats, setStats] = useState<SellerStatsResponse['data'] | null>(null);
    const [statsLoading, setStatsLoading] = useState(true);

    // Get tab from URL or default
    useEffect(() => {
        const tab = searchParams.get('tab');
        const sub = searchParams.get('sub');
        if (tab && userTabs.some(t => t.id === tab)) {
            setActiveTab(tab);
            const subNavItems = TAB_SUBNAV[tab];
            if (sub && subNavItems?.some(s => s.id === sub)) {
                setActiveSubNav(sub);
            } else if (subNavItems?.[0]) {
                setActiveSubNav(subNavItems[0].id);
            }
        }
    }, [searchParams, userTabs]);

    // Load orders when on orders tab
    useEffect(() => {
        if (activeTab === 'siparislerim' && isAuthenticated) {
            loadOrders(activeSubNav);
        }
    }, [activeTab, activeSubNav, isAuthenticated]);

    const loadOrders = async (subNav: string) => {
        setLoadingOrders(true);
        try {
            // Always load both buyer and seller orders for the sidebar counts
            const [buyerRes, sellerRes] = await Promise.all([
                ordersApi.getAll({ per_page: 100 }),
                ordersApi.getSellerOrders({ per_page: 100 }),
            ]);
            if (buyerRes.data?.orders) setBuyerOrders(buyerRes.data.orders);
            if (sellerRes.data?.orders) setSellerOrders(sellerRes.data.orders);
        } catch (error) {
            console.error('Failed to load orders:', error);
        }
        setLoadingOrders(false);
    };

    const handleViewOrderDetail = (orderId: number, isSeller: boolean) => {
        setViewingOrderId(orderId);
        setViewingOrderIsSeller(isSeller);
    };

    const handleBackFromOrderDetail = () => {
        setViewingOrderId(null);
    };

    // Auth is handled by market layout - no redirect needed here

    // Load dynamic counts for badges (reviews, returns)
    useEffect(() => {
        const loadBadgeCounts = async () => {
            if (!isAuthenticated) return;

            try {
                // Load review count
                const reviewsRes = await reviewsApi.getSellerReviews({ per_page: 1 });
                if (reviewsRes.data?.pagination?.total !== undefined) {
                    setReviewCount(reviewsRes.data.pagination.total);
                }

                // Load pending return requests count (for sellers)
                const returnsRes = await returnsApi.getSellerRequests();
                if (returnsRes.data?.pending_count !== undefined) {
                    setPendingReturnCount(returnsRes.data.pending_count);
                }
            } catch (error) {
                console.error('Failed to load badge counts:', error);
            }
        };

        const loadRandomPosts = async () => {
            const res = await blogApi.getRandom(5);
            if (res.data?.posts) {
                setRandomPosts(res.data.posts);
            }
        };

        const loadStats = async () => {
            setStatsLoading(true);
            try {
                const res = await sellerApi.getStats();
                if (res.data?.data) {
                    setStats(res.data.data);
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            } finally {
                setStatsLoading(false);
            }
        };

        loadBadgeCounts();
        loadRandomPosts();
        loadStats();
    }, [isAuthenticated]);

    const handleTabChange = (tabId: string) => {
        setActiveTab(tabId);
        setViewingOrderId(null); // Reset order detail view when changing tabs
        const subNavItems = TAB_SUBNAV[tabId];
        if (subNavItems?.[0]) {
            setActiveSubNav(subNavItems[0].id);
        }
        router.push(`/market/hesabim?tab=${tabId}&sub=${subNavItems?.[0]?.id || ''}`, { scroll: false });
    };

    const handleSubNavChange = (subId: string) => {
        setActiveSubNav(subId);
        setViewingOrderId(null); // Reset order detail view when changing sub-nav
        setOrderStatusFilter('all'); // Reset status filter when changing sub-nav
        router.push(`/market/hesabim?tab=${activeTab}&sub=${subId}`, { scroll: false });
    };

    // Get status counts for orders
    const getOrderStatusCounts = (orders: any[]) => {
        const counts = { shipped: 0, delivered: 0, cancelled: 0, pending: 0, confirmed: 0, processing: 0, returned: 0, all: 0, needsShipping: 0, problematic: 0 };
        counts.all = orders.length;
        orders.forEach(order => {
            if (order.status === 'shipped') counts.shipped++;
            else if (order.status === 'delivered') counts.delivered++;
            else if (order.status === 'cancelled') counts.cancelled++;
            else if (order.status === 'confirmed') counts.confirmed++;
            else if (order.status === 'processing') counts.processing++;
            else if (order.status === 'returned') counts.returned++;
            else counts.pending++;
        });
        counts.needsShipping = counts.pending + counts.confirmed + counts.processing;
        counts.problematic = 0;
        return counts;
    };

    // Current orders based on activeSubNav
    const currentOrders = activeSubNav === 'sattiklarim' ? sellerOrders : buyerOrders;
    const statusCounts = getOrderStatusCounts(currentOrders);
    const sellerCounts = getOrderStatusCounts(sellerOrders);
    const buyerCounts = getOrderStatusCounts(buyerOrders);

    // Helper function to get sub-nav items with dynamic counts
    const getSubNavItems = (tabId: string) => {
        const items = TAB_SUBNAV[tabId] || [];
        return items.map(item => {
            // Dynamic count for yorumlarim
            if (item.id === 'yorumlarim') {
                return { ...item, count: reviewCount > 0 ? reviewCount : undefined };
            }
            // Dynamic count for sattiklarim (pending orders for sellers)
            if (item.id === 'sattiklarim') {
                return { ...item, count: pendingOrdersCount > 0 ? pendingOrdersCount : undefined };
            }
            // Dynamic count for iade-talepleri (pending returns for sellers)
            if (item.id === 'iade-talepleri') {
                return { ...item, count: pendingReturnCount > 0 ? pendingReturnCount : undefined };
            }
            return item;
        });
    };

    const currentSubNavItems = getSubNavItems(activeTab);
    const currentTabLabel = userTabs.find(t => t.id === activeTab)?.label || 'Hesabim';

    // Visible tabs with optional badge
    const visibleTabs = userTabs.map(tab => {
        let badge: string | undefined;
        if (tab.id === 'siparislerim' && pendingOrdersCount > 0) {
            badge = String(pendingOrdersCount);
        }
        return { ...tab, badge };
    });

    return (
        <div className="min-h-screen">
            <div className="max-w-[1300px] mx-auto px-4 sm:px-7 py-6">

                {/* Breadcrumb */}
                <div className="text-sm text-[#6b7280] mb-6">
                    <Link href="/market" className="hover:text-[#b8651a]">Ana Sayfa</Link>
                    <span className="mx-2">/</span>
                    <span className="text-[#1a1a1a] font-medium">{currentTabLabel}</span>
                </div>

                {/* Dashboard Header */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-5">
                        <div>
                            <h1 className="text-2xl font-black text-[#1a1a1a]">
                                Hos geldin, {user?.business_name || user?.nickname || 'Kullanici'}
                            </h1>
                            <p className="text-sm text-[#6b7280] mt-1">{user?.email}</p>
                        </div>
                        <div className="w-12 h-12 rounded-2xl bg-[#fbeede] flex items-center justify-center">
                            <User className="w-6 h-6 text-[#b8651a]" />
                        </div>
                    </div>

                    {/* Stat Cards */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        {statsLoading ? (
                            <>
                                {[1, 2, 3, 4].map((i) => (
                                    <div key={i} className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                        <div className="flex items-center gap-2 mb-2">
                                            <Skeleton className="h-4 w-4 rounded" />
                                            <Skeleton className="h-3 w-20" />
                                        </div>
                                        <Skeleton className="h-7 w-24" />
                                    </div>
                                ))}
                            </>
                        ) : (
                            <>
                                <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <TrendingUp className="w-4 h-4 text-[#b8651a]" />
                                        <span className="text-xs font-medium text-[#6b7280]">Toplam Satış</span>
                                    </div>
                                    <p className="text-xl font-black text-[#1a1a1a]">{stats?.total_sales?.formatted || '₺0'}</p>
                                </div>
                                <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <ShoppingBag className="w-4 h-4 text-[#b8651a]" />
                                        <span className="text-xs font-medium text-[#6b7280]">Aktif Sipariş</span>
                                    </div>
                                    <p className="text-xl font-black text-[#1a1a1a]">{stats?.pending_orders?.formatted || '0'}</p>
                                </div>
                                <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <Wallet className="w-4 h-4 text-[#b8651a]" />
                                        <span className="text-xs font-medium text-[#6b7280]">Cuzdan</span>
                                    </div>
                                    <p className="text-xl font-black text-[#1a1a1a]">{stats?.wallet_balance?.formatted || '₺0'}</p>
                                </div>
                                <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <Star className="w-4 h-4 text-[#b8651a]" />
                                        <span className="text-xs font-medium text-[#6b7280]">Aktif Ilan</span>
                                    </div>
                                    <p className="text-xl font-black text-[#1a1a1a]">{stats?.active_offers?.formatted || '0'}</p>
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* Unified Card: Tab Bar + Sidebar + Content */}
                <div className="bg-white rounded-2xl border border-[#f0eceb] overflow-hidden">

                    {/* TOP: Horizontal Tab Bar */}
                    <div className="overflow-x-auto scrollbar-hide border-b border-[#f0eceb]">
                        <div className="flex gap-1 min-w-max px-2">
                            {visibleTabs.map((tab) => {
                                const Icon = tab.icon;
                                return (
                                    <button
                                        key={tab.id}
                                        onClick={() => handleTabChange(tab.id)}
                                        className={cn(
                                            'px-4 py-3.5 text-[15px] font-medium whitespace-nowrap border-b-2 transition-colors flex items-center gap-2',
                                            activeTab === tab.id
                                                ? 'text-[#b8651a] border-[#b8651a] font-bold'
                                                : 'text-[#6b7280] border-transparent hover:text-[#1a1a1a]'
                                        )}
                                    >
                                        <Icon className="w-4 h-4" />
                                        {tab.label}
                                        {tab.badge && (
                                            <span className="bg-[#b8651a] text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">
                                                {tab.badge}
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* BOTTOM: Sidebar + Content */}
                    <div className="flex items-start">

                    {/* LEFT SIDEBAR — sub-tabs only (desktop) */}
                    {currentSubNavItems.length > 1 && !viewingOrderId && (
                        <aside className="hidden md:flex flex-col gap-0.5 w-72 flex-shrink-0 sticky top-4 border-r border-[#f0eceb] p-4 self-stretch">
                            {activeTab === 'siparislerim' ? (
                                <div className="space-y-1">
                                    {/* Sattıklarım group */}
                                    <div className="flex items-center justify-between px-1 pb-1.5 pt-0.5">
                                        <span className="text-[17px] font-black text-[#1a1a1a]">Sattıklarım</span>
                                        <button
                                            onClick={() => { handleSubNavChange('sattiklarim'); setOrderStatusFilter('all'); }}
                                            className="text-[13px] text-[#b8651a] hover:underline font-medium"
                                        >
                                            Tümünü Gör
                                        </button>
                                    </div>
                                    {[
                                        { label: 'Kargolanacaklar', filter: 'active', count: sellerCounts.needsShipping, icon: '📦' },
                                        { label: 'Kargodakiler',    filter: 'shipped', count: sellerCounts.shipped, icon: '🚚' },
                                        { label: 'Tamamlananlar',   filter: 'delivered', count: sellerCounts.delivered, icon: '👍' },
                                        { label: 'İptal / İade',    filter: 'cancelled_returned', count: sellerCounts.cancelled + sellerCounts.returned, icon: '↩️' },
                                    ].map((item) => {
                                        const isActive = activeSubNav === 'sattiklarim' && orderStatusFilter === item.filter;
                                        return (
                                            <button
                                                key={`sat-${item.filter}`}
                                                onClick={() => { handleSubNavChange('sattiklarim'); setOrderStatusFilter(item.filter); }}
                                                className={cn(
                                                    'w-full flex items-center gap-3 px-3 py-3 rounded-xl text-[14px] transition-all text-left relative',
                                                    isActive
                                                        ? 'bg-[#faf8f6] text-[#1a1a1a] font-semibold'
                                                        : 'text-[#6b7280] hover:bg-[#faf8f6] hover:text-[#1a1a1a]'
                                                )}
                                            >
                                                {isActive && <span className="absolute left-0 top-2 bottom-2 w-0.5 bg-[#f59e0b] rounded-full" />}
                                                <span className="text-xl leading-none flex-shrink-0">{item.icon}</span>
                                                <span className="flex-1 truncate">{item.label}</span>
                                                <span className={cn(
                                                    'text-[14px] font-bold flex-shrink-0',
                                                    isActive ? 'text-[#1a1a1a]' : 'text-[#9ca3af]'
                                                )}>{item.count}</span>
                                            </button>
                                        );
                                    })}

                                    <div className="my-2 border-t border-[#f0eceb]" />

                                    {/* Aldıklarım group */}
                                    <div className="flex items-center justify-between px-1 pb-1.5">
                                        <span className="text-[17px] font-black text-[#1a1a1a]">Aldıklarım</span>
                                        <button
                                            onClick={() => { handleSubNavChange('satin-aldiklarim'); setOrderStatusFilter('all'); }}
                                            className="text-[13px] text-[#b8651a] hover:underline font-medium"
                                        >
                                            Tümünü Gör
                                        </button>
                                    </div>
                                    {[
                                        { label: 'Kargodakiler',   filter: 'shipped', count: buyerCounts.shipped, icon: '🚚' },
                                        { label: 'Tamamlananlar',  filter: 'delivered', count: buyerCounts.delivered, icon: '👍' },
                                        { label: 'İptal / İade',   filter: 'cancelled_returned', count: buyerCounts.cancelled + buyerCounts.returned, icon: '↩️' },
                                    ].map((item) => {
                                        const isActive = activeSubNav === 'satin-aldiklarim' && orderStatusFilter === item.filter;
                                        return (
                                            <button
                                                key={`al-${item.filter}`}
                                                onClick={() => { handleSubNavChange('satin-aldiklarim'); setOrderStatusFilter(item.filter); }}
                                                className={cn(
                                                    'w-full flex items-center gap-3 px-3 py-3 rounded-xl text-[14px] transition-all text-left relative',
                                                    isActive
                                                        ? 'bg-[#faf8f6] text-[#1a1a1a] font-semibold'
                                                        : 'text-[#6b7280] hover:bg-[#faf8f6] hover:text-[#1a1a1a]'
                                                )}
                                            >
                                                {isActive && <span className="absolute left-0 top-2 bottom-2 w-0.5 bg-[#f59e0b] rounded-full" />}
                                                <span className="text-xl leading-none flex-shrink-0">{item.icon}</span>
                                                <span className="flex-1 truncate">{item.label}</span>
                                                <span className={cn(
                                                    'text-[14px] font-bold flex-shrink-0',
                                                    isActive ? 'text-[#1a1a1a]' : 'text-[#9ca3af]'
                                                )}>{item.count}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                currentSubNavItems.map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() => handleSubNavChange(item.id)}
                                        className={cn(
                                            'w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-[13px] transition-colors text-left',
                                            activeSubNav === item.id
                                                ? 'bg-[#1a1a1a] text-white font-semibold'
                                                : 'text-[#6b7280] hover:bg-[#faf8f6] hover:text-[#1a1a1a]'
                                        )}
                                    >
                                        <span className="truncate">{item.label}</span>
                                        {item.count !== undefined && (
                                            <span className={cn(
                                                'text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 flex-shrink-0',
                                                activeSubNav === item.id
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-amber-100 text-amber-700'
                                            )}>{item.count}</span>
                                        )}
                                    </button>
                                ))
                            )}
                        </aside>
                    )}

                    {/* RIGHT CONTENT */}
                    <div className="flex-1 min-w-0 p-6">
                        {/* Mobile sub-nav select */}
                        {currentSubNavItems.length > 0 && !viewingOrderId && (
                            <div className="mb-4 md:hidden">
                                <select
                                    value={activeSubNav}
                                    onChange={(e) => handleSubNavChange(e.target.value)}
                                    className="w-full rounded-xl border border-[#f0eceb] bg-white px-3 py-2 text-sm text-[#1a1a1a]"
                                >
                                    {currentSubNavItems.map((item) => (
                                        <option key={item.id} value={item.id}>{item.label}</option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Tab Content */}
                        {activeTab === 'ilanlarim' && <ListingsContent subNav={activeSubNav} />}
                        {activeTab === 'siparislerim' && (
                            viewingOrderId ? (
                                <DynamicOrderDetailView
                                    orderId={viewingOrderId}
                                    isSeller={viewingOrderIsSeller}
                                    onBack={handleBackFromOrderDetail}
                                />
                            ) : (
                                <DynamicOrdersContent
                                    subNav={activeSubNav}
                                    buyerOrders={buyerOrders}
                                    sellerOrders={sellerOrders}
                                    loadingOrders={loadingOrders}
                                    onViewOrderDetail={handleViewOrderDetail}
                                    statusFilter={orderStatusFilter}
                                    onStatusFilterChange={setOrderStatusFilter}
                                />
                            )
                        )}
                        {activeTab === 'begendiklerim' && <FavoritesContent subNav={activeSubNav} />}
                        {activeTab === 'hesap-hareketlerim' && <SettlementsContent subNav={activeSubNav} />}
                        {activeTab === 'destek' && <SupportTicketsContent subNav={activeSubNav} onSubNavChange={handleSubNavChange} />}
                        {activeTab === 'ayarlarim' && <SettingsContent subNav={activeSubNav} user={user} />}
                        {activeTab === 'firma-istekleri' && <FirmaIstekleriContent subNav={activeSubNav} />}
                        {activeTab === 'istek-yolla' && <IstekYollaContent subNav={activeSubNav} />}
                    </div>
                    </div>{/* end BOTTOM flex */}
                </div>{/* end Unified Card */}

                {/* Blog Section */}
                {randomPosts.length > 0 && (
                    <div className="mt-10 border-t border-[#f0eceb] pt-8">
                        <div className="flex items-center justify-between mb-5">
                            <h3 className="text-lg font-black text-[#1a1a1a]">
                                Ilginizi Cekebilir
                            </h3>
                            <Link
                                href="/market/blog"
                                className="text-sm text-[#b8651a] hover:text-[#934f12] font-medium flex items-center gap-1"
                            >
                                Tum Yazilar
                                <ArrowRight className="w-3.5 h-3.5" />
                            </Link>
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                            {randomPosts.map((post) => (
                                <BlogCard key={post.id} post={post} />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// Export with Suspense wrapper
export default function HesabimPage() {
    return (
        <Suspense fallback={<HesabimLoading />}>
            <HesabimContent />
        </Suspense>
    );
}
