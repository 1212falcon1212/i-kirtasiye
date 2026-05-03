'use client';

import { useEffect, useState, useCallback } from 'react';
import Link from 'next/link';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Tag,
    Percent,
    Clock,
    Filter,
    Box,
    ArrowLeft,
    Flame,
    Zap,
    ChevronDown,
    X,
    SlidersHorizontal
} from 'lucide-react';
import { productsApi, categoriesApi, Product, Category } from '@/lib/api';
import { ProductCard } from '@/components/market/ProductCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

// Extended product type for deals
interface DealProduct extends Product {
    original_price?: number;
    discount_percentage?: number;
    deal_ends_at?: string;
}

// Countdown timer hook
function useCountdown(targetDate: string | undefined) {
    const [timeLeft, setTimeLeft] = useState({ days: 0, hours: 0, minutes: 0, seconds: 0 });

    useEffect(() => {
        if (!targetDate) return;

        const calculateTimeLeft = () => {
            const difference = new Date(targetDate).getTime() - new Date().getTime();

            if (difference <= 0) {
                return { days: 0, hours: 0, minutes: 0, seconds: 0 };
            }

            return {
                days: Math.floor(difference / (1000 * 60 * 60 * 24)),
                hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
                minutes: Math.floor((difference / 1000 / 60) % 60),
                seconds: Math.floor((difference / 1000) % 60),
            };
        };

        setTimeLeft(calculateTimeLeft());
        const timer = setInterval(() => setTimeLeft(calculateTimeLeft()), 1000);

        return () => clearInterval(timer);
    }, [targetDate]);

    return timeLeft;
}

// Countdown Timer Component
function CountdownTimer({ targetDate }: { targetDate: string }) {
    const { days, hours, minutes, seconds } = useCountdown(targetDate);

    return (
        <div className="flex items-center gap-1 text-xs">
            <Clock className="w-3 h-3 text-red-500" />
            <div className="flex gap-1">
                {days > 0 && (
                    <span className="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded font-mono font-bold">
                        {days}g
                    </span>
                )}
                <span className="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded font-mono font-bold">
                    {String(hours).padStart(2, '0')}
                </span>
                <span className="text-red-500">:</span>
                <span className="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded font-mono font-bold">
                    {String(minutes).padStart(2, '0')}
                </span>
                <span className="text-red-500">:</span>
                <span className="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded font-mono font-bold">
                    {String(seconds).padStart(2, '0')}
                </span>
            </div>
        </div>
    );
}

// Deal Card Component
function DealCard({ product, index }: { product: DealProduct; index: number }) {
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price);
    };

    // Mock discount data if not present
    const discountPercentage = product.discount_percentage || Math.floor(Math.random() * 30) + 10;
    const originalPrice = product.original_price || (product.lowest_price ? product.lowest_price * (1 + discountPercentage / 100) : 0);
    const dealEndsAt = product.deal_ends_at || new Date(Date.now() + (Math.random() * 7 + 1) * 24 * 60 * 60 * 1000).toISOString();

    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: index * 0.05, duration: 0.3 }}
        >
            <Link href={`/market/product/${product.id}`}>
                <Card className="group relative border-slate-200 dark:border-slate-800 hover:shadow-md dark:hover:shadow-[#ea580c]/5 transition-all duration-300 cursor-pointer overflow-hidden h-full">
                    {/* Discount Badge */}
                    <div className="absolute top-3 left-3 z-10">
                        <Badge className="bg-gradient-to-r from-red-500 to-cyan-600 text-white border-0 shadow-lg shadow-red-500/30 font-bold text-sm px-2.5 py-1">
                            <Percent className="w-3 h-3 mr-1" />
                            {discountPercentage}% indirim
                        </Badge>
                    </div>

                    {/* Hot Deal Indicator */}
                    {discountPercentage >= 25 && (
                        <div className="absolute top-3 right-3 z-10">
                            <div className="bg-gradient-to-r from-[#ea580c] to-amber-500 text-white rounded-full p-1.5 shadow-lg shadow-[#ea580c]/30 ">
                                <Flame className="w-4 h-4" />
                            </div>
                        </div>
                    )}

                    <CardContent className="p-0">
                        {/* Image */}
                        <div className="relative aspect-square bg-white dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                            {(product.image_url || product.image) ? (
                                <img
                                    src={product.image_url || product.image}
                                    alt={product.name}
                                    className="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-500"
                                />
                            ) : (
                                <Box className="w-20 h-20 text-slate-300 dark:text-slate-600" />
                            )}

                            {/* Overlay on hover */}
                            <div className="absolute inset-0 bg-gradient-to-t from-[#ffedd5]/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                        </div>

                        {/* Content */}
                        <div className="p-4 space-y-3">
                            {/* Countdown Timer */}
                            <div className="flex items-center justify-between">
                                <CountdownTimer targetDate={dealEndsAt} />
                                {Number(product.offers_count) > 0 && (
                                    <span className="text-xs text-slate-500 dark:text-slate-400">
                                        {product.offers_count} satıcı
                                    </span>
                                )}
                            </div>

                            {/* Brand */}
                            {product.brand && (
                                <p className="text-xs font-semibold text-[#ea580c] dark:text-[#ffedd5] uppercase tracking-wider">
                                    {product.brand}
                                </p>
                            )}

                            {/* Name */}
                            <h3 className="font-bold text-slate-900 dark:text-white group-hover:text-[#ea580c] dark:group-hover:text-[#ea580c] line-clamp-2 min-h-[2.5rem] transition-colors">
                                {product.name}
                            </h3>

                            {/* Price Section */}
                            <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-xl font-bold text-[#ea580c] dark:text-[#ffedd5]">
                                        {formatPrice(product.lowest_price || 0)}
                                    </span>
                                    <span className="text-sm text-slate-400 line-through">
                                        {formatPrice(originalPrice)}
                                    </span>
                                </div>
                                <p className="text-xs text-[#ea580c] dark:text-[#ffedd5] font-medium mt-1">
                                    {formatPrice(originalPrice - (product.lowest_price || 0))} tasarruf
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </motion.div>
    );
}

// Loading Skeleton
function LoadingSkeleton() {
    return (
        <div className="bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700 overflow-clip">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-3">
                {[...Array(8)].map((_, i) => (
                    <Card key={i} className="border-slate-200 dark:border-slate-800 overflow-hidden">
                        <CardContent className="p-0">
                            <Skeleton className="aspect-square w-full" />
                            <div className="p-4 space-y-3">
                                <Skeleton className="h-4 w-24" />
                                <Skeleton className="h-3 w-16" />
                                <Skeleton className="h-5 w-full" />
                                <Skeleton className="h-5 w-3/4" />
                                <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <Skeleton className="h-6 w-28" />
                                    <Skeleton className="h-4 w-20 mt-1" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}

export default function KampanyalarPage() {
    const [products, setProducts] = useState<DealProduct[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    // Filters
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [selectedDiscount, setSelectedDiscount] = useState<string>('all');
    const [showFilters, setShowFilters] = useState(false);

    // Load categories
    useEffect(() => {
        const loadCategories = async () => {
            try {
                const response = await categoriesApi.getAll();
                if (response.data?.categories) {
                    setCategories(response.data.categories);
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        };
        loadCategories();
    }, []);

    // Load products
    const loadProducts = useCallback(async () => {
        setIsLoading(true);
        try {
            const params: { page?: number; per_page?: number; category?: string } = {
                page: currentPage,
                per_page: 12,
            };

            if (selectedCategory !== 'all') {
                params.category = selectedCategory;
            }

            const response = await productsApi.getAll(params);

            if (response.data) {
                // Add mock discount data to products
                const dealsProducts = response.data.products.map((product) => ({
                    ...product,
                    discount_percentage: Math.floor(Math.random() * 40) + 10,
                    original_price: product.lowest_price ? product.lowest_price * (1 + (Math.random() * 0.5 + 0.1)) : undefined,
                    deal_ends_at: new Date(Date.now() + (Math.random() * 7 + 1) * 24 * 60 * 60 * 1000).toISOString(),
                }));

                // Filter by discount percentage if selected
                let filteredProducts = dealsProducts;
                if (selectedDiscount !== 'all') {
                    const minDiscount = parseInt(selectedDiscount);
                    filteredProducts = dealsProducts.filter(p => (p.discount_percentage || 0) >= minDiscount);
                }

                setProducts(filteredProducts);
                setTotalPages(response.data.pagination?.last_page || 1);
            }
        } catch (error) {
            console.error('Failed to load deals:', error);
        } finally {
            setIsLoading(false);
        }
    }, [currentPage, selectedCategory, selectedDiscount]);

    useEffect(() => {
        loadProducts();
    }, [loadProducts]);

    const clearFilters = () => {
        setSelectedCategory('all');
        setSelectedDiscount('all');
        setCurrentPage(1);
    };

    const hasActiveFilters = selectedCategory !== 'all' || selectedDiscount !== 'all';

    return (
        <div className="min-h-screen">
            {/* Hero Banner */}
            <div className="relative bg-gradient-to-br from-[#ffedd5] via-[#ea580c] to-[#c2410c] dark:from-[#c2410c] dark:via-[#831843] dark:to-[#500724] overflow-hidden">
                {/* Background Pattern */}
                <div className="absolute inset-0 opacity-10">
                    <div className="absolute inset-0" style={{
                        backgroundImage: 'radial-gradient(circle at 2px 2px, white 1px, transparent 0)',
                        backgroundSize: '32px 32px'
                    }} />
                </div>

                {/* Floating Elements */}
                <motion.div
                    className="absolute top-10 left-10 text-white/20"
                    animate={{ y: [0, -20, 0], rotate: [0, 10, 0] }}
                    transition={{ duration: 4, repeat: Infinity }}
                >
                    <Tag className="w-16 h-16" />
                </motion.div>
                <motion.div
                    className="absolute bottom-10 right-20 text-white/20"
                    animate={{ y: [0, 20, 0], rotate: [0, -10, 0] }}
                    transition={{ duration: 5, repeat: Infinity }}
                >
                    <Percent className="w-20 h-20" />
                </motion.div>
                <motion.div
                    className="absolute top-20 right-40 text-white/10"
                    animate={{ scale: [1, 1.2, 1] }}
                    transition={{ duration: 3, repeat: Infinity }}
                >
                    <Zap className="w-12 h-12" />
                </motion.div>

                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                        className="text-center"
                    >
                        <Badge className="bg-white/20 text-white border-white/30 mb-4">
                            <Flame className="w-3 h-3 mr-1" />
                            Özel Fırsatlar
                        </Badge>
                        <h1 className="text-3xl md:text-5xl font-bold text-white mb-4">
                            Kampanyalar ve İndirimler
                        </h1>
                        <p className="text-lg md:text-xl text-white/80 max-w-2xl mx-auto">
                            i-kirtasiye pazaryerinin en avantajlı fırsatlarını kaçırmayın.
                            Sınırlı süreli kampanyalar ve özel indirimler burada!
                        </p>
                    </motion.div>
                </div>
            </div>

            {/* Main Content */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                    <Link href="/market" className="hover:text-[#ea580c] transition-colors">Pazaryeri</Link>
                    <span>/</span>
                    <span className="text-slate-900 dark:text-white font-medium">Kampanyalar</span>
                </div>

                {/* Filters Bar */}
                <div className="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 mb-6 shadow-sm">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <Button
                                variant="outline"
                                size="sm"
                                className="gap-2 md:hidden"
                                onClick={() => setShowFilters(!showFilters)}
                            >
                                <SlidersHorizontal className="w-4 h-4" />
                                Filtrele
                                <ChevronDown className={`w-4 h-4 transition-transform ${showFilters ? 'rotate-180' : ''}`} />
                            </Button>

                            <div className={`flex flex-col md:flex-row gap-3 ${showFilters ? 'flex' : 'hidden md:flex'} w-full md:w-auto`}>
                                {/* Category Filter */}
                                <Select value={selectedCategory} onValueChange={(value) => { setSelectedCategory(value); setCurrentPage(1); }}>
                                    <SelectTrigger className="w-full md:w-[180px]">
                                        <SelectValue placeholder="Kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Tüm Kategoriler</SelectItem>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.slug}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                {/* Discount Filter */}
                                <Select value={selectedDiscount} onValueChange={(value) => { setSelectedDiscount(value); setCurrentPage(1); }}>
                                    <SelectTrigger className="w-full md:w-[180px]">
                                        <SelectValue placeholder="İndirim Oranı" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Tüm İndirimler</SelectItem>
                                        <SelectItem value="10">%10 ve üzeri</SelectItem>
                                        <SelectItem value="20">%20 ve üzeri</SelectItem>
                                        <SelectItem value="30">%30 ve üzeri</SelectItem>
                                        <SelectItem value="40">%40 ve üzeri</SelectItem>
                                        <SelectItem value="50">%50 ve üzeri</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Active Filters & Results Count */}
                        <div className="flex items-center gap-3">
                            {hasActiveFilters && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearFilters}
                                    className="text-slate-500 hover:text-red-500"
                                >
                                    <X className="w-4 h-4 mr-1" />
                                    Filtreleri Temizle
                                </Button>
                            )}
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                {products.length} kampanyalı ürün
                            </span>
                        </div>
                    </div>
                </div>

                {/* Products Grid */}
                <AnimatePresence mode="wait">
                    {isLoading ? (
                        <LoadingSkeleton />
                    ) : products.length === 0 ? (
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                        >
                            <Card className="border-slate-200 dark:border-slate-800">
                                <CardContent className="flex flex-col items-center justify-center py-16">
                                    <div className="w-20 h-20 rounded-full bg-[#ffedd5] dark:bg-[#1e40af]/30 flex items-center justify-center mb-4">
                                        <Tag className="h-10 w-10 text-[#ea580c] dark:text-[#ffedd5]" />
                                    </div>
                                    <h2 className="text-xl font-semibold mb-2 text-slate-900 dark:text-white">
                                        Aktif kampanya bulunamadı
                                    </h2>
                                    <p className="text-slate-500 dark:text-slate-400 mb-4 text-center max-w-md">
                                        Seçtiğiniz filtrelere uygun kampanya bulunmuyor.
                                        Filtreleri değiştirmeyi veya daha sonra tekrar kontrol etmeyi deneyin.
                                    </p>
                                    <div className="flex gap-3">
                                        {hasActiveFilters && (
                                            <Button variant="outline" onClick={clearFilters}>
                                                <X className="w-4 h-4 mr-2" />
                                                Filtreleri Temizle
                                            </Button>
                                        )}
                                        <Link href="/market">
                                            <Button className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white">
                                                <ArrowLeft className="w-4 h-4 mr-2" />
                                                Pazaryerine Dön
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        </motion.div>
                    ) : (
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                        >
                            <div className="bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700 overflow-clip">
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-3">
                                    {products.map((product) => (
                                        <ProductCard key={product.id} product={product} />
                                    ))}
                                </div>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Pagination */}
                {totalPages > 1 && !isLoading && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        className="flex justify-center gap-2 pt-8"
                    >
                        <Button
                            variant="outline"
                            disabled={currentPage === 1}
                            onClick={() => setCurrentPage(currentPage - 1)}
                            className="border-slate-300 dark:border-slate-700"
                        >
                            Önceki
                        </Button>
                        <span className="flex items-center px-4 text-slate-600 dark:text-slate-400 font-medium">
                            {currentPage} / {totalPages}
                        </span>
                        <Button
                            variant="outline"
                            disabled={currentPage === totalPages}
                            onClick={() => setCurrentPage(currentPage + 1)}
                            className="border-slate-300 dark:border-slate-700"
                        >
                            Sonraki
                        </Button>
                    </motion.div>
                )}
            </div>
        </div>
    );
}
