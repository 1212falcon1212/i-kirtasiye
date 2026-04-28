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
        <div className="bg-[#934f12] h-9 hidden md:flex items-center">
            <div className="max-w-[1300px] mx-auto px-7 w-full flex items-center justify-between">
                {/* Left */}
                <div className="flex items-center gap-4 text-xs text-[#fbeede] font-semibold">
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
                <div className="flex items-center gap-3 text-xs text-[#fbeede] font-semibold">
                    <Link
                        href="/register"
                        className="hover:text-white transition-colors"
                    >
                        Nasıl Satıcı Olurum?
                    </Link>
                    <span className="opacity-40">|</span>
                    <Link
                        href="/iletisim"
                        className="hover:text-white transition-colors"
                    >
                        İletişim
                    </Link>
                    {phone && (
                        <>
                            <span className="opacity-40">|</span>
                            <a
                                href={`tel:${phone.replace(/\s/g, '')}`}
                                className="flex items-center gap-1.5 hover:text-white transition-colors"
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
