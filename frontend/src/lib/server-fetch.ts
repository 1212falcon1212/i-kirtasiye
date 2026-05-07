/**
 * Server-side fetch utility for React Server Components.
 *
 * Used in `page.tsx` async server components to prefetch public API data
 * (e.g. `/cms/homepage`, `/products`) so initial HTML ships with the data
 * baked in — no client-side spinner on first paint.
 *
 * Notes:
 * - Browser-side ApiClient depends on `localStorage` for tokens and is not
 *   safe to call from server runtimes. This helper is a clean alternative.
 * - Honours Next.js fetch cache semantics (`next.revalidate`) for ISR.
 * - Returns `null` on any non-2xx or network failure so callers can fall
 *   back to client-side fetching gracefully.
 *
 * Env vars (in priority order):
 *   1. `API_INTERNAL_URL`  — server-only override (e.g. `http://backend:8000/api`
 *      in a docker-compose setup, avoids round-tripping the public domain)
 *   2. `NEXT_PUBLIC_API_URL` — same URL the browser uses (fallback)
 *   3. `http://localhost:8000/api` — last-resort local dev default
 */

const SERVER_API_URL =
  process.env.API_INTERNAL_URL ||
  process.env.NEXT_PUBLIC_API_URL ||
  'http://localhost:8000/api';

interface ServerFetchOptions {
  /** Seconds to cache the response (Next.js ISR). Defaults to 300s (5m). */
  revalidate?: number;
  /** Optional cache tags for on-demand revalidation via `revalidateTag`. */
  tags?: string[];
}

export async function serverFetch<T>(
  endpoint: string,
  options: ServerFetchOptions = {},
): Promise<T | null> {
  try {
    const response = await fetch(`${SERVER_API_URL}${endpoint}`, {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      next: {
        revalidate: options.revalidate ?? 300,
        ...(options.tags ? { tags: options.tags } : {}),
      },
    });

    if (!response.ok) {
      return null;
    }

    return (await response.json()) as T;
  } catch {
    return null;
  }
}
