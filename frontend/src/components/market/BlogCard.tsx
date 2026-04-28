"use client";

import { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Calendar, Clock, Eye } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import type { BlogPost } from "@/lib/api";

interface BlogCardProps {
  post: BlogPost;
  className?: string;
}

export function BlogCard({ post, className }: BlogCardProps) {
  const [imgError, setImgError] = useState(false);

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString("tr-TR", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  };

  return (
    <Link href={`/market/blog/${post.slug}`} className="block h-full">
      <article
        className={cn(
          "group relative bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-200 flex flex-col h-full overflow-hidden hover:shadow-md",
          className
        )}
      >
        {/* Image */}
        <div className="relative aspect-[16/9] overflow-hidden bg-slate-100 dark:bg-slate-700">
          {post.featured_image_url && !imgError ? (
            <Image
              src={post.featured_image_url}
              alt={post.title}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              onError={() => setImgError(true)}
              unoptimized
            />
          ) : (
            <div className="flex items-center justify-center h-full">
              <div className="text-4xl text-slate-300 dark:text-slate-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
              </div>
            </div>
          )}
          {post.category && (
            <Badge className="absolute top-3 left-3 bg-blue-600 hover:bg-blue-700 text-white text-xs">
              {post.category.name}
            </Badge>
          )}
          {post.is_featured && (
            <Badge className="absolute top-3 right-3 bg-amber-500 hover:bg-amber-600 text-white text-xs">
              One Cikan
            </Badge>
          )}
        </div>

        {/* Content */}
        <div className="flex flex-col flex-1 p-4">
          <h3 className="text-base font-semibold text-slate-900 dark:text-slate-100 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-2">
            {post.title}
          </h3>

          {post.excerpt && (
            <p className="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mb-3 flex-1">
              {post.excerpt}
            </p>
          )}

          {/* Meta */}
          <div className="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500 mt-auto pt-3 border-t border-slate-100 dark:border-slate-700">
            {post.published_at && (
              <span className="flex items-center gap-1">
                <Calendar className="w-3.5 h-3.5" />
                {formatDate(post.published_at)}
              </span>
            )}
            {post.read_time_minutes && (
              <span className="flex items-center gap-1">
                <Clock className="w-3.5 h-3.5" />
                {post.read_time_minutes} dk
              </span>
            )}
            {post.view_count !== undefined && post.view_count > 0 && (
              <span className="flex items-center gap-1 ml-auto">
                <Eye className="w-3.5 h-3.5" />
                {post.view_count}
              </span>
            )}
          </div>
        </div>
      </article>
    </Link>
  );
}
