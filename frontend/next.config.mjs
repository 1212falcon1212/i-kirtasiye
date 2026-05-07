import createMDX from '@next/mdx';
import withSerwistInit from '@serwist/next';

/** @type {import('next').NextConfig} */
const nextConfig = {
    pageExtensions: ['js', 'jsx', 'md', 'mdx', 'ts', 'tsx'],
    experimental: {
        optimizeCss: true,
    },
    images: {
        unoptimized: process.env.NODE_ENV === 'development',
        remotePatterns: [
            {
                protocol: "http",
                hostname: "localhost",
                port: "8003",
                pathname: "/storage/**",
            },
            {
                protocol: "http",
                hostname: "localhost",
                port: "8000",
                pathname: "/storage/**",
            },
            {
                protocol: "https",
                hostname: "i-kirtasiye.com",
                pathname: "/**",
            },
            {
                protocol: "https",
                hostname: "images.pexels.com",
                pathname: "/**",
            },
            {
                protocol: "https",
                hostname: "images.unsplash.com",
                pathname: "/**",
            },
            {
                protocol: "https",
                hostname: "www.nezih.com.tr",
                pathname: "/**",
            },
            {
                protocol: "https",
                hostname: "nezih.com.tr",
                pathname: "/**",
            },
        ],
    },
    async rewrites() {
        return [
            {
                source: '/storage/:path*',
                destination: 'http://localhost:8003/storage/:path*',
            },
        ];
    },
};

const withSerwist = withSerwistInit({
    swSrc: 'src/app/sw.ts',
    swDest: 'public/sw.js',
    cacheOnNavigation: true,
    reloadOnOnline: true,
    disable: process.env.NODE_ENV === 'development',
    additionalPrecacheEntries: [
        { url: '/offline', revision: process.env.SW_REVISION ?? `${Date.now()}` },
    ],
});

const withMDX = createMDX({
    extension: /\.mdx?$/,
    options: {
        remarkPlugins: [],
        rehypePlugins: [],
    },
});

export default withSerwist(withMDX(nextConfig));
