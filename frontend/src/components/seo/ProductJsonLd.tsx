'use client';

import { JsonLd } from './JsonLd';

interface ProductJsonLdProps {
    name: string;
    description?: string;
    image?: string;
    brand?: string;
    barcode?: string;
    lowestPrice?: number;
    highestPrice?: number;
    offersCount?: number;
    inStock?: boolean;
    reviewCount?: number;
    averageRating?: number;
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

    if (lowestPrice !== undefined && lowestPrice > 0) {
        const offers: Record<string, unknown> = {
            '@type': 'AggregateOffer',
            priceCurrency: 'TRY',
            lowPrice: lowestPrice.toFixed(2),
            availability: inStock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        };

        if (highestPrice !== undefined && highestPrice > 0) {
            offers.highPrice = highestPrice.toFixed(2);
        }

        if (offersCount !== undefined && offersCount > 0) {
            offers.offerCount = offersCount;
        }

        data.offers = offers;
    }

    if (averageRating !== undefined && averageRating > 0 && reviewCount !== undefined && reviewCount > 0) {
        data.aggregateRating = {
            '@type': 'AggregateRating',
            ratingValue: averageRating.toFixed(1),
            reviewCount,
            bestRating: 5,
            worstRating: 1,
        };
    }

    return <JsonLd data={data} />;
}
