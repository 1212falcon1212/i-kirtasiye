"use client";

import Link from "next/link";
import Image from "next/image";
import { ArrowRight, Star } from "lucide-react";
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from "@/components/ui/carousel";
import { ProductCard } from "./ProductCard";
import { GridProductCard } from "./GridProductCard";
import { cn } from "@/lib/utils";

interface ProductCarouselProps {
    title: string;
    products: any[];
    linkUrl?: string;
}

export function ProductCarousel({
    title,
    products,
    linkUrl,
}: ProductCarouselProps) {
    if (!products || products.length === 0) return null;

    return (
        <section className="py-4">
            <div className="flex items-center justify-between mb-4 px-1">
                <h2 className="text-xl font-bold text-[#1a1a1a] tracking-tight">
                    {title}
                </h2>
                {linkUrl && (
                    <Link
                        href={linkUrl}
                        className="group flex items-center gap-1.5 text-sm font-semibold border-[1.5px] border-[#fbeede] text-[#b8651a] hover:bg-[#b8651a] hover:text-white rounded-[10px] px-4 py-1.5 transition-colors"
                    >
                        Tümünü Gör
                        <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                )}
            </div>

            <Carousel
                opts={{
                    align: "start",
                    loop: false,
                }}
                className="w-full relative group"
            >
                <CarouselContent className="-ml-4 pb-4">
                    {products.map((product, index) => (
                        <CarouselItem
                            key={`${product.id}-${index}`}
                            className="pl-4 basis-1/2 sm:basis-1/3 md:basis-1/4 lg:basis-1/5 xl:basis-1/6"
                        >
                            <GridProductCard product={product} />
                        </CarouselItem>
                    ))}
                </CarouselContent>
                <CarouselPrevious className="left-0 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-white shadow-sm border-[#f0eceb]" />
                <CarouselNext className="right-0 translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-white shadow-sm border-[#f0eceb]" />
            </Carousel>
        </section>
    );
}

// Grid variant - Farmazon style: single white card container with products inside
export function ProductGrid({
    title,
    products,
    linkUrl,
    icon,
    columns = 5,
    rows = 2,
    showRanking = false,
}: {
    title: string;
    products: any[];
    linkUrl?: string;
    icon?: React.ReactNode;
    columns?: 2 | 3 | 4 | 5 | 6;
    rows?: 1 | 2 | 3 | 4 | 5;
    showRanking?: boolean;
}) {
    if (!products || products.length === 0) return null;

    // Limit products based on columns x rows
    const maxProducts = columns * rows;
    const displayProducts = products.slice(0, maxProducts);

    const formatPrice = (price?: number | string) => {
        const numPrice = typeof price === 'string' ? parseFloat(price) : price;
        if (!numPrice || isNaN(numPrice)) return { whole: "---", decimal: "00" };
        const [whole, decimal] = numPrice.toFixed(2).split(".");
        return { whole, decimal };
    };

    const gridCols = {
        2: "grid-cols-1 lg:grid-cols-2",
        3: "grid-cols-1 lg:grid-cols-2",
        4: "grid-cols-1 lg:grid-cols-2",
        5: "grid-cols-1 lg:grid-cols-2",
        6: "grid-cols-1 lg:grid-cols-2",
    };

    return (
        <section className="py-4">
            {/* Full-width background container */}
            <div className="bg-white dark:bg-[#1a1a1a] -mx-4 sm:-mx-7 px-4 sm:px-7 border-y border-[#f0eceb] dark:border-[#2a2a2a] py-12">
                {/* Header inside the card */}
                <div className="flex items-center justify-between pb-6 border-b border-[#f0eceb] dark:border-[#2a2a2a]">
                    <h2 className="text-2xl font-bold text-[#1a1a1a] dark:text-white tracking-tight">{title}</h2>
                    {linkUrl && (
                        <Link
                            href={linkUrl}
                            className="group flex items-center gap-1.5 text-sm font-semibold border-[1.5px] border-[#fbeede] text-[#b8651a] hover:bg-[#b8651a] hover:text-white rounded-[10px] px-4 py-1.5 transition-colors"
                        >
                            Tümünü Gör
                            <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    )}
                </div>

                {/* Products grid inside the card */}
                <div className={cn("grid gap-5 mt-6", gridCols[columns])}>
                    {displayProducts.map((product, index) => {
                        const price = formatPrice(product.lowest_price);
                        const offersCount = Number(product.offers_count) || 0;

                        return (
                            <Link
                                key={`${product.id}-${index}`}
                                href={`/market/product/${product.id}`}
                                className="group relative flex flex-row rounded-3xl border border-[#f0eceb] dark:border-[#2a2a2a] overflow-hidden hover:border-[#fbeede] dark:hover:border-[#b8651a] transition-colors"
                            >
                                {/* Left: Product Image */}
                                <div className="w-[130px] sm:w-[210px] md:w-[240px] h-[150px] sm:h-[180px] md:h-[210px] flex-shrink-0 bg-white dark:bg-[#1f1f1f] relative flex items-center justify-center">
                                    {(product.image_url || product.image) ? (
                                        <Image
                                            src={(product.image_url || product.image)!}
                                            alt={product.name}
                                            fill
                                            sizes="240px"
                                            className="object-contain p-4 group-hover:scale-105 transition-transform duration-150"
                                        />
                                    ) : (
                                        <div className="text-6xl opacity-30">💊</div>
                                    )}
                                </div>

                                {/* Right: Product Info */}
                                <div className="flex-1 min-w-0 p-4 sm:p-6 flex flex-col justify-center">
                                    {product.brand && (
                                        <p className="text-[12px] font-extrabold text-[#b8651a] dark:text-[#fbeede] uppercase tracking-wider mb-2 truncate">
                                            {product.brand}
                                        </p>
                                    )}
                                    <h3 className="text-sm sm:text-base font-semibold text-[#1a1a1a] dark:text-slate-200 line-clamp-2 mb-3 group-hover:text-[#b8651a] dark:group-hover:text-[#fbeede] transition-colors">
                                        {product.name}
                                    </h3>
                                    <div className="mb-2">
                                        <span className="text-xl sm:text-2xl font-black text-[#1a1a1a] dark:text-white">
                                            {price.whole}
                                        </span>
                                        <span className="text-sm sm:text-base font-medium text-[#1a1a1a] dark:text-white">
                                            ,{price.decimal} TL
                                        </span>
                                    </div>
                                    {offersCount > 0 && (
                                        <p className="text-sm text-[#6b7280]">
                                            <span className="font-semibold text-[#b8651a]">{offersCount} ilan</span>
                                        </p>
                                    )}
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

// Horizontal scrolling list for mobile-first design
export function ProductScrollList({
    title,
    products,
    linkUrl,
    size = "md",
}: {
    title: string;
    products: any[];
    linkUrl?: string;
    size?: "sm" | "md" | "lg";
}) {
    if (!products || products.length === 0) return null;

    const formatPrice = (price?: number) => {
        if (!price) return "---";
        return new Intl.NumberFormat("tr-TR", {
            style: "currency",
            currency: "TRY",
        }).format(price);
    };

    const sizeStyles = {
        sm: { card: "w-32", image: "h-24", text: "text-xs" },
        md: { card: "w-40", image: "h-32", text: "text-sm" },
        lg: { card: "w-48", image: "h-40", text: "text-base" },
    };

    const styles = sizeStyles[size];

    return (
        <section className="py-4">
            <div className="flex items-center justify-between mb-4 px-1">
                <h3 className="text-lg font-bold text-[#1a1a1a]">{title}</h3>
                {linkUrl && (
                    <Link href={linkUrl} className="text-sm text-[#b8651a] font-medium">
                        Tümünü Gör
                    </Link>
                )}
            </div>

            <div className="relative -mx-4 px-4">
                <div className="flex gap-3 overflow-x-auto pb-4 scrollbar-hide">
                    {products.map((product) => (
                        <Link
                            key={product.id}
                            href={`/market/product/${product.id}`}
                            className={cn("flex-shrink-0 group", styles.card)}
                        >
                            <div
                                className={cn(
                                    "relative rounded-lg overflow-hidden bg-[#fbeede] mb-2",
                                    styles.image
                                )}
                            >
                                {(product.image_url || product.image) ? (
                                    <Image
                                        src={(product.image_url || product.image)!}
                                        alt={product.name}
                                        fill
                                        sizes="160px"
                                        className="object-cover group-hover:scale-105 transition-transform"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-3xl opacity-50">
                                        💊
                                    </div>
                                )}
                            </div>
                            <p
                                className={cn(
                                    "font-medium text-[#1a1a1a] line-clamp-2 group-hover:text-[#b8651a] transition-colors",
                                    styles.text
                                )}
                            >
                                {product.name}
                            </p>
                            <p className="text-sm font-bold text-[#b8651a] mt-1">
                                {formatPrice(product.lowest_price)}
                            </p>
                        </Link>
                    ))}
                </div>

                {/* Fade edges */}
                <div className="absolute right-0 top-0 bottom-4 w-8 bg-white/80 pointer-events-none" />
            </div>
        </section>
    );
}

// Featured products with larger cards
export function FeaturedProducts({
    title = "Öne Çıkan Ürünler",
    products,
    linkUrl,
}: {
    title?: string;
    products: any[];
    linkUrl?: string;
}) {
    if (!products || products.length === 0) return null;

    const formatPrice = (price?: number) => {
        if (!price) return "---";
        return new Intl.NumberFormat("tr-TR", {
            style: "currency",
            currency: "TRY",
        }).format(price);
    };

    // First product is featured (large), rest are smaller
    const [featured, ...rest] = products.slice(0, 5);

    return (
        <section className="py-4">
            <div className="flex items-center justify-between mb-4 px-1">
                <h2 className="text-xl font-bold text-[#1a1a1a] tracking-tight">{title}</h2>
                {linkUrl && (
                    <Link
                        href={linkUrl}
                        className="group flex items-center gap-1.5 text-sm font-semibold border-[1.5px] border-[#fbeede] text-[#b8651a] hover:bg-[#b8651a] hover:text-white rounded-[10px] px-4 py-1.5 transition-colors"
                    >
                        Tümünü Gör
                        <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                )}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {/* Featured Product (Large) */}
                <Link
                    href={`/market/product/${featured.id}`}
                    className="group relative bg-[#fbeede] rounded-2xl p-6 overflow-hidden border border-[#f0eceb] hover:border-[#fbeede] hover:shadow-md transition-all"
                >
                    <div className="relative flex flex-col md:flex-row items-center gap-6">
                        {/* Image */}
                        <div className="relative w-48 h-48 rounded-lg overflow-hidden bg-white shadow-sm flex-shrink-0">
                            {featured.image ? (
                                <Image
                                    src={featured.image!}
                                    alt={featured.name}
                                    fill
                                    sizes="192px"
                                    className="object-contain p-4 group-hover:scale-105 transition-transform duration-150"
                                />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center text-6xl opacity-50">
                                    💊
                                </div>
                            )}
                        </div>

                        {/* Info */}
                        <div className="flex-1 text-center md:text-left">
                            <span className="inline-block px-3 py-1 bg-accent-soft text-[#b8651a] text-xs font-bold rounded-sm mb-3">
                                ÖNE ÇIKAN
                            </span>
                            {featured.brand && (
                                <p className="text-sm text-slate-500 font-medium mb-1">
                                    {featured.brand}
                                </p>
                            )}
                            <h3 className="text-xl font-bold text-[#1a1a1a] mb-2 group-hover:text-[#b8651a] transition-colors">
                                {featured.name}
                            </h3>
                            <div className="flex items-baseline gap-2 justify-center md:justify-start mb-4">
                                <span className="text-3xl font-bold text-[#b8651a]">
                                    {formatPrice(featured.lowest_price)}
                                </span>
                                {featured.offers_count && Number(featured.offers_count) > 0 && (
                                    <span className="text-sm text-slate-500">
                                        {featured.offers_count} satıcı
                                    </span>
                                )}
                            </div>
                            <span className="inline-flex items-center gap-2 text-sm font-medium text-[#b8651a] group-hover:gap-3 transition-all">
                                Detayları Gör
                                <ArrowRight className="w-4 h-4" />
                            </span>
                        </div>
                    </div>
                </Link>

                {/* Rest of Products (Grid) */}
                <div className="grid grid-cols-2 gap-4">
                    {rest.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </div>
        </section>
    );
}
