'use client';

import { MarketHeader } from '@/components/market/MarketHeader';
import { MarketFooter } from '@/components/market/MarketFooter';
import { WhatsAppBubble } from '@/components/market/WhatsAppBubble';
import { medicalBgImage } from '@/components/ui/medical-background';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div
            className="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 relative"
            style={{
                backgroundImage: medicalBgImage,
                backgroundRepeat: 'repeat',
                backgroundSize: '240px 240px',
            }}
        >
            <MarketHeader />
            <main className="relative">{children}</main>
            <MarketFooter />
            <WhatsAppBubble />
        </div>
    );
}
