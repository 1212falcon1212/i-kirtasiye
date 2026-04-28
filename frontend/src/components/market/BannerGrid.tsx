'use client';

import Image from 'next/image';
import Link from 'next/link';

const banners = [
    {
        title: 'Vitaminler',
        subtitle: 'Sağlıklı yaşam için',
        image: 'https://images.pexels.com/photos/3683098/pexels-photo-3683098.jpeg?auto=compress&cs=tinysrgb&w=800&h=400&fit=crop',
        href: '/market/category/vitaminler',
    },
    {
        title: 'Cilt Bakımı',
        subtitle: 'Cildinize özen gösterin',
        image: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=800&h=400&fit=crop',
        href: '/market/category/cilt-bakimi',
    },
    {
        title: 'Anne Bebek',
        subtitle: 'Bebeğiniz için en iyisi',
        image: 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=800&h=400&fit=crop',
        href: '/market/category/anne-bebek',
    },
    {
        title: 'Saç Bakımı',
        subtitle: 'Güçlü ve parlak saçlar',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&h=400&fit=crop',
        href: '/market/category/sac-bakimi',
    }
];

export function BannerGrid() {
    return (
        <section className="py-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {banners.map((banner, index) => (
                    <Link
                        key={index}
                        href={banner.href}
                        className="group relative overflow-hidden rounded-lg h-40 md:h-48 shadow-sm hover:shadow-md transition-shadow duration-150"
                    >
                        <Image
                            src={banner.image}
                            alt={banner.title}
                            fill
                            sizes="(max-width: 640px) 100vw, 50vw"
                            className="object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-[#1a1a1a]/60 group-hover:bg-[#1a1a1a]/50 transition-colors" />
                        <div className="relative h-full flex items-end p-5 md:p-6">
                            <div>
                                <h3 className="text-lg md:text-xl font-bold text-white mb-1">
                                    {banner.title}
                                </h3>
                                <p className="text-white/80 text-sm">
                                    {banner.subtitle}
                                </p>
                            </div>
                        </div>
                    </Link>
                ))}
            </div>
        </section>
    );
}
