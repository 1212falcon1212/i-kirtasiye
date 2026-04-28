import type { Metadata } from 'next';
import { serverFetch } from '@/lib/server-fetch';
import { BlogDetailClient } from './BlogDetailClient';

interface BlogPostData {
  post: {
    id: number;
    title: string;
    slug: string;
    excerpt?: string;
    meta_title?: string;
    meta_description?: string;
    featured_image_url?: string;
    author?: {
      name: string;
    };
    published_at: string;
    category?: {
      name: string;
      slug: string;
    };
  };
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const data = await serverFetch<BlogPostData>(`/blog/posts/${slug}`, {
    revalidate: 300,
  });
  const post = data?.post;

  if (!post) {
    return {
      title: 'Yazi Bulunamadi | i-kirtasiye Blog',
      description: 'Aradiginiz blog yazisi bulunamadi.',
    };
  }

  const title = post.meta_title || `${post.title} | i-kirtasiye Blog`;
  const description =
    post.meta_description ||
    post.excerpt ||
    `${post.title} - i-kirtasiye Blog'da okuyun`;
  const imageUrl =
    post.featured_image_url || 'https://i-kirtasiye.com/images/og-default.png';

  return {
    title,
    description,
    openGraph: {
      title: post.title,
      description,
      type: 'article',
      siteName: 'i-kirtasiye',
      url: `https://i-kirtasiye.com/market/blog/${slug}`,
      images: [{ url: imageUrl, width: 1200, height: 630, alt: post.title }],
      ...(post.published_at && {
        publishedTime: post.published_at,
      }),
      ...(post.author?.name && {
        authors: [post.author.name],
      }),
    },
    twitter: {
      card: 'summary_large_image',
      title: post.title,
      description,
      images: [imageUrl],
    },
    alternates: {
      canonical: `https://i-kirtasiye.com/market/blog/${slug}`,
    },
  };
}

export default function BlogDetailPage() {
  return <BlogDetailClient />;
}
