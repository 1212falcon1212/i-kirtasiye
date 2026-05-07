"use client";

import Link from "next/link";
import { ArrowRight } from "lucide-react";

interface CategoryItem {
    id: number;
    name: string;
    slug: string;
    icon?: string;
    image?: string;
    products_count?: number;
}

interface CategoryShowcaseProps {
    categories?: CategoryItem[];
    title?: string;
    subtitle?: string;
}

// Fallback categories with images
const FALLBACK_CATEGORIES: CategoryItem[] = [
    {
        id: 1,
        name: "Defter",
        slug: "defter",
        image: "https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=600&h=400&fit=crop",
        products_count: 248,
    },
    {
        id: 2,
        name: "Kalem",
        slug: "kalem",
        image: "https://images.unsplash.com/photo-1455390582262-044cdead277a?w=600&h=400&fit=crop",
        products_count: 312,
    },
    {
        id: 3,
        name: "Ofis Malzemeleri",
        slug: "ofis-malzemeleri",
        image: "https://images.unsplash.com/photo-1497032628192-86f99bcd76bc?w=600&h=400&fit=crop",
        products_count: 186,
    },
    {
        id: 4,
        name: "Sanat & Hobi",
        slug: "sanat-hobi",
        image: "https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=600&h=400&fit=crop",
        products_count: 124,
    },
    {
        id: 5,
        name: "Okul Çantaları",
        slug: "okul-cantalari",
        image: "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&h=400&fit=crop",
        products_count: 89,
    },
    {
        id: 6,
        name: "Kitap & Yayın",
        slug: "kitap-yayin",
        image: "https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=600&h=400&fit=crop",
        products_count: 156,
    },
];

export function CategoryShowcase({
    categories,
    title = "Kategoriler",
    subtitle = "İhtiyacınız olan ürünlere hızla ulaşın",
}: CategoryShowcaseProps) {
    const displayCategories = categories && categories.length > 0
        ? categories.map((cat, index) => ({
            ...cat,
            image: cat.image || FALLBACK_CATEGORIES[index % FALLBACK_CATEGORIES.length]?.image,
        }))
        : FALLBACK_CATEGORIES;

    return (
        <section className="py-10">
            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h2 className="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">
                        {title}
                    </h2>
                    {subtitle && (
                        <p className="text-slate-500 mt-1">{subtitle}</p>
                    )}
                </div>
                <Link
                    href="/market/categories"
                    className="group hidden sm:flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-[#ea580c] transition-colors duration-150"
                >
                    Tüm Kategoriler
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>

            {/* Category Grid */}
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                {displayCategories.slice(0, 6).map((category) => (
                    <Link
                        key={category.id}
                        href={`/market/category/${category.slug}`}
                        className="group relative aspect-[4/5] rounded-lg overflow-hidden"
                    >
                        {/* Background Image */}
                        <div
                            className="absolute inset-0 bg-cover bg-center"
                            style={{
                                backgroundImage: `url(${category.image})`,
                            }}
                        />

                        {/* Unified dark overlay for text readability */}
                        <div className="absolute inset-0 bg-slate-900/60 group-hover:bg-slate-900/70 transition-colors duration-150" />

                        {/* Content */}
                        <div className="absolute inset-0 p-4 flex flex-col justify-end">
                            {/* Product Count Badge */}
                            {category.products_count !== undefined && (
                                <div className="absolute top-3 right-3">
                                    <span className="inline-flex items-center px-2 py-1 rounded-md bg-white/20 text-white text-xs font-bold">
                                        {category.products_count} Ürün
                                    </span>
                                </div>
                            )}

                            {/* Category Name */}
                            <h3 className="text-lg font-bold text-white mb-1">
                                {category.name}
                            </h3>

                            {/* Arrow indicator */}
                            <div className="flex items-center gap-1 text-white/80 text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                <span>Keşfet</span>
                                <ArrowRight className="w-4 h-4" />
                            </div>
                        </div>

                        {/* Hover Border Effect */}
                        <div className="absolute inset-0 rounded-lg border border-white/0 group-hover:border-white/20 transition-colors duration-150" />
                    </Link>
                ))}
            </div>

            {/* Mobile See All Button */}
            <div className="mt-6 sm:hidden">
                <Link
                    href="/market/categories"
                    className="flex items-center justify-center gap-2 w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-md transition-colors duration-150"
                >
                    Tüm Kategorileri Gör
                    <ArrowRight className="w-4 h-4" />
                </Link>
            </div>
        </section>
    );
}

// Alternative: Horizontal scrolling category strip for smaller areas
export function CategoryStrip({
    categories,
}: {
    categories?: CategoryItem[];
}) {
    const displayCategories = categories && categories.length > 0
        ? categories
        : FALLBACK_CATEGORIES;

    return (
        <div className="relative">
            <div className="flex gap-3 overflow-x-auto pb-4 scrollbar-hide -mx-4 px-4">
                {displayCategories.map((category, index) => (
                    <Link
                        key={category.id}
                        href={`/market/category/${category.slug}`}
                        className="flex-shrink-0 group"
                    >
                        <div className="relative w-24 h-24 rounded-lg overflow-hidden mb-2">
                            <div
                                className="absolute inset-0 bg-cover bg-center"
                                style={{
                                    backgroundImage: `url(${category.image || FALLBACK_CATEGORIES[index % FALLBACK_CATEGORIES.length]?.image})`,
                                }}
                            />
                            <div className="absolute inset-0 bg-slate-900/50 group-hover:bg-slate-900/60 transition-colors duration-150" />
                            <div className="absolute inset-0 flex items-center justify-center">
                                <span className="text-3xl">{category.icon || "✏️"}</span>
                            </div>
                        </div>
                        <p className="text-xs font-medium text-slate-700 text-center truncate w-24">
                            {category.name}
                        </p>
                    </Link>
                ))}
            </div>

            {/* Fade edge */}
            <div className="absolute right-0 top-0 bottom-4 w-8 bg-gradient-to-l from-white to-transparent pointer-events-none" />
        </div>
    );
}
