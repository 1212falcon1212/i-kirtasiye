<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheControl
{
    /**
     * Add Cache-Control headers to public API responses.
     * Enables browser and CDN caching for read-only endpoints.
     */
    public function handle(Request $request, Closure $next, string|int $maxAge = 300): Response
    {
        $maxAge = (int) $maxAge;
        $response = $next($request);

        // Only cache successful GET requests
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        $response->headers->set('Cache-Control', "public, max-age={$maxAge}, s-maxage={$maxAge}");
        $response->headers->set('Vary', 'Accept');

        return $response;
    }
}
