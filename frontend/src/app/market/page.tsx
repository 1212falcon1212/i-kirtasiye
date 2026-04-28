import type { Metadata } from 'next';
import { MarketHomeClient } from './MarketHomeClient';

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

export default function MarketHomePage() {
  return <MarketHomeClient />;
}
