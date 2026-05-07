import type { Metadata } from 'next';
import { MarketHomeClient } from './MarketHomeClient';
import { serverFetch } from '@/lib/server-fetch';
import type { CmsHomepageResponse, ProductsResponse } from '@/lib/api';

export const metadata: Metadata = {
  title: 'Pazaryeri | i-kirtasiye - B2B Kırtasiye Tedarik Platformu',
  description:
    "Kirtasiye ihtiyaclarinizi en uygun fiyatlarla karsilayin. Binlerce urun, guvenilir tedarikciler ve hizli kargo ile i-kirtasiye'da.",
  openGraph: {
    title: 'i-kirtasiye - B2B Kırtasiye Tedarik Platformu',
    description:
      "Kirtasiye ihtiyaclarinizi en uygun fiyatlarla karsilayin. Binlerce urun, guvenilir tedarikciler.",
    type: 'website',
    siteName: 'i-kirtasiye',
    url: 'https://i-kirtasiye.com/market',
    images: [
      {
        url: 'https://i-kirtasiye.com/images/og-default.png',
        width: 1200,
        height: 630,
        alt: 'i-kirtasiye B2B Kırtasiye Platformu',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'i-kirtasiye - B2B Kırtasiye Tedarik Platformu',
    description:
      "Kirtasiye ihtiyaclarinizi en uygun fiyatlarla karsilayin.",
    images: ['https://i-kirtasiye.com/images/og-default.png'],
  },
  alternates: {
    canonical: 'https://i-kirtasiye.com/market',
  },
};

// Hero + ilk 40 ürünü server tarafında ISR ile prefetch ediyoruz.
// Backend `/cms/homepage` ve `/products` endpointleri public — auth gerektirmiyor.
// Layout `'use client'` AuthContext gate olsa bile page.tsx server component
// olarak kalabilir; child olarak client component (MarketHomeClient) render edilir.
export const revalidate = 120; // 2 dk ISR

export default async function MarketHomePage() {
  const [homepage, products] = await Promise.all([
    serverFetch<CmsHomepageResponse>('/cms/homepage', { revalidate: 120 }),
    serverFetch<ProductsResponse>(
      '/products?per_page=40&sort_by=offers_count',
      { revalidate: 120 },
    ),
  ]);

  return (
    <MarketHomeClient
      initialHomepage={homepage}
      initialProducts={products}
    />
  );
}
