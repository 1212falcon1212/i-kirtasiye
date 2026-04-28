import type { Metadata } from 'next';
import { SearchClient } from './SearchClient';

export async function generateMetadata({
  searchParams,
}: {
  searchParams: Promise<{ q?: string }>;
}): Promise<Metadata> {
  const { q } = await searchParams;
  const query = q?.trim() || '';

  if (!query) {
    return {
      title: 'Urun Ara | i-kirtasiye',
      description: "i-kirtasiye'da binlerce urun arasinda arama yapin",
    };
  }

  return {
    title: `"${query}" Arama Sonuclari | i-kirtasiye`,
    description: `"${query}" icin arama sonuclari - i-kirtasiye'da en uygun fiyatlarla bulun`,
    openGraph: {
      title: `"${query}" Arama Sonuclari | i-kirtasiye`,
      description: `"${query}" icin arama sonuclari`,
      type: 'website',
      siteName: 'i-kirtasiye',
    },
    robots: {
      index: false,
      follow: true,
    },
  };
}

export default function SearchPage() {
  return <SearchClient />;
}
