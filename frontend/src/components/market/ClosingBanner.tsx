'use client';

import React from 'react';
import Link from 'next/link';
import { ArrowRight } from 'lucide-react';
import { Banner } from '@/lib/api';

interface ClosingBannerProps {
    banners: Banner[];
}

export function ClosingBanner({ banners }: ClosingBannerProps) {
    if (!banners || banners.length === 0) return null;

    // Single banner → cinematic full-width
    if (banners.length === 1) {
        return <SingleBanner banner={banners[0]} />;
    }

    // 2+ banners → editorial dual layout
    return <DualLayout banners={banners.slice(0, 2)} />;
}

function SingleBanner({ banner }: { banner: Banner }) {
    const content = (
        <div
            className="relative overflow-hidden rounded-3xl h-[220px] md:h-[280px] cursor-pointer group"
            style={{
                backgroundImage: banner.image_url ? `url(${banner.image_url})` : undefined,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                backgroundColor: '#ffedd5',
            }}
        >
            {/* Gradient overlay */}
            <div className="absolute inset-0 bg-gradient-to-r from-[#1a1a1a]/80 via-[#1a1a1a]/40 to-transparent" />

            {/* Decorative rose accent line */}
            <div className="absolute top-0 left-0 w-1.5 h-full bg-[#1e3a8a]" />

            {/* Content */}
            <div className="absolute inset-0 flex items-center">
                <div className="max-w-[1300px] mx-auto px-10 md:px-14 w-full">
                    <div className="max-w-lg">
                        {banner.badge_text && (
                            <span className="inline-block text-[10px] font-bold tracking-[4px] uppercase text-[#d99248] mb-3">
                                {banner.badge_text}
                            </span>
                        )}
                        {banner.title && (
                            <h3 className="text-2xl md:text-4xl font-black text-white leading-[1.1] tracking-tight mb-3">
                                {banner.title}
                            </h3>
                        )}
                        {banner.subtitle && (
                            <p className="text-sm md:text-base text-white/70 font-normal mb-6 max-w-md leading-relaxed">
                                {banner.subtitle}
                            </p>
                        )}
                        {banner.button_text && (
                            <span className="inline-flex items-center gap-2 bg-[#1e3a8a] text-white text-sm font-bold px-7 py-3.5 rounded-xl group-hover:bg-[#1e40af] group-hover:gap-3 transition-all shadow-lg shadow-[#ea580c]/25">
                                {banner.button_text}
                                <ArrowRight className="w-4 h-4" />
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );

    if (banner.link_url) {
        return banner.link_url.startsWith('http') ? (
            <a href={banner.link_url} target="_blank" rel="noopener noreferrer">{content}</a>
        ) : (
            <Link href={banner.link_url}>{content}</Link>
        );
    }

    return content;
}

function DualLayout({ banners }: { banners: Banner[] }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {banners.map((banner, idx) => (
                <DualCard key={banner.id} banner={banner} variant={idx === 0 ? 'primary' : 'secondary'} />
            ))}
        </div>
    );
}

function DualCard({ banner, variant }: { banner: Banner; variant: 'primary' | 'secondary' }) {
    const gradientClass = variant === 'primary'
        ? 'bg-gradient-to-br from-[#c2410c]/90 via-[#ea580c]/70 to-[#d99248]/50'
        : 'bg-gradient-to-br from-[#1a1a1a]/90 via-[#374151]/70 to-[#1a1a1a]/50';

    const content = (
        <div
            className="relative overflow-hidden rounded-3xl h-[200px] md:h-[240px] cursor-pointer group"
            style={{
                backgroundImage: banner.image_url ? `url(${banner.image_url})` : undefined,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                backgroundColor: variant === 'primary' ? '#ea580c' : '#1a1a1a',
            }}
        >
            {/* Gradient overlay */}
            <div className={`absolute inset-0 ${gradientClass}`} />

            {/* Subtle border glow */}
            <div className="absolute inset-0 rounded-3xl border border-white/10 group-hover:border-white/20 transition-colors" />

            {/* Content */}
            <div className="absolute inset-0 flex items-end p-7 md:p-9">
                <div>
                    {banner.badge_text && (
                        <span className="inline-block text-[10px] font-bold tracking-[3px] uppercase text-white/60 mb-2">
                            {banner.badge_text}
                        </span>
                    )}
                    {banner.title && (
                        <h3 className="text-xl md:text-2xl font-black text-white leading-tight tracking-tight mb-2">
                            {banner.title}
                        </h3>
                    )}
                    {banner.subtitle && (
                        <p className="text-xs md:text-sm text-white/70 mb-4 max-w-xs">
                            {banner.subtitle}
                        </p>
                    )}
                    {banner.button_text && (
                        <span className="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm text-white text-xs font-bold px-5 py-2.5 rounded-xl border border-white/20 group-hover:bg-white/25 group-hover:gap-3 transition-all">
                            {banner.button_text}
                            <ArrowRight className="w-3.5 h-3.5" />
                        </span>
                    )}
                </div>
            </div>
        </div>
    );

    if (banner.link_url) {
        return banner.link_url.startsWith('http') ? (
            <a href={banner.link_url} target="_blank" rel="noopener noreferrer">{content}</a>
        ) : (
            <Link href={banner.link_url}>{content}</Link>
        );
    }

    return content;
}
