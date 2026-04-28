'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Search, Zap, ArrowRight } from 'lucide-react';

export function QuickOrderCard() {
    const [code, setCode] = useState('');
    const router = useRouter();

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const c = code.trim();
        if (c) router.push(`/market/search?q=${encodeURIComponent(c)}`);
    };

    return (
        <div
            className="flex flex-col gap-3 p-5"
            style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border)',
                borderRadius: 'var(--radius-lg)',
            }}
        >
            <div className="flex items-center gap-2">
                <div
                    className="w-8 h-8 rounded-md flex items-center justify-center"
                    style={{ background: 'var(--accent-soft)', color: 'var(--accent)' }}
                >
                    <Zap className="w-4 h-4" />
                </div>
                <div>
                    <div className="eyebrow text-[10px]">Hızlı sipariş</div>
                    <div className="text-sm font-semibold" style={{ color: 'var(--fg)' }}>
                        SKU veya barkod ile
                    </div>
                </div>
            </div>
            <form onSubmit={submit} className="relative">
                <Search
                    className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5"
                    style={{ color: 'var(--fg-soft)' }}
                />
                <input
                    type="text"
                    value={code}
                    onChange={(e) => setCode(e.target.value)}
                    placeholder="FC-9000-12, BIC-CRY-50…"
                    className="input"
                    style={{ paddingLeft: 32 }}
                />
            </form>
            <button type="button" className="btn btn-soft btn-sm">
                Excel ile toplu yükle <ArrowRight className="w-3 h-3" />
            </button>
            <div
                className="flex gap-1.5 mt-auto pt-2"
                style={{ borderTop: '1px dashed var(--border)' }}
            >
                <div className="flex-1 text-center">
                    <div
                        className="mono text-lg font-bold"
                        style={{ color: 'var(--accent)' }}
                    >
                        142
                    </div>
                    <div className="text-[10px]" style={{ color: 'var(--fg-soft)' }}>
                        bayi bu hafta
                    </div>
                </div>
                <div className="w-px" style={{ background: 'var(--border)' }} />
                <div className="flex-1 text-center">
                    <div
                        className="mono text-lg font-bold"
                        style={{ color: 'var(--accent)' }}
                    >
                        3.8s
                    </div>
                    <div className="text-[10px]" style={{ color: 'var(--fg-soft)' }}>
                        ort. süre
                    </div>
                </div>
            </div>
        </div>
    );
}

export default QuickOrderCard;
