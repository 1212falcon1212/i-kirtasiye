'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ChevronDown } from 'lucide-react';

export const SORT_OPTIONS = [
    { value: 'offers_count', label: 'En uygun' },
    { value: 'price_asc', label: 'Fiyat ↑' },
    { value: 'price_desc', label: 'Fiyat ↓' },
    { value: 'name', label: 'İsim A-Z' },
    { value: 'newest', label: 'En yeni' },
] as const;

export type ViewMode = 'grid' | 'list';

export function FilterSection({
    title,
    children,
    defaultOpen = true,
}: {
    title: string;
    children: React.ReactNode;
    defaultOpen?: boolean;
}) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <div className="py-3.5" style={{ borderBottom: '1px solid var(--border)' }}>
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between text-left text-[13px] font-semibold"
                style={{ color: 'var(--fg)' }}
            >
                {title}
                <ChevronDown
                    className="w-3.5 h-3.5 transition-transform"
                    style={{
                        color: 'var(--fg-soft)',
                        transform: open ? 'rotate(180deg)' : 'none',
                    }}
                />
            </button>
            {open && <div className="mt-3 flex flex-col gap-2">{children}</div>}
        </div>
    );
}

export function CheckRow({
    label,
    count,
    checked,
    onChange,
}: {
    label: string;
    count?: number;
    checked: boolean;
    onChange: (next: boolean) => void;
}) {
    return (
        <label className="flex cursor-pointer items-center gap-2 text-[13px]">
            <span
                className="flex h-4 w-4 items-center justify-center rounded transition-colors"
                style={{
                    border: `1.5px solid ${checked ? 'var(--accent)' : 'var(--border-strong)'}`,
                    background: checked ? 'var(--accent)' : 'transparent',
                    color: 'var(--accent-fg)',
                }}
            >
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => onChange(e.target.checked)}
                    className="sr-only"
                />
                {checked && (
                    <svg
                        width="10"
                        height="10"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="3"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="M5 12l4 4 10-10" />
                    </svg>
                )}
            </span>
            <span className="flex-1" style={{ color: 'var(--fg)' }}>
                {label}
            </span>
            {count != null && (
                <span className="mono text-[11px]" style={{ color: 'var(--fg-soft)' }}>
                    {count.toLocaleString('tr-TR')}
                </span>
            )}
        </label>
    );
}

export function Pagination({
    currentPage,
    lastPage,
    onChange,
}: {
    currentPage: number;
    lastPage: number;
    onChange: (p: number) => void;
}) {
    const pages: (number | 'ellipsis')[] = [];
    const range = 2;
    for (let i = 1; i <= lastPage; i++) {
        if (
            i === 1 ||
            i === lastPage ||
            (i >= currentPage - range && i <= currentPage + range)
        ) {
            pages.push(i);
        } else if (
            pages[pages.length - 1] !== 'ellipsis' &&
            (i < currentPage - range || i > currentPage + range)
        ) {
            pages.push('ellipsis');
        }
    }

    return (
        <div className="mt-8 flex justify-center gap-1">
            <Button
                variant="outline"
                size="sm"
                disabled={currentPage <= 1}
                onClick={() => onChange(currentPage - 1)}
            >
                Önceki
            </Button>
            {pages.map((p, idx) =>
                p === 'ellipsis' ? (
                    <span
                        key={`e-${idx}`}
                        className="px-2 py-1.5 text-[12px]"
                        style={{ color: 'var(--fg-soft)' }}
                    >
                        …
                    </span>
                ) : (
                    <Button
                        key={p}
                        size="sm"
                        variant={p === currentPage ? 'default' : 'outline'}
                        style={
                            p === currentPage
                                ? {
                                      background: 'var(--accent)',
                                      color: 'var(--accent-fg)',
                                      minWidth: 32,
                                  }
                                : { minWidth: 32 }
                        }
                        onClick={() => onChange(p)}
                    >
                        {p}
                    </Button>
                ),
            )}
            <Button
                variant="outline"
                size="sm"
                disabled={currentPage >= lastPage}
                onClick={() => onChange(currentPage + 1)}
            >
                Sonraki
            </Button>
        </div>
    );
}
