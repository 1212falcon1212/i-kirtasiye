import { MetadataRoute } from 'next';

export default function robots(): MetadataRoute.Robots {
    return {
        rules: {
            userAgent: '*',
            allow: '/',
            disallow: ['/api/', '/market/hesabim/', '/checkout/', '/admin/', '/seller/'],
        },
        sitemap: 'https://i-kirtasiye.com/sitemap.xml',
    };
}
