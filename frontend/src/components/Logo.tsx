import Link from 'next/link';

type LogoSize = 'sm' | 'md' | 'lg';

interface LogoProps {
    size?: LogoSize;
    href?: string;
    /** When true, renders as a span (no link wrapper) */
    asChild?: boolean;
    className?: string;
}

const SIZES: Record<LogoSize, { box: number; radius: number; mark: number; word: number }> = {
    sm: { box: 26, radius: 6, mark: 13, word: 14 },
    md: { box: 32, radius: 8, mark: 16, word: 18 },
    lg: { box: 40, radius: 10, mark: 20, word: 22 },
};

export function Logo({ size = 'md', href = '/', asChild = false, className = '' }: LogoProps) {
    const s = SIZES[size];

    const inner = (
        <span className={`inline-flex items-center gap-2.5 ${className}`}>
            <span
                aria-hidden
                className="inline-flex items-center justify-center font-mono font-bold"
                style={{
                    width: s.box,
                    height: s.box,
                    borderRadius: s.radius,
                    background: 'var(--accent)',
                    color: 'var(--accent-fg)',
                    fontSize: s.mark,
                    lineHeight: 1,
                }}
            >
                i
            </span>
            <span
                className="font-bold tracking-tight"
                style={{ fontSize: s.word, letterSpacing: '-0.02em', color: 'var(--fg)' }}
            >
                kirtasiye
                <span className="font-medium" style={{ color: 'var(--fg-soft)' }}>
                    .b2b
                </span>
            </span>
        </span>
    );

    if (asChild) return inner;

    return (
        <Link href={href} className="inline-flex items-center" aria-label="i-kirtasiye anasayfa">
            {inner}
        </Link>
    );
}

export default Logo;
