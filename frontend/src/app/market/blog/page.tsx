"use client";

import { useState, useEffect, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { BookOpen, ChevronLeft, ChevronRight, Search } from "lucide-react";
import { blogApi, BlogPost, BlogCategory } from "@/lib/api";
import { BlogCard } from "@/components/market/BlogCard";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export default function BlogPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [posts, setPosts] = useState<BlogPost[]>([]);
  const [categories, setCategories] = useState<BlogCategory[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
  });

  const activeCategory = searchParams.get("category") || "";
  const currentPage = Number(searchParams.get("page")) || 1;

  const loadData = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await blogApi.getPosts({
        category: activeCategory || undefined,
        page: currentPage,
        per_page: 12,
      });
      if (response.data) {
        setPosts(response.data.posts);
        setCategories(response.data.categories);
        setPagination({
          current_page: response.data.pagination.current_page,
          last_page: response.data.pagination.last_page,
          total: response.data.pagination.total,
        });
      }
    } catch {
      setError("Blog yazilari yuklenemedi.");
    } finally {
      setIsLoading(false);
    }
  }, [activeCategory, currentPage]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const handleCategoryChange = (slug: string) => {
    const params = new URLSearchParams();
    if (slug) params.set("category", slug);
    router.push(`/market/blog${params.toString() ? `?${params}` : ""}`);
  };

  const handlePageChange = (page: number) => {
    const params = new URLSearchParams();
    if (activeCategory) params.set("category", activeCategory);
    if (page > 1) params.set("page", String(page));
    router.push(`/market/blog${params.toString() ? `?${params}` : ""}`);
  };

  return (
    <div className="min-h-screen">
      {/* Hero Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-blue-900 to-slate-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
          <div className="text-center">
            <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm text-sm font-medium px-4 py-1.5 rounded-full mb-4">
              <BookOpen className="w-4 h-4" />
              Blog
            </div>
            <h1 className="text-3xl sm:text-4xl font-bold mb-3">
              Kirtasiye Dunyasindan Yazilar
            </h1>
            <p className="text-slate-300 max-w-2xl mx-auto text-sm sm:text-base">
              Sektor trendleri, urun rehberleri ve kirtasiye yonetimi hakkinda
              guncel bilgiler.
            </p>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Category Filters */}
        <div className="flex flex-wrap gap-2 mb-8">
          <button
            onClick={() => handleCategoryChange("")}
            className={cn(
              "px-4 py-2 rounded-full text-sm font-medium transition-colors",
              !activeCategory
                ? "bg-blue-600 text-white"
                : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
            )}
          >
            Tumu
          </button>
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => handleCategoryChange(cat.slug)}
              className={cn(
                "px-4 py-2 rounded-full text-sm font-medium transition-colors",
                activeCategory === cat.slug
                  ? "bg-blue-600 text-white"
                  : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
              )}
            >
              {cat.name}
              {cat.posts_count > 0 && (
                <span className="ml-1.5 text-xs opacity-70">
                  ({cat.posts_count})
                </span>
              )}
            </button>
          ))}
        </div>

        {/* Content */}
        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <Skeleton className="aspect-[16/9] w-full" />
                <div className="p-4 space-y-3">
                  <Skeleton className="h-5 w-3/4" />
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-4 w-2/3" />
                  <div className="flex gap-3 pt-3">
                    <Skeleton className="h-3 w-24" />
                    <Skeleton className="h-3 w-16" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : error ? (
          <div className="text-center py-16">
            <p className="text-red-500 mb-4">{error}</p>
            <Button onClick={loadData} variant="outline">
              Tekrar Dene
            </Button>
          </div>
        ) : posts.length === 0 ? (
          <div className="text-center py-16">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 mb-4">
              <Search className="w-8 h-8 text-slate-400" />
            </div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">
              Yazi Bulunamadi
            </h3>
            <p className="text-slate-500 dark:text-slate-400 mb-4">
              {activeCategory
                ? "Bu kategoride henuz yazi yok."
                : "Henuz blog yazisi eklenmemis."}
            </p>
            {activeCategory && (
              <Button
                onClick={() => handleCategoryChange("")}
                variant="outline"
              >
                Tum Yazilar
              </Button>
            )}
          </div>
        ) : (
          <>
            {/* Post Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {posts.map((post) => (
                <BlogCard key={post.id} post={post} />
              ))}
            </div>

            {/* Pagination */}
            {pagination.last_page > 1 && (
              <div className="flex items-center justify-center gap-2 mt-10">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handlePageChange(pagination.current_page - 1)}
                  disabled={pagination.current_page <= 1}
                >
                  <ChevronLeft className="w-4 h-4" />
                  Onceki
                </Button>
                <span className="text-sm text-slate-500 px-3">
                  {pagination.current_page} / {pagination.last_page}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handlePageChange(pagination.current_page + 1)}
                  disabled={
                    pagination.current_page >= pagination.last_page
                  }
                >
                  Sonraki
                  <ChevronRight className="w-4 h-4" />
                </Button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
