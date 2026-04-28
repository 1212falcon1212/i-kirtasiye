"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import Image from "next/image";
import Link from "next/link";
import DOMPurify from "dompurify";
import {
  ArrowLeft,
  Calendar,
  Clock,
  Eye,
  Tag,
  ChevronRight,
  User,
} from "lucide-react";
import { blogApi, BlogPost } from "@/lib/api";
import { BlogCard } from "@/components/market/BlogCard";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function BlogDetailClient() {
  const params = useParams();
  const router = useRouter();
  const slug = params.slug as string;

  const [post, setPost] = useState<BlogPost | null>(null);
  const [relatedPosts, setRelatedPosts] = useState<BlogPost[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [imgError, setImgError] = useState(false);

  useEffect(() => {
    if (!slug) return;

    const loadPost = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await blogApi.getPost(slug);
        if (response.data) {
          setPost(response.data.post);
          setRelatedPosts(response.data.related_posts);
        } else {
          setError("Yazi bulunamadi.");
        }
      } catch {
        setError("Yazi yuklenirken bir hata olustu.");
      } finally {
        setIsLoading(false);
      }
    };

    loadPost();
  }, [slug]);

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString("tr-TR", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  };

  if (isLoading) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <Skeleton className="h-6 w-48" />
        <Skeleton className="h-10 w-3/4" />
        <div className="flex gap-4">
          <Skeleton className="h-4 w-32" />
          <Skeleton className="h-4 w-20" />
          <Skeleton className="h-4 w-20" />
        </div>
        <Skeleton className="aspect-[16/9] w-full rounded-lg" />
        <div className="space-y-3">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="h-4 w-full" />
          ))}
        </div>
      </div>
    );
  }

  if (error || !post) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <p className="text-red-500 mb-4">{error || "Yazi bulunamadi."}</p>
        <Button onClick={() => router.push("/market/blog")} variant="outline">
          <ArrowLeft className="w-4 h-4 mr-2" />
          Blog&#39;a Don
        </Button>
      </div>
    );
  }

  const sanitizedContent =
    typeof window !== "undefined" ? DOMPurify.sanitize(post.content || "") : post.content || "";

  return (
    <div className="min-h-screen">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
          <Link
            href="/market"
            className="hover:text-blue-600 transition-colors"
          >
            Market
          </Link>
          <ChevronRight className="w-3.5 h-3.5" />
          <Link
            href="/market/blog"
            className="hover:text-blue-600 transition-colors"
          >
            Blog
          </Link>
          {post.category && (
            <>
              <ChevronRight className="w-3.5 h-3.5" />
              <Link
                href={`/market/blog?category=${post.category.slug}`}
                className="hover:text-blue-600 transition-colors"
              >
                {post.category.name}
              </Link>
            </>
          )}
          <ChevronRight className="w-3.5 h-3.5" />
          <span className="text-slate-900 dark:text-slate-200 truncate max-w-[200px]">
            {post.title}
          </span>
        </nav>

        {/* Category Badge */}
        {post.category && (
          <Link href={`/market/blog?category=${post.category.slug}`}>
            <Badge className="bg-blue-600 hover:bg-blue-700 text-white mb-4">
              {post.category.name}
            </Badge>
          </Link>
        )}

        {/* Title */}
        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 dark:text-slate-100 mb-4">
          {post.title}
        </h1>

        {/* Meta Info */}
        <div className="flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400 mb-6">
          {post.author && (
            <span className="flex items-center gap-1.5">
              <User className="w-4 h-4" />
              {post.author.name}
            </span>
          )}
          {post.published_at && (
            <span className="flex items-center gap-1.5">
              <Calendar className="w-4 h-4" />
              {formatDate(post.published_at)}
            </span>
          )}
          {post.read_time_minutes && (
            <span className="flex items-center gap-1.5">
              <Clock className="w-4 h-4" />
              {post.read_time_minutes} dk okuma
            </span>
          )}
          {post.view_count !== undefined && post.view_count > 0 && (
            <span className="flex items-center gap-1.5">
              <Eye className="w-4 h-4" />
              {post.view_count} goruntulenme
            </span>
          )}
        </div>

        {/* Featured Image */}
        {post.featured_image_url && !imgError && (
          <div className="relative aspect-[16/9] rounded-xl overflow-hidden mb-8">
            <Image
              src={post.featured_image_url}
              alt={post.title}
              fill
              className="object-cover"
              onError={() => setImgError(true)}
              unoptimized
            />
          </div>
        )}

        {/* Content */}
        <article
          className="prose prose-slate dark:prose-invert max-w-none mb-10
            prose-headings:text-slate-900 dark:prose-headings:text-slate-100
            prose-h2:text-2xl prose-h2:font-bold prose-h2:mt-8 prose-h2:mb-4
            prose-h3:text-xl prose-h3:font-semibold prose-h3:mt-6 prose-h3:mb-3
            prose-p:text-slate-600 dark:prose-p:text-slate-300 prose-p:leading-relaxed prose-p:mb-4
            prose-li:text-slate-600 dark:prose-li:text-slate-300
            prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline
            prose-strong:text-slate-900 dark:prose-strong:text-slate-100
            prose-img:rounded-lg"
          dangerouslySetInnerHTML={{ __html: sanitizedContent }}
        />

        {/* Tags */}
        {post.tags && post.tags.length > 0 && (
          <div className="flex flex-wrap items-center gap-2 mb-10 pt-6 border-t border-slate-200 dark:border-slate-700">
            <Tag className="w-4 h-4 text-slate-400" />
            {post.tags.map((tag) => (
              <Badge
                key={tag}
                variant="secondary"
                className="text-xs"
              >
                {tag}
              </Badge>
            ))}
          </div>
        )}

        {/* Back Button */}
        <div className="mb-12">
          <Button
            onClick={() => router.push("/market/blog")}
            variant="outline"
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Tum Yazilara Don
          </Button>
        </div>

        {/* Related Posts */}
        {relatedPosts.length > 0 && (
          <section className="border-t border-slate-200 dark:border-slate-700 pt-10">
            <h2 className="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">
              Ilgili Yazilar
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {relatedPosts.map((related) => (
                <BlogCard key={related.id} post={related} />
              ))}
            </div>
          </section>
        )}
      </div>
    </div>
  );
}
