'use client';

import { JsonLd } from './JsonLd';

interface ProductJsonLdProps {
    name: string;
    description?: string;
    image?: string;
    brand?: string;
    barcode?: string;
    lowestPrice?: number | string | null;
    highestPrice?: number | string | null;
    offersCount?: number | string | null;
    inStock?: boolean;
    reviewCount?: number | string | null;
    averageRating?: number | string | null;
}

function toFiniteNumber(value: number | string | null | undefined): number | null {
    if (value === null || value === undefined || value === '') return null;
    const num = typeof value === 'number' ? value : Number(value);
    return Number.isFinite(num) ? num : null;
}

export function ProductJsonLd({
    name,
    description,
    image,
    brand,
    barcode,
    lowestPrice,
    highestPrice,
    offersCount,
    inStock = true,
    reviewCount,
    averageRating,
}: ProductJsonLdProps) {
    const data: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name,
    };

    if (description) {
        data.description = description;
    }

    if (image) {
        data.image = image;
    }

    if (brand) {
        data.brand = {
            '@type': 'Brand',
            name: brand,
        };
    }

    if (barcode) {
        data.sku = barcode;
        data.gtin13 = barcode.length === 13 ? barcode : undefined;
    }

    const lowest = toFiniteNumber(lowestPrice);
    if (lowest !== null && lowest > 0) {
        const offers: Record<string, unknown> = {
            '@type': 'AggregateOffer',
            priceCurrency: 'TRY',
            lowPrice: lowest.toFixed(2),
            availability: inStock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        };

        const highest = toFiniteNumber(highestPrice);
        if (highest !== null && highest > 0) {
            offers.highPrice = highest.toFixed(2);
        }

        const count = toFiniteNumber(offersCount);
        if (count !== null && count > 0) {
            offers.offerCount = count;
        }

        data.offers = offers;
    }

    const rating = toFiniteNumber(averageRating);
    const reviews = toFiniteNumber(reviewCount);
    if (rating !== null && rating > 0 && reviews !== null && reviews > 0) {
        data.aggregateRating = {
            '@type': 'AggregateRating',
            ratingValue: rating.toFixed(1),
            reviewCount: reviews,
            bestRating: 5,
            worstRating: 1,
        };
    }

    return <JsonLd data={data} />;
}
