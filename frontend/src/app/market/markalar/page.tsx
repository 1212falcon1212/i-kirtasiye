'use client';

import { useEffect, useState, useCallback } from 'react';
import Link from 'next/link';
import { Search, Building2, Box, ArrowRight, Filter, Grid3X3, List } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { api } from '@/lib/api';
import { cn } from '@/lib/utils';

interface Brand {
    id: number;
    name: string;
    slug: string;
    logo_url?: string | null;
    description?: string | null;
    products_count?: number;
}

function BrandLogo({ brand, size = 'md' }: { brand: Brand; size?: 'sm' | 'md' }) {
    const [imgError, setImgError] = useState(false);
    const handleError = useCallback(() => setImgError(true), []);

    const isSm = size === 'sm';
    const wrapperClass = isSm
        ? 'w-12 h-12 rounded-lg flex-shrink-0'
        : 'w-16 h-16 mb-3 rounded-xl';

    if (brand.logo_url && !imgError) {
        return (
            <div className={`${wrapperClass} overflow-hidden bg-slate-50 flex items-center justify-center`}>
                <img
                    src={brand.logo_url}
                    alt={brand.name}
                    className="max-w-full max-h-full object-contain"
                    onError={handleError}
                />
            </div>
        );
    }

    return (
        <div className={`${wrapperClass} bg-gradient-to-br from-[#fbeede] to-teal-50 flex items-center justify-center`}>
            <span className={`font-bold text-[#b8651a] ${isSm ? 'text-lg' : 'text-2xl'}`}>
                {brand.name.charAt(0)}
            </span>
        </div>
    );
}

const FALLBACK_BRANDS: Brand[] = [
    { id: 1, name: 'Abdi Ibrahim', slug: 'abdi-ibrahim', products_count: 245 },
    { id: 2, name: 'Bayer', slug: 'bayer', products_count: 189 },
    { id: 3, name: 'Pfizer', slug: 'pfizer', products_count: 156 },
    { id: 4, name: 'Novartis', slug: 'novartis', products_count: 134 },
    { id: 5, name: 'Sanofi', slug: 'sanofi', products_count: 198 },
    { id: 6, name: 'Roche', slug: 'roche', products_count: 87 },
    { id: 7, name: 'GSK', slug: 'gsk', products_count: 112 },
    { id: 8, name: 'AstraZeneca', slug: 'astrazeneca', products_count: 95 },
    { id: 9, name: 'Johnson & Johnson', slug: 'johnson-johnson', products_count: 167 },
    { id: 10, name: 'Merck', slug: 'merck', products_count: 78 },
    { id: 11, name: 'Eczacibasi', slug: 'eczacibasi', products_count: 234 },
    { id: 12, name: 'Bioderma', slug: 'bioderma', products_count: 89 },
    { id: 13, name: 'La Roche-Posay', slug: 'la-roche-posay', products_count: 76 },
    { id: 14, name: 'Vichy', slug: 'vichy', products_count: 65 },
    { id: 15, name: 'Avene', slug: 'avene', products_count: 54 },
    { id: 16, name: 'Mustela', slug: 'mustela', products_count: 43 },
    { id: 17, name: 'Bepanthen', slug: 'bepanthen', products_count: 32 },
    { id: 18, name: 'Eucerin', slug: 'eucerin', products_count: 67 },
    { id: 19, name: 'CeraVe', slug: 'cerave', products_count: 45 },
    { id: 20, name: 'Neutrogena', slug: 'neutrogena', products_count: 78 },
];

const ALPHABET = 'ABCDEFGHIJKLMNOPRSTUVYZ'.split('');

export default function BrandsPage() {
    const [brands, setBrands] = useState<Brand[]>([]);
    const [filteredBrands, setFilteredBrands] = useState<Brand[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedLetter, setSelectedLetter] = useState<string | null>(null);
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

    useEffect(() => {
        const loadBrands = async () => {
            try {
                const response = await api.get<{ data: Brand[] } | Brand[]>('/brands');
                if (response.data) {
                    const brandsData = Array.isArray(response.data) ? response.data : response.data.data || [];
                    setBrands(brandsData.length > 0 ? brandsData : FALLBACK_BRANDS);
                    setFilteredBrands(brandsData.length > 0 ? brandsData : FALLBACK_BRANDS);
                }
            } catch {
                setBrands(FALLBACK_BRANDS);
                setFilteredBrands(FALLBACK_BRANDS);
            } finally {
                setIsLoading(false);
            }
        };
        loadBrands();
    }, []);

    useEffect(() => {
        let result = brands;

        if (searchQuery) {
            result = result.filter(brand =>
                brand.name.toLowerCase().includes(searchQuery.toLowerCase())
            );
        }

        if (selectedLetter) {
            result = result.filter(brand =>
                brand.name.toUpperCase().startsWith(selectedLetter)
            );
        }

        setFilteredBrands(result);
    }, [searchQuery, selectedLetter, brands]);

    const groupedBrands = filteredBrands.reduce((acc, brand) => {
        const letter = brand.name.charAt(0).toUpperCase();
        if (!acc[letter]) acc[letter] = [];
        acc[letter].push(brand);
        return acc;
    }, {} as Record<string, Brand[]>);

    const sortedLetters = Object.keys(groupedBrands).sort();

    return (
        <div className="min-h-screen">
            {/* Hero Section */}
            <section className="relative py-12 overflow-hidden bg-gradient-to-br from-[#fbeede] via-teal-600 to-cyan-600">
                <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.05%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-30" />

                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-white/20 backdrop-blur-sm rounded-lg mb-6">
                            <Building2 className="w-8 h-8 text-white" />
                        </div>
                        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">
                            Tüm Markalar
                        </h1>
                        <p className="text-[#b8651a] text-lg max-w-2xl mx-auto mb-8">
                            {brands.length}+ marka arasindan ihtiyaciniz olan urunleri bulun
                        </p>

                        {/* Search Bar */}
                        <div className="max-w-xl mx-auto relative">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <Input
                                type="search"
                                placeholder="Marka ara..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full h-14 pl-12 pr-4 bg-white border-0 rounded-lg text-slate-900 placeholder:text-slate-400 text-lg shadow-md"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* Alphabet Filter */}
            <div className="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-slate-100 shadow-sm">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-1 overflow-x-auto scrollbar-hide flex-1">
                            <button
                                onClick={() => setSelectedLetter(null)}
                                className={cn(
                                    "px-3 py-1.5 text-sm font-medium rounded-lg transition-colors flex-shrink-0",
                                    selectedLetter === null
                                        ? "bg-[#fbeede] text-white"
                                        : "text-slate-600 hover:bg-slate-100"
                                )}
                            >
                                Tumu
                            </button>
                            {ALPHABET.map(letter => (
                                <button
                                    key={letter}
                                    onClick={() => setSelectedLetter(letter)}
                                    className={cn(
                                        "w-8 h-8 text-sm font-medium rounded-lg transition-colors flex-shrink-0",
                                        selectedLetter === letter
                                            ? "bg-[#fbeede] text-white"
                                            : "text-slate-600 hover:bg-slate-100"
                                    )}
                                >
                                    {letter}
                                </button>
                            ))}
                        </div>

                        <div className="flex items-center gap-1 border-l border-slate-200 pl-4">
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setViewMode('grid')}
                                className={cn(viewMode === 'grid' && 'bg-slate-100')}
                            >
                                <Grid3X3 className="w-4 h-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setViewMode('list')}
                                className={cn(viewMode === 'list' && 'bg-slate-100')}
                            >
                                <List className="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Brands Content */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {isLoading ? (
                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        {Array(20).fill(0).map((_, i) => (
                            <Skeleton key={i} className="h-32 rounded-lg" />
                        ))}
                    </div>
                ) : filteredBrands.length === 0 ? (
                    <div className="text-center py-16">
                        <div className="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <Search className="w-8 h-8 text-slate-400" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900 mb-2">Marka bulunamadi</h3>
                        <p className="text-slate-500">Arama kriterlerinize uygun marka bulunamadi.</p>
                        <Button
                            variant="outline"
                            className="mt-4"
                            onClick={() => {
                                setSearchQuery('');
                                setSelectedLetter(null);
                            }}
                        >
                            Filtreleri Temizle
                        </Button>
                    </div>
                ) : viewMode === 'grid' ? (
                    <div className="space-y-10">
                        {sortedLetters.map(letter => (
                            <div key={letter}>
                                <div className="flex items-center gap-4 mb-4">
                                    <span className="w-10 h-10 bg-[#fbeede] text-[#b8651a] rounded-xl flex items-center justify-center font-bold text-lg">
                                        {letter}
                                    </span>
                                    <div className="h-px flex-1 bg-slate-200" />
                                    <span className="text-sm text-slate-500">{groupedBrands[letter].length} marka</span>
                                </div>
                                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                                    {groupedBrands[letter].map(brand => (
                                        <Link
                                            key={brand.id}
                                            href={`/market/marka/${brand.slug}`}
                                            className="group bg-white rounded-lg border border-slate-200 p-5 hover:border-[#fbeede] hover:shadow-lg hover:shadow-[#b8651a]/10 transition-all duration-300"
                                        >
                                            <div className="flex flex-col items-center text-center">
                                                <BrandLogo brand={brand} />
                                                <h3 className="font-semibold text-slate-900 group-hover:text-[#b8651a] transition-colors mb-1 line-clamp-1">
                                                    {brand.name}
                                                </h3>
                                                {brand.products_count !== undefined && (
                                                    <span className="text-xs text-slate-500 flex items-center gap-1">
                                                        <Box className="w-3 h-3" />
                                                        {brand.products_count} urun
                                                    </span>
                                                )}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="space-y-8">
                        {sortedLetters.map(letter => (
                            <div key={letter}>
                                <div className="flex items-center gap-4 mb-3">
                                    <span className="w-10 h-10 bg-[#fbeede] text-[#b8651a] rounded-xl flex items-center justify-center font-bold text-lg">
                                        {letter}
                                    </span>
                                    <div className="h-px flex-1 bg-slate-200" />
                                </div>
                                <div className="space-y-2">
                                    {groupedBrands[letter].map(brand => (
                                        <Link
                                            key={brand.id}
                                            href={`/market/marka/${brand.slug}`}
                                            className="group flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-4 hover:border-[#fbeede] hover:shadow-md transition-all"
                                        >
                                            <BrandLogo brand={brand} size="sm" />
                                            <div className="flex-1 min-w-0">
                                                <h3 className="font-semibold text-slate-900 group-hover:text-[#b8651a] transition-colors">
                                                    {brand.name}
                                                </h3>
                                                {brand.description && (
                                                    <p className="text-sm text-slate-500 line-clamp-1">{brand.description}</p>
                                                )}
                                            </div>
                                            {brand.products_count !== undefined && (
                                                <span className="text-sm text-slate-500 flex items-center gap-1 flex-shrink-0">
                                                    <Box className="w-4 h-4" />
                                                    {brand.products_count} urun
                                                </span>
                                            )}
                                            <ArrowRight className="w-5 h-5 text-slate-400 group-hover:text-[#b8651a] group-hover:translate-x-1 transition-all flex-shrink-0" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
