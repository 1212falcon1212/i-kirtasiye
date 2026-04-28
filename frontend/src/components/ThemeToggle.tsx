'use client';

import * as React from 'react';
import { Moon, Sun } from 'lucide-react';

type Theme = 'light' | 'dark';

function readStoredTheme(): Theme {
    if (typeof window === 'undefined') return 'light';
    try {
        const t = localStorage.getItem('theme');
        if (t === 'dark' || t === 'light') return t;
    } catch {
        /* ignore */
    }
    return 'light';
}

function applyTheme(theme: Theme) {
    const html = document.documentElement;
    if (theme === 'dark') {
        html.setAttribute('data-theme', 'dark');
        html.classList.add('dark');
    } else {
        html.setAttribute('data-theme', 'light');
        html.classList.remove('dark');
    }
}

export function ThemeToggle({ className = '' }: { className?: string }) {
    const [mounted, setMounted] = React.useState(false);
    const [theme, setTheme] = React.useState<Theme>('light');

    React.useEffect(() => {
        const stored = readStoredTheme();
        setTheme(stored);
        applyTheme(stored);
        setMounted(true);
    }, []);

    const toggle = React.useCallback(() => {
        setTheme((prev) => {
            const next: Theme = prev === 'dark' ? 'light' : 'dark';
            try {
                localStorage.setItem('theme', next);
            } catch {
                /* ignore */
            }
            applyTheme(next);
            return next;
        });
    }, []);

    return (
        <button
            type="button"
            onClick={toggle}
            aria-label="Tema değiştir"
            className={`btn btn-icon ${className}`}
            title={theme === 'dark' ? 'Aydınlık moda geç' : 'Karanlık moda geç'}
        >
            {mounted && theme === 'dark' ? (
                <Sun className="h-4 w-4" />
            ) : (
                <Moon className="h-4 w-4" />
            )}
        </button>
    );
}
