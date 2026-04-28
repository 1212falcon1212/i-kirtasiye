import Link from 'next/link';
import { ArrowRight, Flame } from 'lucide-react';

interface HeroBannerProps {
    eyebrow?: string;
    headline?: string;
    description?: string;
    ctaLabel?: string;
    ctaHref?: string;
}

export function HeroBanner({
    eyebrow = 'Hafta sonuna özel',
    headline = 'OKULA DÖNÜŞ %25 İNDİRİM',
    description = "Defter, kalem, çanta ve daha fazlası — bayilere özel toptan fiyatlarla.",
    ctaLabel = 'Alışverişe başla',
    ctaHref = '/market/products',
}: HeroBannerProps) {
    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-5">
            <div
                className="relative overflow-hidden grid grid-cols-1 lg:grid-cols-[minmax(380px,1fr)_2fr]"
                style={{
                    borderRadius: 'var(--radius-xl)',
                    background: 'var(--bg-elevated)',
                    border: '1px solid var(--border)',
                    minHeight: 360,
                }}
            >
                {/* Left */}
                <div
                    className="relative p-10 flex flex-col justify-between overflow-hidden"
                    style={{
                        background: 'linear-gradient(150deg, var(--accent) 0%, var(--accent-hover) 100%)',
                        color: 'var(--accent-fg)',
                    }}
                >
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
                    <div className="relative">
                        <span
                            className="chip"
                            style={{
                                background: 'rgba(255,255,255,0.18)',
                                color: 'white',
                                border: 'none',
                                fontWeight: 600,
                            }}
                        >
                            <Flame className="w-3 h-3" /> {eyebrow}
                        </span>
                        <div className="text-sm font-semibold mt-4 opacity-85 uppercase tracking-wide">
                            25 farklı kategoride
                        </div>
                        <h1
                            className="font-extrabold mt-1 leading-[1.05]"
                            style={{ fontSize: 40, letterSpacing: '-0.02em' }}
                        >
                            {headline}
                        </h1>
                        <p className="mt-3 text-sm max-w-sm opacity-90">{description}</p>
                    </div>

                    <div className="relative mt-6">
                        <Link
                            href={ctaHref}
                            className="btn btn-lg"
                            style={{
                                background: 'white',
                                color: 'var(--accent)',
                                fontWeight: 700,
                                paddingLeft: 20,
                                paddingRight: 20,
                            }}
                        >
                            {ctaLabel} <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                {/* Right visual */}
                <div
                    className="relative hidden lg:flex items-center justify-center overflow-hidden"
                    style={{ background: 'var(--accent-soft)' }}
                >
                    <div
                        className="ph-image absolute"
                        style={{
                            top: 36,
                            left: 60,
                            width: 130,
                            height: 220,
                            transform: 'rotate(-8deg)',
                            borderRadius: 12,
                            boxShadow: '0 12px 32px rgba(0,0,0,0.12)',
                            fontSize: 11,
                        }}
                    >
                        DEFTER
                    </div>
                    <div
                        className="ph-image absolute"
                        style={{
                            top: 80,
                            right: 110,
                            width: 200,
                            height: 200,
                            borderRadius: 999,
                            boxShadow: '0 12px 32px rgba(0,0,0,0.12)',
                            fontSize: 11,
                        }}
                    >
                        KALEM SETİ
                    </div>
                    <div
                        className="ph-image absolute"
                        style={{
                            bottom: 40,
                            left: 200,
                            width: 160,
                            height: 130,
                            transform: 'rotate(6deg)',
                            borderRadius: 12,
                            boxShadow: '0 12px 32px rgba(0,0,0,0.12)',
                            fontSize: 11,
                        }}
                    >
                        BOYA SETİ
                    </div>
                    <div
                        className="absolute inline-flex items-center gap-2 px-3.5 py-2"
                        style={{
                            bottom: 32,
                            right: 36,
                            background: 'var(--bg-elevated)',
                            borderRadius: 999,
                            border: '1px solid var(--border)',
                            fontSize: 12,
                            fontWeight: 600,
                            boxShadow: 'var(--shadow-lg)',
                        }}
                    >
                        <span
                            className="w-2 h-2 rounded-full animate-pulse-live"
                            style={{ background: 'var(--success)' }}
                        />
                        <span style={{ color: 'var(--fg)' }}>3.200+ ilan güncellendi</span>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default HeroBanner;
