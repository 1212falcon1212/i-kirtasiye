'use client';

import * as React from 'react';

interface ThemeProviderProps {
    children: React.ReactNode;
    /** Reserved props for backwards-compat with previous next-themes-style call sites */
    attribute?: string;
    defaultTheme?: 'light' | 'dark';
    enableSystem?: boolean;
    storageKey?: string;
    forcedTheme?: 'light' | 'dark';
    disableTransitionOnChange?: boolean;
}

/**
 * Lightweight theme provider.
 * The actual theme state is owned by ThemeToggle (writes to <html data-theme> + localStorage).
 * An inline <script> in app/layout.tsx restores the saved theme before paint to avoid flash.
 * This component only enforces a forcedTheme (when provided) and is otherwise a passthrough.
 */
export function ThemeProvider({ children, forcedTheme }: ThemeProviderProps) {
    React.useEffect(() => {
        if (forcedTheme) {
            const html = document.documentElement;
            html.setAttribute('data-theme', forcedTheme);
            if (forcedTheme === 'dark') html.classList.add('dark');
            else html.classList.remove('dark');
        }
    }, [forcedTheme]);

    return <>{children}</>;
}

export default ThemeProvider;
