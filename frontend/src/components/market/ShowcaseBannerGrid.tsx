'use client';

import Link from 'next/link';
import { Banner } from '@/lib/api';

const SHOWCASE_COLORS = [
    'from-[#ea580c] to-[#c2410c]',
    'from-blue-800 to-blue-950',
    'from-[#1a1a1a] to-[#2a2a2a]',
    'from-purple-600 to-indigo-700',
    'from-cyan-500 to-cyan-600',
    'from-[#ea580c] to-cyan-700',
    'from-cyan-600 to-blue-700',
    'from-red-600 to-cyan-700',
    'from-[#1a1a1a] to-[#333333]',
    'from-fuchsia-600 to-purple-700',
    'from-cyan-600 to-cyan-700',
    'from-sky-500 to-indigo-600',
    'from-[#ea580c] to-cyan-700',
    'from-teal-500 to-cyan-700',
    'from-violet-600 to-purple-800',
];

export function ShowcaseBannerGrid({ banners }: { banners: Banner[] }) {
    return (
        <section className="py-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {banners.map((banner, index) => {
                    const colorClass = SHOWCASE_COLORS[index % SHOWCASE_COLORS.length];
                    const isExternal = banner.link_url?.startsWith('http');

                    const cardContent = (
                        <>
                            <div className="relative h-full flex items-center p-4 md:p-5">
                                <div className="flex-1 min-w-0 pr-2 z-10">
                                    {banner.title && (
                                        <h3 className="text-white font-extrabold text-sm md:text-base lg:text-lg leading-tight mb-1 drop-shadow-sm line-clamp-2">
                                            {banner.title}
                                        </h3>
                                    )}
                                    {banner.subtitle && (
                                        <p className="text-white/90 text-xs md:text-sm font-semibold leading-tight line-clamp-2">
                                            {banner.subtitle}
                                        </p>
                                    )}
                                    {banner.button_text && (
                                        <span className="inline-block mt-2.5 bg-white text-[#1a1a1a] text-xs font-bold px-3 py-1.5 rounded-md shadow-sm group-hover:shadow-md transition-shadow">
                                            {banner.button_text}
                                        </span>
                                    )}
                                </div>
                                <div className="flex-shrink-0 w-[100px] h-[100px] md:w-[120px] md:h-[120px] relative">
                                    <div className="absolute inset-0 bg-white/20 rounded-xl" />
                                    <img
                                        src={banner.image_url}
                                        alt={banner.title || ''}
                                        className="w-full h-full object-contain relative z-10 drop-shadow-lg transition-transform duration-300 group-hover:scale-110 p-1"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        </>
                    );

                    const className = `relative overflow-hidden rounded-xl h-[140px] md:h-[150px] bg-gradient-to-br ${colorClass} group cursor-pointer shadow-sm hover:shadow-lg transition-all duration-200`;

                    if (!banner.link_url) {
                        return <div key={banner.id} className={className}>{cardContent}</div>;
                    }

                    if (isExternal) {
                        return (
                            <a key={banner.id} href={banner.link_url} target="_blank" rel="noopener noreferrer" className={className}>
                                {cardContent}
                            </a>
                        );
                    }

                    return (
                        <Link key={banner.id} href={banner.link_url} className={className}>
                            {cardContent}
                        </Link>
                    );
                })}
            </div>
        </section>
    );
}
