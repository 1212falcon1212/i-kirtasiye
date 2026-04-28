import { Star } from 'lucide-react';

interface StarsProps {
    value: number;
    size?: number;
    max?: number;
}

export function Stars({ value, size = 12, max = 5 }: StarsProps) {
    const rounded = Math.round(value);
    return (
        <span className="inline-flex gap-0.5" style={{ color: 'var(--warning)' }}>
            {Array.from({ length: max }).map((_, i) => (
                <Star
                    key={i}
                    width={size}
                    height={size}
                    style={{
                        opacity: i < rounded ? 1 : 0.25,
                        fill: i < rounded ? 'currentColor' : 'transparent',
                    }}
                />
            ))}
        </span>
    );
}

export default Stars;
