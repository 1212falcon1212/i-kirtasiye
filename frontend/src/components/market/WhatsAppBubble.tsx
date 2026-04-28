"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import { X } from "lucide-react";
import { cmsApi, CmsLayoutResponse } from "@/lib/api";

export function WhatsAppBubble() {
    const [settings, setSettings] = useState<CmsLayoutResponse["settings"] | null>(null);
    const [showTooltip, setShowTooltip] = useState(false);
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        cmsApi.getLayout().then((res) => {
            const raw = res.data as { data?: CmsLayoutResponse } | CmsLayoutResponse;
            const layout = (raw as { data?: CmsLayoutResponse }).data ?? (raw as CmsLayoutResponse);
            if (layout?.settings) {
                setSettings(layout.settings);
            }
        });
    }, []);

    useEffect(() => {
        if (!settings?.whatsapp_phone) return;
        const timer = setTimeout(() => setShowTooltip(true), 3000);
        return () => clearTimeout(timer);
    }, [settings?.whatsapp_phone]);

    if (!settings?.whatsapp_phone) return null;

    const phone = settings.whatsapp_phone.replace(/\s/g, "").replace(/^\+/, "");
    const message = encodeURIComponent(settings.whatsapp_message || "Merhaba, bilgi almak istiyorum.");
    const whatsappUrl = `https://wa.me/${phone}?text=${message}`;

    return (
        <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2">
            {/* Tooltip */}
            {showTooltip && !dismissed && (
                <div className="relative bg-white rounded-2xl shadow-xl border border-gray-100 px-4 py-3 max-w-[240px] animate-in slide-in-from-bottom-2 fade-in duration-300">
                    <button
                        onClick={() => setDismissed(true)}
                        className="absolute -top-2 -right-2 w-5 h-5 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors"
                    >
                        <X className="w-3 h-3 text-gray-500" />
                    </button>
                    <p className="text-sm text-gray-700 font-medium">
                        Size nasıl yardımcı olabiliriz?
                    </p>
                    <p className="text-xs text-gray-400 mt-1">WhatsApp ile yazın</p>
                    <div className="absolute -bottom-2 right-6 w-4 h-4 bg-white border-b border-r border-gray-100 rotate-45" />
                </div>
            )}

            {/* WhatsApp Button */}
            <a
                href={whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                onClick={() => setDismissed(true)}
                className="block w-[60px] h-[60px] transition-transform duration-200 hover:scale-110"
                aria-label="WhatsApp ile iletişime geçin"
            >
                <Image
                    src="/icons/whatsapp.png"
                    alt="WhatsApp"
                    width={60}
                    height={60}
                    className="w-full h-full"
                />
            </a>
        </div>
    );
}
