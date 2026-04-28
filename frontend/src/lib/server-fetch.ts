/**
 * Server-side fetch utility for metadata generation.
 * This is used in server components (generateMetadata) where
 * the browser-based ApiClient (with localStorage) is not available.
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

interface ServerFetchOptions {
  revalidate?: number;
}

export async function serverFetch<T>(
  endpoint: string,
  options: ServerFetchOptions = {}
): Promise<T | null> {
  try {
    const response = await fetch(`${API_URL}${endpoint}`, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      next: { revalidate: options.revalidate ?? 300 },
    });

    if (!response.ok) {
      return null;
    }

    return await response.json() as T;
  } catch {
    return null;
  }
}
