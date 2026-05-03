'use client';

import { Badge } from '@/components/ui/badge';
import { Box, Truck, MapPin, CheckCircle, AlertCircle, Clock } from 'lucide-react';

export interface ShippingStep {
    status: string;
    label: string;
    completed: boolean;
    current: boolean;
    date?: string;
}

const SHIPPING_STEPS = [
    { status: 'pending', label: 'Kargo Bekleniyor', icon: Clock },
    { status: 'processing', label: 'Hazırlanıyor', icon: Box },
    { status: 'shipped', label: 'Kargoya Verildi', icon: Truck },
    { status: 'in_transit', label: 'Yolda', icon: Truck },
    { status: 'out_for_delivery', label: 'Dağıtımda', icon: MapPin },
    { status: 'delivered', label: 'Teslim Edildi', icon: CheckCircle },
];

const STATUS_ORDER = ['pending', 'processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'];

interface ShippingTimelineProps {
    currentStatus: string;
    trackingNumber?: string;
    trackingUrl?: string;
    shippedAt?: string;
    deliveredAt?: string;
}

export function ShippingTimeline({
    currentStatus,
    trackingNumber,
    trackingUrl,
    shippedAt,
    deliveredAt,
}: ShippingTimelineProps) {
    const currentIndex = STATUS_ORDER.indexOf(currentStatus);
    const isReturned = currentStatus === 'returned';
    const isFailed = currentStatus === 'failed';

    if (isReturned || isFailed) {
        return (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <div className="flex items-center gap-2 text-red-700">
                    <AlertCircle className="h-5 w-5" />
                    <span className="font-medium">
                        {isReturned ? 'İade Edildi' : 'Teslimat Başarısız'}
                    </span>
                </div>
                {trackingNumber && (
                    <p className="text-sm text-red-600 mt-2">
                        Takip No: {trackingNumber}
                    </p>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Tracking Info */}
            {trackingNumber && (
                <div className="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                    <div>
                        <p className="text-sm text-gray-500">Kargo Takip No</p>
                        <p className="font-mono font-medium">{trackingNumber}</p>
                    </div>
                    {trackingUrl && (
                        <a
                            href={trackingUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-[#ea580c] hover:underline text-sm"
                        >
                            Takip Et →
                        </a>
                    )}
                </div>
            )}

            {/* Timeline */}
            <div className="relative">
                <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200" />

                <div className="space-y-4">
                    {SHIPPING_STEPS.map((step, index) => {
                        const isCompleted = currentIndex > index || (currentIndex === index && currentStatus === 'delivered');
                        const isCurrent = currentIndex === index;
                        const Icon = step.icon;

                        return (
                            <div key={step.status} className="relative flex items-start gap-4 pl-8">
                                {/* Icon Circle */}
                                <div
                                    className={`absolute left-0 w-8 h-8 rounded-full flex items-center justify-center ${isCompleted
                                            ? 'bg-[#ffedd5] text-white'
                                            : isCurrent
                                                ? 'bg-[#ffedd5] text-[#ea580c] ring-2 ring-[#ea580c]'
                                                : 'bg-gray-100 text-gray-400'
                                        }`}
                                >
                                    <Icon className="h-4 w-4" />
                                </div>

                                {/* Content */}
                                <div className="flex-1 min-w-0 pb-4">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`font-medium ${isCompleted || isCurrent ? 'text-gray-900' : 'text-gray-400'
                                                }`}
                                        >
                                            {step.label}
                                        </span>
                                        {isCurrent && (
                                            <Badge variant="secondary" className="bg-[#ffedd5] text-[#ea580c]">
                                                Şu an
                                            </Badge>
                                        )}
                                    </div>

                                    {/* Show dates for shipped and delivered */}
                                    {step.status === 'shipped' && shippedAt && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {new Date(shippedAt).toLocaleString('tr-TR')}
                                        </p>
                                    )}
                                    {step.status === 'delivered' && deliveredAt && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {new Date(deliveredAt).toLocaleString('tr-TR')}
                                        </p>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
