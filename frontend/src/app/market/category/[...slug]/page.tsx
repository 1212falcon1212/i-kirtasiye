import type { Metadata } from 'next';
import { serverFetch } from '@/lib/server-fetch';
import { CategoryClient } from './CategoryClient';

interface CategoryData {
  category?: {
    id: number;
    name: string;
    slug: string;
    description?: string;
  };
  breadcrumb?: Array<{
    id: number;
    name: string;
    slug: string;
  }>;
}

function formatSlugToName(slug: string): string {
  const words = slug.replace(/-/g, ' ').split(' ');
  return words
    .map((word) => {
      if (!word) return '';
      return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    })
    .join(' ');
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string[] }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const fullSlug = slug.join('/');
  const lastSlug = slug[slug.length - 1];

  const data = await serverFetch<CategoryData>(`/categories/slug/${fullSlug}`, {
    revalidate: 300,
  });

  const categoryName = data?.category?.name || formatSlugToName(lastSlug || '');
  const description =
    data?.category?.description ||
    `${categoryName} urunlerini en uygun fiyatlarla i-kirtasiye'da bulun`;

  return {
    title: `${categoryName} | i-kirtasiye`,
    description,
    openGraph: {
      title: `${categoryName} | i-kirtasiye`,
      description,
      type: 'website',
      siteName: 'i-kirtasiye',
      url: `https://i-kirtasiye.com/market/category/${fullSlug}`,
    },
    twitter: {
      card: 'summary',
      title: `${categoryName} | i-kirtasiye`,
      description,
    },
    alternates: {
      canonical: `https://i-kirtasiye.com/market/category/${fullSlug}`,
    },
  };
}

export default function MarketCategoryPage() {
  return <CategoryClient />;
}
