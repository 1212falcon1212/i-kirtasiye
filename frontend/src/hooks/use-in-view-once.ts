'use client';

import { useEffect, useRef, useState } from 'react';

// NOTE: This project's tsconfig combines `dom` + `webworker` libs, which strips
// some DOM globals from the type-resolver context. We re-declare the bits we
// need rather than touching the shared tsconfig.
declare const IntersectionObserver: {
    new (
        callback: (entries: IntersectionObserverEntry[]) => void,
        options?: { rootMargin?: string; threshold?: number | number[] },
    ): IntersectionObserver;
    prototype: IntersectionObserver;
};
interface IntersectionObserver {
    observe(target: Element): void;
    disconnect(): void;
}
interface IntersectionObserverEntry {
    isIntersecting: boolean;
}

interface UseInViewOnceOptions {
    /** Pixel margin around the root used to expand the intersection bounds. */
    rootMargin?: string;
    /** When true, hook acts as already-visible (skip observation). */
    initialInView?: boolean;
}

/**
 * Returns `[ref, inView]` where `inView` flips to `true` the first time the
 * referenced element intersects the viewport (with the given root margin) and
 * stays `true` thereafter. Used to defer mounting/fetching of below-the-fold
 * sections until the user is about to scroll them into view.
 */
export function useInViewOnce<T extends Element = HTMLDivElement>({
    rootMargin = '200px 0px',
    initialInView = false,
}: UseInViewOnceOptions = {}) {
    const ref = useRef<T | null>(null);
    const [inView, setInView] = useState<boolean>(initialInView);

    useEffect(() => {
        if (initialInView) return;
        const node = ref.current;
        if (!node) return;
        if (typeof IntersectionObserver === 'undefined') {
            // Safari/old browsers — eager fallback
            setInView(true);
            return;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        setInView(true);
                        observer.disconnect();
                        break;
                    }
                }
            },
            { rootMargin },
        );
        observer.observe(node);
        return () => observer.disconnect();
    }, [rootMargin, initialInView]);

    return [ref, inView] as const;
}
