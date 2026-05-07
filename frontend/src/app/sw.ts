/// <reference lib="webworker" />

import { defaultCache } from '@serwist/next/worker';
import type { PrecacheEntry, SerwistGlobalConfig } from 'serwist';
import {
    CacheFirst,
    ExpirationPlugin,
    NetworkFirst,
    Serwist,
    StaleWhileRevalidate,
} from 'serwist';

declare global {
    interface WorkerGlobalScope extends SerwistGlobalConfig {
        __SW_MANIFEST: (PrecacheEntry | string)[] | undefined;
    }
}

declare const self: ServiceWorkerGlobalScope;

const serwist = new Serwist({
    precacheEntries: self.__SW_MANIFEST,
    skipWaiting: true,
    clientsClaim: true,
    navigationPreload: true,
    runtimeCaching: [
        // Google Fonts (CacheFirst, 365 gün)
        {
            matcher: ({ url }) =>
                url.origin === 'https://fonts.googleapis.com' ||
                url.origin === 'https://fonts.gstatic.com',
            handler: new CacheFirst({
                cacheName: 'google-fonts',
                plugins: [
                    new ExpirationPlugin({
                        maxEntries: 4,
                        maxAgeSeconds: 365 * 24 * 60 * 60,
                    }),
                ],
            }),
        },
        // Statik font dosyaları (SWR, 7 gün)
        {
            matcher: ({ url }) => /\.(?:eot|otf|ttc|ttf|woff|woff2)$/i.test(url.pathname),
            handler: new StaleWhileRevalidate({
                cacheName: 'static-font-assets',
                plugins: [
                    new ExpirationPlugin({
                        maxEntries: 4,
                        maxAgeSeconds: 7 * 24 * 60 * 60,
                    }),
                ],
            }),
        },
        // Statik görseller (SWR, 24 saat)
        {
            matcher: ({ url }) =>
                /\.(?:jpg|jpeg|gif|png|svg|ico|webp)$/i.test(url.pathname),
            handler: new StaleWhileRevalidate({
                cacheName: 'static-image-assets',
                plugins: [
                    new ExpirationPlugin({
                        maxEntries: 64,
                        maxAgeSeconds: 24 * 60 * 60,
                    }),
                ],
            }),
        },
        // API (products, categories) — NetworkFirst, 10s timeout, 24h
        {
            matcher: ({ url }) => /\/api\/(?:products|categories)/i.test(url.pathname),
            handler: new NetworkFirst({
                cacheName: 'api-data',
                networkTimeoutSeconds: 10,
                plugins: [
                    new ExpirationPlugin({
                        maxEntries: 32,
                        maxAgeSeconds: 24 * 60 * 60,
                    }),
                ],
            }),
        },
        // Diğer her şey için varsayılanlar (Google Fonts, statik chunk'lar, navigation, vs.)
        ...defaultCache,
    ],
    fallbacks: {
        entries: [
            {
                url: '/offline',
                matcher: ({ request }) => request.destination === 'document',
            },
        ],
    },
});

serwist.addEventListeners();
