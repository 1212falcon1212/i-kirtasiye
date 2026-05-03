'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { ArrowRight, ChevronLeft, ChevronRight, Flame } from 'lucide-react';

export interface HeroBannerSlide {
    id?: number | string;
    eyebrow?: string | null;
    headline?: string | null;
    description?: string | null;
    ctaLabel?: string | null;
    ctaHref?: string | null;
    sideImage?: string | null;
    sideImageAlt?: string | null;
}

interface HeroBannerProps {
    slides?: HeroBannerSlide[];
    /** Auto-advance interval in ms. 0 disables. */
    autoplayMs?: number;
    /** Backward-compat single-slide props */
    eyebrow?: string;
    headline?: string;
    description?: string;
    ctaLabel?: string;
    ctaHref?: string;
    sideImage?: string | null;
    sideImageAlt?: string;
    sideImageHref?: string | null;
}

const DEFAULT_SLIDE: HeroBannerSlide = {
    eyebrow: 'Hafta sonuna özel',
    headline: 'OKULA DÖNÜŞ %25 İNDİRİM',
    description: "Defter, kalem, çanta ve daha fazlası — bayilere özel toptan fiyatlarla.",
    ctaLabel: 'Alışverişe başla',
    ctaHref: '/market/products',
    sideImage: null,
};

export function HeroBanner({
    slides,
    autoplayMs = 6000,
    eyebrow,
    headline,
    description,
    ctaLabel,
    ctaHref,
    sideImage,
    sideImageAlt,
    sideImageHref,
}: HeroBannerProps) {
    const list: HeroBannerSlide[] =
        slides && slides.length > 0
            ? slides
            : [
                  {
                      eyebrow: eyebrow ?? DEFAULT_SLIDE.eyebrow,
                      headline: headline ?? DEFAULT_SLIDE.headline,
                      description: description ?? DEFAULT_SLIDE.description,
                      ctaLabel: ctaLabel ?? DEFAULT_SLIDE.ctaLabel,
                      ctaHref: ctaHref ?? sideImageHref ?? DEFAULT_SLIDE.ctaHref,
                      sideImage: sideImage ?? null,
                      sideImageAlt: sideImageAlt ?? '',
                  },
              ];

    const [index, setIndex] = useState(0);
    const total = list.length;
    const slide = list[Math.min(index, total - 1)];
    const showControls = total > 1;

    useEffect(() => {
        if (!showControls || autoplayMs <= 0) return;
        const t = setInterval(() => setIndex((i) => (i + 1) % total), autoplayMs);
        return () => clearInterval(t);
    }, [showControls, autoplayMs, total]);

    const go = (next: number) => setIndex(((next % total) + total) % total);
    const prev = () => go(index - 1);
    const advance = () => go(index + 1);

    const eyebrowText = slide.eyebrow ?? null;
    const headlineText = slide.headline ?? null;
    const descriptionText = slide.description ?? null;
    const ctaLabelText = slide.ctaLabel ?? null;
    const ctaHrefText = slide.ctaHref ?? '/market/products';
    const sideImg = slide.sideImage ?? null;
    const sideAlt = slide.sideImageAlt ?? headlineText ?? '';

    const hasOverlayText = !!(eyebrowText || headlineText || descriptionText || ctaLabelText);
    const hasSlideContent = !!(sideImg || hasOverlayText);
    // Show default placeholder content only if every slide is empty
    const showDefaultPlaceholder = !sideImg && !hasOverlayText;

    return (
        <section className="w-full">
            <div
                className="relative overflow-hidden w-full mx-auto"
                style={{
                    background: showDefaultPlaceholder
                        ? 'linear-gradient(150deg, var(--accent) 0%, var(--accent-hover) 100%)'
                        : 'transparent',
                    maxWidth: 1920,
                    aspectRatio: showDefaultPlaceholder ? '4 / 1' : undefined,
                }}
            >
                {/* Background image — intrinsic 4:1, scales by container width */}
                {sideImg ? (
                    slide.ctaHref ? (
                        <Link
                            href={slide.ctaHref}
                            className="block w-full"
                            aria-label={sideAlt || 'Banner'}
                        >
                            <Image
                                src={sideImg}
                                alt={sideAlt}
                                width={1600}
                                height={400}
                                sizes="100vw"
                                className="block w-full h-auto"
                                priority
                                key={sideImg}
                            />
                        </Link>
                    ) : (
                        <Image
                            src={sideImg}
                            alt={sideAlt}
                            width={1600}
                            height={400}
                            sizes="100vw"
                            className="block w-full h-auto"
                            priority
                            key={sideImg}
                        />
                    )
                ) : showDefaultPlaceholder ? (
                    <DefaultDecor />
                ) : null}

                {/* Left text overlay */}
                {hasOverlayText && (
                    <div
                        className="absolute inset-y-0 left-0 z-10 flex flex-col justify-center px-6 sm:px-10 md:px-12"
                        style={{
                            width: '100%',
                            maxWidth: 460,
                            color: 'var(--accent-fg)',
                            background: sideImg
                                ? 'linear-gradient(90deg, rgba(194,65,12,0.92) 0%, rgba(194,65,12,0.88) 60%, rgba(194,65,12,0) 100%)'
                                : 'transparent',
                        }}
                    >
                        {eyebrowText && (
                            <span
                                className="chip self-start"
                                style={{
                                    background: 'rgba(255,255,255,0.18)',
                                    color: 'white',
                                    border: 'none',
                                    fontWeight: 600,
                                }}
                            >
                                <Flame className="w-3 h-3" /> {eyebrowText}
                            </span>
                        )}
                        {headlineText && (
                            <h1
                                className="font-extrabold mt-3 leading-[1.05] drop-shadow-sm"
                                style={{ fontSize: 'clamp(22px, 3.2vw, 40px)', letterSpacing: '-0.02em' }}
                            >
                                {headlineText}
                            </h1>
                        )}
                        {descriptionText && (
                            <p className="mt-3 text-sm sm:text-[15px] max-w-md opacity-95">
                                {descriptionText}
                            </p>
                        )}
                        {ctaLabelText && (
                            <div className="mt-5">
                                <Link
                                    href={ctaHrefText}
                                    className="btn btn-lg"
                                    style={{
                                        background: 'white',
                                        color: 'var(--accent-hover)',
                                        fontWeight: 700,
                                        paddingLeft: 20,
                                        paddingRight: 20,
                                    }}
                                >
                                    {ctaLabelText} <ArrowRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        )}
                    </div>
                )}


                {/* Carousel controls */}
                {showControls && (
                    <>
                        <button
                            type="button"
                            onClick={prev}
                            aria-label="Önceki banner"
                            className="absolute left-3 top-1/2 -translate-y-1/2 z-20 hidden md:flex items-center justify-center transition-opacity hover:!opacity-100"
                            style={{
                                width: 36,
                                height: 36,
                                background: 'rgba(255,255,255,0.85)',
                                color: 'var(--fg)',
                                borderRadius: 999,
                                boxShadow: 'var(--shadow-lg)',
                                opacity: 0.7,
                            }}
                        >
                            <ChevronLeft className="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            onClick={advance}
                            aria-label="Sonraki banner"
                            className="absolute right-3 top-1/2 -translate-y-1/2 z-20 hidden md:flex items-center justify-center transition-opacity hover:!opacity-100"
                            style={{
                                width: 36,
                                height: 36,
                                background: 'rgba(255,255,255,0.85)',
                                color: 'var(--fg)',
                                borderRadius: 999,
                                boxShadow: 'var(--shadow-lg)',
                                opacity: 0.7,
                            }}
                        >
                            <ChevronRight className="w-4 h-4" />
                        </button>
                        <div
                            className="absolute left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5"
                            style={{ bottom: 14 }}
                        >
                            {list.map((_, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    onClick={() => go(i)}
                                    aria-label={`${i + 1}. banner`}
                                    className="transition-all"
                                    style={{
                                        width: i === index ? 24 : 8,
                                        height: 8,
                                        borderRadius: 999,
                                        background: i === index ? '#ffffff' : 'rgba(255,255,255,0.55)',
                                        border: 'none',
                                        cursor: 'pointer',
                                    }}
                                />
                            ))}
                        </div>
                    </>
                )}

                {!hasSlideContent && null}
            </div>
        </section>
    );
}

function DefaultDecor() {
    return (
        <div className="absolute inset-0">
            <span
                aria-hidden
                className="absolute"
                style={{
                    right: -80,
                    top: -80,
                    width: 240,
                    height: 240,
                    borderRadius: 999,
                    border: '32px solid rgba(255,255,255,0.08)',
                }}
            />
            <span
                aria-hidden
                className="absolute"
                style={{
                    left: -40,
                    bottom: -40,
                    width: 140,
                    height: 140,
                    borderRadius: 999,
                    background: 'rgba(255,255,255,0.06)',
                }}
            />
        </div>
    );
}

export default HeroBanner;
