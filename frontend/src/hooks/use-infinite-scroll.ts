import { useEffect, useRef, useCallback } from 'react';

interface UseInfiniteScrollOptions {
    /** Whether more items are available */
    hasMore: boolean;
    /** Whether currently loading */
    isLoading: boolean;
    /** Callback to load next page */
    onLoadMore: () => void;
    /** Distance from bottom (px) to trigger loading */
    threshold?: number;
    /** Whether the hook is enabled */
    enabled?: boolean;
}

/**
 * Infinite scroll hook - triggers onLoadMore when user scrolls near the bottom.
 * Uses IntersectionObserver for performance (no scroll event listeners).
 */
export function useInfiniteScroll({
    hasMore,
    isLoading,
    onLoadMore,
    threshold = 400,
    enabled = true,
}: UseInfiniteScrollOptions) {
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    const handleIntersect = useCallback(
        (entries: IntersectionObserverEntry[]) => {
            const [entry] = entries;
            if (entry.isIntersecting && hasMore && !isLoading && enabled) {
                onLoadMore();
            }
        },
        [hasMore, isLoading, onLoadMore, enabled]
    );

    useEffect(() => {
        const sentinel = sentinelRef.current;
        if (!sentinel || !enabled) return;

        const observer = new IntersectionObserver(handleIntersect, {
            rootMargin: `0px 0px ${threshold}px 0px`,
        });

        observer.observe(sentinel);
        return () => observer.disconnect();
    }, [handleIntersect, threshold, enabled]);

    return { sentinelRef };
}
