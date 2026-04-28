"use client";

import { useEffect, useState } from "react";
import { WifiOff, RefreshCw } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function OfflinePage() {
    const [isOnline, setIsOnline] = useState(true);

    useEffect(() => {
        setIsOnline(navigator.onLine);

        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);

        return () => {
            window.removeEventListener("online", handleOnline);
            window.removeEventListener("offline", handleOffline);
        };
    }, []);

    useEffect(() => {
        if (isOnline) {
            window.location.reload();
        }
    }, [isOnline]);

    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
            <div className="text-center max-w-md">
                <div className="mx-auto w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mb-8">
                    <WifiOff className="w-12 h-12 text-gray-500" />
                </div>

                <h1 className="text-2xl font-bold text-gray-900 mb-4">
                    İnternet Bağlantısı Yok
                </h1>

                <p className="text-gray-600 mb-8">
                    Görünüşe göre internet bağlantınız kesilmiş. Lütfen bağlantınızı kontrol edin ve tekrar deneyin.
                </p>

                <Button
                    onClick={() => window.location.reload()}
                    className="gap-2"
                    size="lg"
                >
                    <RefreshCw className="w-4 h-4" />
                    Tekrar Dene
                </Button>

                <p className="mt-8 text-sm text-gray-500">
                    Bağlantı geri geldiğinde sayfa otomatik olarak yenilenecektir.
                </p>
            </div>
        </div>
    );
}
