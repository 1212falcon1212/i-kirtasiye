"use client";

import Link from "next/link";
import { Phone, Clock, Truck } from "lucide-react";

interface TopbarProps {
    show?: boolean;
    phone?: string;
    hours?: string;
    shipping?: string;
}

export function Topbar({ show, phone, hours, shipping }: TopbarProps) {
    if (show === false) return null;

    return (
        <div
            className="h-9 hidden md:flex items-center"
            style={{ background: 'var(--accent-hover)', color: 'var(--accent-fg)' }}
        >
            <div className="max-w-[1300px] mx-auto px-7 w-full flex items-center justify-between">
                {/* Left */}
                <div className="flex items-center gap-4 text-xs font-semibold">
                    {shipping && (
                        <span className="flex items-center gap-1.5">
                            <Truck className="w-3 h-3" />
                            {shipping}
                        </span>
                    )}
                    {hours && (
                        <span className="flex items-center gap-1.5">
                            <Clock className="w-3 h-3" />
                            {hours}
                        </span>
                    )}
                </div>

                {/* Right */}
                <div className="flex items-center gap-3 text-xs font-semibold">
                    <Link
                        href="/register"
                        className="opacity-90 hover:opacity-100 transition-opacity"
                    >
                        Nasıl Satıcı Olurum?
                    </Link>
                    <span className="opacity-50">|</span>
                    <Link
                        href="/iletisim"
                        className="opacity-90 hover:opacity-100 transition-opacity"
                    >
                        İletişim
                    </Link>
                    {phone && (
                        <>
                            <span className="opacity-50">|</span>
                            <a
                                href={`tel:${phone.replace(/\s/g, '')}`}
                                className="flex items-center gap-1.5 opacity-90 hover:opacity-100 transition-opacity"
                            >
                                <Phone className="w-3 h-3" />
                                {phone}
                            </a>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
