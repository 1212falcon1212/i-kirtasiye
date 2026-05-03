'use client';

import { useState, useEffect } from 'react';
import { sellerApi, campaignsApi, Campaign, CreateCampaignData, reviewsApi, Review, SellerRating } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    BarChart3,
    ShoppingBag,
    Tag,
    Store,
    Percent,
    Box,
    Star,
    FileText,
    User,
    AlertCircle,
    TrendingUp,
    Loader2,
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

// Campaign Status Colors - pill badges
const CAMPAIGN_STATUS_COLORS: Record<string, string> = {
    'pending': 'bg-amber-50 text-amber-700',
    'active': 'bg-[#ffedd5] text-[#c2410c]',
    'inactive': 'bg-slate-100 text-slate-600',
    'rejected': 'bg-red-50 text-red-700',
    'expired': 'bg-gray-100 text-gray-600',
};

// Campaign status border-left colors
const CAMPAIGN_BORDER_COLORS: Record<string, string> = {
    'pending': 'border-l-amber-400',
    'active': 'border-l-[#ea580c]',
    'inactive': 'border-l-slate-300',
    'rejected': 'border-l-red-400',
    'expired': 'border-l-gray-300',
};

const CAMPAIGN_STATUS_LABELS: Record<string, string> = {
    'pending': 'Onay Bekliyor',
    'active': 'Aktif',
    'inactive': 'Pasif',
    'rejected': 'Reddedildi',
    'expired': 'Süresi Doldu',
};

const CAMPAIGN_TYPE_LABELS: Record<string, string> = {
    'product_discount': 'Ürün İndirimi',
    'store_discount': 'Mağaza İndirimi',
    'brand_discount': 'Marka İndirimi',
    'gift_product': 'Hediye Ürün',
};

// Sales Panel Content
export function SalesPanelContent({ subNav }: { subNav: string }) {
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [reviews, setReviews] = useState<Review[]>([]);
    const [ratings, setRatings] = useState<SellerRating | null>(null);
    const [stats, setStats] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [canCreateCampaign, setCanCreateCampaign] = useState(true);
    const [isCampaignDialogOpen, setIsCampaignDialogOpen] = useState(false);
    const [selectedCampaignType, setSelectedCampaignType] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [replyingTo, setReplyingTo] = useState<number | null>(null);
    const [replyText, setReplyText] = useState('');

    // Campaign form state
    const [campaignForm, setCampaignForm] = useState({
        name: '',
        description: '',
        discount_rate: '',
        min_purchase_amount: '',
        min_quantity: '',
        brand: '',
        starts_at: '',
        ends_at: '',
    });

    useEffect(() => {
        if (subNav === 'kampanya-paneli') {
            loadCampaigns();
        } else if (subNav === 'satis-performansim') {
            loadStats();
        } else if (subNav === 'puanim' || subNav === 'yorumlarim') {
            loadReviews();
        }
    }, [subNav]);

    const loadCampaigns = async () => {
        setLoading(true);
        try {
            const response = await campaignsApi.getAll();
            if (response.data) {
                setCampaigns(response.data.campaigns);
            }
            // Check seller score
            const statsResponse = await reviewsApi.getSellerReviews();
            if (statsResponse.data?.ratings) {
                const score = statsResponse.data.ratings.overall * 2; // Convert to 10-scale
                setCanCreateCampaign(statsResponse.data.ratings.count === 0 || score >= 7);
            }
        } catch (error) {
            console.error('Failed to load campaigns:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadStats = async () => {
        setLoading(true);
        try {
            const response = await sellerApi.getStats();
            if (response.data) {
                setStats(response.data.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadReviews = async () => {
        setLoading(true);
        try {
            const response = await reviewsApi.getSellerReviews();
            if (response.data) {
                setReviews(response.data.reviews);
                setRatings(response.data.ratings || null);
            }
        } catch (error) {
            console.error('Failed to load reviews:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCreateCampaign = async () => {
        if (!selectedCampaignType) return;

        setIsSubmitting(true);
        try {
            const data: CreateCampaignData = {
                name: campaignForm.name,
                description: campaignForm.description || undefined,
                type: selectedCampaignType as any,
                discount_rate: campaignForm.discount_rate ? parseFloat(campaignForm.discount_rate) : undefined,
                min_purchase_amount: campaignForm.min_purchase_amount ? parseFloat(campaignForm.min_purchase_amount) : undefined,
                min_quantity: campaignForm.min_quantity ? parseInt(campaignForm.min_quantity) : undefined,
                brand: selectedCampaignType === 'brand_discount' ? campaignForm.brand : undefined,
                starts_at: campaignForm.starts_at,
                ends_at: campaignForm.ends_at,
            };

            const response = await campaignsApi.create(data);
            if (response.data) {
                toast.success(response.data.message);
                setIsCampaignDialogOpen(false);
                resetCampaignForm();
                loadCampaigns();
            } else if (response.error) {
                toast.error(response.error);
            }
        } catch (error) {
            toast.error('Kampanya oluşturulurken hata oluştu');
        } finally {
            setIsSubmitting(false);
        }
    };

    const resetCampaignForm = () => {
        setSelectedCampaignType('');
        setCampaignForm({
            name: '',
            description: '',
            discount_rate: '',
            min_purchase_amount: '',
            min_quantity: '',
            brand: '',
            starts_at: '',
            ends_at: '',
        });
    };

    const handleReplySubmit = async (reviewId: number) => {
        if (!replyText.trim()) return;

        try {
            const response = await reviewsApi.reply(reviewId, replyText);
            if (response.data) {
                toast.success('Yanıtınız eklendi');
                setReplyingTo(null);
                setReplyText('');
                loadReviews();
            } else if (response.error) {
                toast.error(response.error);
            }
        } catch (error) {
            toast.error('Yanit eklenirken hata oluştu');
        }
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(price);
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('tr-TR');
    };

    // Calculate score from 5-star to 10-point scale
    const getScore = (rating: number) => (rating * 2).toFixed(1);

    return (
        <div className="space-y-6">
            {subNav === 'kampanya-paneli' && (
                <>
                    {!canCreateCampaign && (
                        <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                            <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                            <p className="text-sm text-[#6b7280]">
                                Puanınız 7&apos;nin altında olduğu için yeni kampanya oluşturmanız ve kampanyaya katilmaniz mumkun degildir.
                            </p>
                        </div>
                    )}

                    <div>
                        <h3 className="text-lg font-black text-[#1a1a1a] mb-4">Yeni Kampanya Oluştur</h3>
                        <div className="flex flex-wrap gap-3">
                            <button
                                className="h-auto py-3 px-4 border border-[#f0eceb] rounded-xl hover:bg-[#faf8f6] transition-colors flex items-center disabled:opacity-40 disabled:cursor-not-allowed bg-white"
                                disabled={!canCreateCampaign}
                                onClick={() => { setSelectedCampaignType('product_discount'); setIsCampaignDialogOpen(true); }}
                            >
                                <Percent className="w-4 h-4 mr-2 text-[#ea580c]" />
                                <span className="text-sm text-[#1a1a1a]"><strong className="text-[#ea580c]">Ürüne</strong> % indirim yap</span>
                            </button>
                            <button
                                className="h-auto py-3 px-4 border border-[#f0eceb] rounded-xl hover:bg-[#faf8f6] transition-colors flex items-center disabled:opacity-40 disabled:cursor-not-allowed bg-white"
                                disabled={!canCreateCampaign}
                                onClick={() => { setSelectedCampaignType('store_discount'); setIsCampaignDialogOpen(true); }}
                            >
                                <Store className="w-4 h-4 mr-2 text-[#ea580c]" />
                                <span className="text-sm text-[#1a1a1a]"><strong className="text-[#ea580c]">Magazaya</strong> % indirim yap</span>
                            </button>
                            <button
                                className="h-auto py-3 px-4 border border-[#f0eceb] rounded-xl hover:bg-[#faf8f6] transition-colors flex items-center disabled:opacity-40 disabled:cursor-not-allowed bg-white"
                                disabled={!canCreateCampaign}
                                onClick={() => { setSelectedCampaignType('brand_discount'); setIsCampaignDialogOpen(true); }}
                            >
                                <Tag className="w-4 h-4 mr-2 text-[#ea580c]" />
                                <span className="text-sm text-[#1a1a1a]"><strong className="text-[#ea580c]">Markaya</strong> % indirim yap</span>
                            </button>
                            <button
                                className="h-auto py-3 px-4 border border-[#f0eceb] rounded-xl hover:bg-[#faf8f6] transition-colors flex items-center disabled:opacity-40 disabled:cursor-not-allowed bg-white"
                                disabled={!canCreateCampaign}
                                onClick={() => { setSelectedCampaignType('gift_product'); setIsCampaignDialogOpen(true); }}
                            >
                                <Box className="w-4 h-4 mr-2 text-[#ea580c]" />
                                <span className="text-sm text-[#1a1a1a]"><strong className="text-[#ea580c]">Hediye ürün</strong> kampanyası yap</span>
                            </button>
                        </div>
                    </div>

                    {/* Campaign Create Dialog */}
                    <Dialog open={isCampaignDialogOpen} onOpenChange={(open) => { setIsCampaignDialogOpen(open); if (!open) resetCampaignForm(); }}>
                        <DialogContent className="sm:max-w-[500px] rounded-2xl border-[#f0eceb]">
                            <DialogHeader>
                                <DialogTitle className="text-lg font-black text-[#1a1a1a]">Yeni Kampanya Oluştur</DialogTitle>
                                <DialogDescription className="text-sm text-[#6b7280]">
                                    {CAMPAIGN_TYPE_LABELS[selectedCampaignType]} kampanyası olusturun.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-[#1a1a1a]">Kampanya Adi *</Label>
                                    <Input
                                        value={campaignForm.name}
                                        onChange={(e) => setCampaignForm({ ...campaignForm, name: e.target.value })}
                                        placeholder="Orn: Kis Indirimi"
                                        className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-[#1a1a1a]">Aciklama</Label>
                                    <Textarea
                                        value={campaignForm.description}
                                        onChange={(e) => setCampaignForm({ ...campaignForm, description: e.target.value })}
                                        placeholder="Kampanya detaylari..."
                                        rows={2}
                                        className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                    />
                                </div>
                                {selectedCampaignType !== 'gift_product' && (
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a]">İndirim Orani (%) *</Label>
                                        <Input
                                            type="number"
                                            min="1"
                                            max="100"
                                            value={campaignForm.discount_rate}
                                            onChange={(e) => setCampaignForm({ ...campaignForm, discount_rate: e.target.value })}
                                            placeholder="Orn: 20"
                                            className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                        />
                                    </div>
                                )}
                                {selectedCampaignType === 'brand_discount' && (
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a]">Marka *</Label>
                                        <Input
                                            value={campaignForm.brand}
                                            onChange={(e) => setCampaignForm({ ...campaignForm, brand: e.target.value })}
                                            placeholder="Marka adi"
                                            className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                        />
                                    </div>
                                )}
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a]">Min. Sepet Tutari</Label>
                                        <Input
                                            type="number"
                                            min="0"
                                            value={campaignForm.min_purchase_amount}
                                            onChange={(e) => setCampaignForm({ ...campaignForm, min_purchase_amount: e.target.value })}
                                            placeholder="0"
                                            className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a]">Min. Adet</Label>
                                        <Input
                                            type="number"
                                            min="1"
                                            value={campaignForm.min_quantity}
                                            onChange={(e) => setCampaignForm({ ...campaignForm, min_quantity: e.target.value })}
                                            placeholder="1"
                                            className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a]">Baslangic Tarihi *</Label>
                                        <Input
                                            type="datetime-local"
                                            value={campaignForm.starts_at}
                                            onChange={(e) => setCampaignForm({ ...campaignForm, starts_at: e.target.value })}
                                            className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-[#1a1a1a]">Bitis Tarihi *</Label>
                                        <Input
                                            type="datetime-local"
                                            value={campaignForm.ends_at}
                                            onChange={(e) => setCampaignForm({ ...campaignForm, ends_at: e.target.value })}
                                            className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                        />
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setIsCampaignDialogOpen(false)} className="border-[#f0eceb] hover:bg-[#faf8f6] rounded-xl">İptal</Button>
                                <Button
                                    onClick={handleCreateCampaign}
                                    disabled={isSubmitting || !campaignForm.name || !campaignForm.starts_at || !campaignForm.ends_at}
                                    className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl font-semibold"
                                >
                                    {isSubmitting ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                                    Kampanya Oluştur
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <div>
                        <h3 className="text-lg font-black text-[#1a1a1a] mb-4">Kampanyalarim</h3>
                        {loading ? (
                            <div className="space-y-3">
                                {[1, 2].map((i) => <Skeleton key={i} className="h-24 w-full rounded-2xl" />)}
                            </div>
                        ) : campaigns.length === 0 ? (
                            <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                                <Tag className="w-16 h-16 mx-auto text-[#f0eceb] mb-4" />
                                <p className="text-sm text-[#6b7280]">Kampanyaniz bulunmamaktadir</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {campaigns.map((campaign) => (
                                    <div
                                        key={campaign.id}
                                        className={`bg-white border border-[#f0eceb] rounded-2xl p-4 flex items-start gap-4 border-l-4 ${CAMPAIGN_BORDER_COLORS[campaign.status] || 'border-l-slate-300'}`}
                                    >
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-3 flex-wrap">
                                                <h4 className="text-sm font-bold text-[#1a1a1a]">{campaign.name}</h4>
                                                <Badge className={`px-3 py-1 rounded-full text-xs font-semibold ${CAMPAIGN_STATUS_COLORS[campaign.status]}`}>
                                                    {CAMPAIGN_STATUS_LABELS[campaign.status]}
                                                </Badge>
                                            </div>
                                            <div className="flex items-center gap-3 mt-1.5">
                                                <span className="text-xs text-[#6b7280]">{CAMPAIGN_TYPE_LABELS[campaign.type]}</span>
                                                {campaign.discount_rate && (
                                                    <span className="text-xs font-semibold text-[#ea580c]">%{campaign.discount_rate} indirim</span>
                                                )}
                                            </div>
                                            <div className="mt-2 flex gap-4 text-xs text-[#6b7280]">
                                                <span>Baslangic: {formatDate(campaign.starts_at)}</span>
                                                <span>Bitis: {formatDate(campaign.ends_at)}</span>
                                            </div>
                                            {campaign.status === 'rejected' && campaign.rejection_reason && (
                                                <div className="mt-3 p-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700">
                                                    <strong>Ret Sebebi:</strong> {campaign.rejection_reason}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </>
            )}

            {subNav === 'satis-performansim' && (
                <>
                    {loading ? (
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                            {[1, 2, 3, 4].map((i) => <Skeleton key={i} className="h-28 w-full rounded-2xl" />)}
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                            <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                <p className="text-2xl font-black text-[#1a1a1a]">
                                    {stats?.total_sales?.formatted || '₺0,00'}
                                </p>
                                <p className="text-xs text-[#6b7280] mt-1">Bu Ay Satış</p>
                                {stats?.total_sales?.change && (
                                    <p className={`text-xs mt-1.5 font-medium ${stats.total_sales.trend === 'up' ? 'text-[#ea580c]' : 'text-red-600'}`}>
                                        {stats.total_sales.change}
                                    </p>
                                )}
                            </div>
                            <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                <p className="text-2xl font-black text-[#1a1a1a]">
                                    {stats?.active_offers?.value || 0}
                                </p>
                                <p className="text-xs text-[#6b7280] mt-1">Aktif Ilan</p>
                                {stats?.active_offers?.pending && (
                                    <p className="text-xs text-amber-600 mt-1.5 font-medium">{stats.active_offers.pending}</p>
                                )}
                            </div>
                            <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                <p className="text-2xl font-black text-[#1a1a1a]">
                                    {stats?.pending_orders?.value || 0}
                                </p>
                                <p className="text-xs text-[#6b7280] mt-1">Bekleyen Sipariş</p>
                            </div>
                            <div className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                <TrendingUp className="w-5 h-5 text-[#ea580c] mb-2" />
                                <p className="text-2xl font-black text-[#1a1a1a]">
                                    {stats?.total_sales?.formatted ? '—' : '—'}
                                </p>
                                <p className="text-xs text-[#6b7280] mt-1">Performans</p>
                            </div>
                        </div>
                    )}
                </>
            )}

            {subNav === 'puanim' && (
                <div className="bg-white border border-[#f0eceb] rounded-2xl p-6">
                    {loading ? (
                        <div className="space-y-4">
                            <Skeleton className="h-20 w-20 rounded-full mx-auto" />
                            <Skeleton className="h-6 w-32 mx-auto" />
                            <Skeleton className="h-4 w-full" />
                            <Skeleton className="h-4 w-full" />
                            <Skeleton className="h-4 w-full" />
                        </div>
                    ) : ratings && ratings.count > 0 ? (
                        <>
                            <div className="text-center mb-8">
                                <div className="inline-flex items-center gap-2 mb-2">
                                    <Star className="w-8 h-8 text-[#ea580c] fill-[#ea580c]" />
                                    <span className="text-5xl font-black text-[#ea580c]">{getScore(ratings.overall)}</span>
                                </div>
                                <p className="text-sm text-[#6b7280]">{ratings.count} degerlendirme</p>
                            </div>
                            <div className="space-y-4 max-w-md mx-auto">
                                <div>
                                    <div className="flex items-center justify-between mb-1.5">
                                        <span className="text-sm text-[#6b7280]">Teslimat Hizi</span>
                                        <span className="text-sm font-bold text-[#1a1a1a]">{getScore(ratings.delivery)}</span>
                                    </div>
                                    <div className="w-full h-2 bg-[#f0eceb] rounded-full overflow-hidden">
                                        <div className="h-full bg-[#1e3a8a] rounded-full transition-all" style={{ width: `${(ratings.delivery / 5) * 100}%` }} />
                                    </div>
                                </div>
                                <div>
                                    <div className="flex items-center justify-between mb-1.5">
                                        <span className="text-sm text-[#6b7280]">Ürün Kalitesi</span>
                                        <span className="text-sm font-bold text-[#1a1a1a]">{getScore(ratings.quality)}</span>
                                    </div>
                                    <div className="w-full h-2 bg-[#f0eceb] rounded-full overflow-hidden">
                                        <div className="h-full bg-[#1e3a8a] rounded-full transition-all" style={{ width: `${(ratings.quality / 5) * 100}%` }} />
                                    </div>
                                </div>
                                <div>
                                    <div className="flex items-center justify-between mb-1.5">
                                        <span className="text-sm text-[#6b7280]">İletisim</span>
                                        <span className="text-sm font-bold text-[#1a1a1a]">{getScore(ratings.communication)}</span>
                                    </div>
                                    <div className="w-full h-2 bg-[#f0eceb] rounded-full overflow-hidden">
                                        <div className="h-full bg-[#1e3a8a] rounded-full transition-all" style={{ width: `${(ratings.communication / 5) * 100}%` }} />
                                    </div>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="text-center py-8">
                            <Star className="w-16 h-16 mx-auto text-[#f0eceb] mb-4" />
                            <p className="text-sm text-[#6b7280]">Henuz degerlendirme bulunmuyor</p>
                            <p className="text-xs text-[#6b7280] mt-1">Satış yaptikca musterilerinizden puan alacaksiniz</p>
                        </div>
                    )}
                </div>
            )}

            {subNav === 'yorumlarim' && (
                <div className="space-y-3">
                    {loading ? (
                        <div className="space-y-3">
                            {[1, 2, 3].map((i) => <Skeleton key={i} className="h-32 w-full rounded-2xl" />)}
                        </div>
                    ) : reviews.length === 0 ? (
                        <div className="text-center py-12 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                            <FileText className="w-16 h-16 mx-auto text-[#f0eceb] mb-4" />
                            <p className="text-sm text-[#6b7280]">Henuz yorum bulunmuyor</p>
                        </div>
                    ) : (
                        reviews.map((review) => (
                            <div key={review.id} className="bg-white border border-[#f0eceb] rounded-2xl p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-9 h-9 bg-[#faf8f6] rounded-full flex items-center justify-center">
                                            <User className="w-4 h-4 text-[#6b7280]" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold text-[#1a1a1a]">
                                                {review.buyer?.nickname || review.buyer?.business_name || 'Anonim'}
                                            </p>
                                            <p className="text-xs text-[#6b7280]">{formatDate(review.created_at)}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-0.5">
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <Star
                                                key={star}
                                                className={`w-3.5 h-3.5 ${star <= review.rating ? 'text-[#ea580c] fill-[#ea580c]' : 'text-[#f0eceb]'}`}
                                            />
                                        ))}
                                    </div>
                                </div>
                                {review.product && (
                                    <p className="text-xs text-[#6b7280] mt-2">
                                        Ürün: {review.product.name}
                                    </p>
                                )}
                                {review.comment && (
                                    <p className="mt-3 text-sm text-[#374151]">{review.comment}</p>
                                )}
                                {review.seller_reply ? (
                                    <div className="mt-3 p-3 bg-[#faf8f6] rounded-xl border border-[#f0eceb]">
                                        <p className="text-xs text-[#6b7280] mb-1">Yanitiniz:</p>
                                        <p className="text-sm text-[#374151]">{review.seller_reply}</p>
                                    </div>
                                ) : (
                                    <div className="mt-3">
                                        {replyingTo === review.id ? (
                                            <div className="space-y-2">
                                                <Textarea
                                                    value={replyText}
                                                    onChange={(e) => setReplyText(e.target.value)}
                                                    placeholder="Yanitinizi yazin..."
                                                    rows={2}
                                                    className="border-[#f0eceb] rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]"
                                                />
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleReplySubmit(review.id)}
                                                        disabled={!replyText.trim()}
                                                        className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl font-semibold"
                                                    >
                                                        Gönder
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => { setReplyingTo(null); setReplyText(''); }}
                                                        className="border-[#f0eceb] hover:bg-[#faf8f6] rounded-xl"
                                                    >
                                                        İptal
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => setReplyingTo(review.id)}
                                                className="border-[#f0eceb] hover:bg-[#faf8f6] rounded-xl text-sm"
                                            >
                                                Yanit Ver
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </div>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}
