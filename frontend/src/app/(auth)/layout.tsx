'use client';

import Link from 'next/link';
import { Logo } from '@/components/Logo';
import { Pencil, BookOpen, Package, Truck } from 'lucide-react';

export default function AuthLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="min-h-screen flex" style={{ background: 'var(--bg)' }}>
            {/* Left brand panel */}
            <div
                className="hidden lg:flex lg:w-1/2 xl:w-[55%] relative overflow-hidden"
                style={{
                    background:
                        'linear-gradient(150deg, var(--accent) 0%, var(--accent-hover) 100%)',
                    color: 'var(--accent-fg)',
                }}
            >
                <div
                    aria-hidden
                    className="absolute"
                    style={{
                        right: -120,
                        top: -120,
                        width: 360,
                        height: 360,
                        borderRadius: 999,
                        border: '40px solid rgba(255,255,255,0.08)',
                    }}
                />
                <div
                    aria-hidden
                    className="absolute"
                    style={{
                        left: -60,
                        bottom: -60,
                        width: 220,
                        height: 220,
                        borderRadius: 999,
                        background: 'rgba(255,255,255,0.06)',
                    }}
                />

                <div className="relative z-10 flex flex-col justify-between p-12 xl:p-16 w-full">
                    <Link href="/" className="inline-flex items-center gap-3">
                        <svg width="48" height="48" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50 20C50 20 30 15 15 20V75C30 70 50 75 50 75C50 75 70 70 85 75V20C70 15 50 20 50 20Z" stroke="white" stroke-width="4" fill="none"/>
                            <path d="M50 20V75" stroke="white" stroke-width="2"/>
                            <circle cx="50" cy="30" r="8" fill="white"/>
                            <path d="M46 38L50 65L54 38" fill="white"/>
                        </svg>
                        <span className="text-2xl font-bold tracking-tight text-white">
                            i-kırtasiye
                        </span>
                    </Link>

                    <div className="space-y-8 max-w-md">
                        <div className="space-y-3">
                            <h2 className="text-4xl xl:text-5xl font-extrabold leading-tight">
                                Türkiye&apos;nin
                                <br />
                                Komisyonsuz B2B
                                <br />
                                Kırtasiye Pazaryeri
                            </h2>
                            <p className="text-base opacity-90">
                                Binlerce kırtasiyeci, yüzlerce tedarikçi. Vergi numarası
                                doğrulamalı güvenli toptan tedarik.
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            {[
                                { Icon: Pencil, label: 'Kalem & Yazı' },
                                { Icon: BookOpen, label: 'Defter & Bloknot' },
                                { Icon: Package, label: 'Ofis Malzemeleri' },
                                { Icon: Truck, label: '24 saatte teslim' },
                            ].map(({ Icon, label }) => (
                                <div
                                    key={label}
                                    className="flex items-center gap-2 px-3 py-2 rounded-lg"
                                    style={{ background: 'rgba(255,255,255,0.12)' }}
                                >
                                    <Icon className="w-4 h-4" />
                                    <span className="text-sm font-medium">{label}</span>
                                </div>
                            ))}
                        </div>

                        <div className="flex gap-8 pt-4 border-t" style={{ borderColor: 'rgba(255,255,255,0.18)' }}>
                            {[
                                { v: '5.000+', l: 'Kayıtlı bayi' },
                                { v: '200+', l: 'Tedarikçi' },
                                { v: '50K+', l: 'Ürün' },
                            ].map((s) => (
                                <div key={s.l}>
                                    <div className="text-2xl font-bold font-mono">{s.v}</div>
                                    <div className="text-xs opacity-80">{s.l}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Right form panel */}
            <div className="w-full lg:w-1/2 xl:w-[45%] flex items-center justify-center p-5 sm:p-8 lg:p-10">
                <div className="w-full max-w-[460px]">
                    <div className="lg:hidden flex justify-center mb-7">
                        <Logo size="md" />
                    </div>
                    <div
                        className="card"
                        style={{
                            padding: 'var(--space-8)',
                            boxShadow: 'var(--shadow-lg)',
                        }}
                    >
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
