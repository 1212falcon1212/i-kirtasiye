import Link from 'next/link';
import Image from 'next/image';

type LogoSize = 'sm' | 'md' | 'lg';

interface LogoProps {
    size?: LogoSize;
    href?: string;
    /** When true, renders as a span (no link wrapper) */
    asChild?: boolean;
    className?: string;
}

const SIZES: Record<LogoSize, { width: number; height: number }> = {
    sm: { width: 120, height: 40 },
    md: { width: 160, height: 52 },
    lg: { width: 200, height: 64 },
};

export function Logo({ size = 'md', href = '/', asChild = false, className = '' }: LogoProps) {
    const s = SIZES[size];

    const inner = (
        <Image
            src="/logo.webp"
            alt="i-kırtasiye logo"
            width={s.width}
            height={s.height}
            className={className}
            priority
        />
    );

    if (asChild) return inner;

    return (
        <Link href={href} className="inline-flex items-center" aria-label="i-kırtasiye anasayfa">
            {inner}
        </Link>
    );
}

export default Logo;
