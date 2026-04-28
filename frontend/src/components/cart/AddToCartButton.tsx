'use client';

import { ShoppingCart, Check, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCartStore } from '@/stores/useCartStore';
import { useState, useRef } from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { QuantitySelector } from '@/components/market/QuantitySelector';
import { useAuth } from '@/contexts/AuthContext';

interface AddToCartButtonProps {
    offerId: number;
    stock: number;
    sellerId?: number;
    quantity?: number;
    disabled?: boolean;
    showConfetti?: boolean;
    className?: string;
    showQuantitySelector?: boolean; // Show +/- quantity selector
    size?: 'sm' | 'md' | 'lg';
}

// Confetti particle component
function ConfettiParticle({ delay, x, color }: { delay: number; x: number; color: string }) {
    return (
        <div
            className="absolute w-2 h-2 rounded-full animate-confetti pointer-events-none"
            style={{
                left: `${x}%`,
                backgroundColor: color,
                animationDelay: `${delay}ms`,
                top: '50%',
            }}
        />
    );
}

export function AddToCartButton({
    offerId,
    stock,
    sellerId,
    quantity: initialQuantity = 1,
    disabled = false,
    showConfetti = false,
    className = '',
    showQuantitySelector = false,
    size = 'md',
}: AddToCartButtonProps) {
    const { addItem, isLoading, setOpen } = useCartStore();
    const { user } = useAuth();
    const [isAdded, setIsAdded] = useState(false);
    const [showAnimation, setShowAnimation] = useState(false);
    const [particles, setParticles] = useState<Array<{ id: number; x: number; delay: number; color: string }>>([]);
    const [quantity, setQuantity] = useState(initialQuantity);
    const buttonRef = useRef<HTMLButtonElement>(null);

    const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];

    const createParticles = () => {
        const newParticles = Array.from({ length: 20 }, (_, i) => ({
            id: i,
            x: Math.random() * 100,
            delay: Math.random() * 200,
            color: colors[Math.floor(Math.random() * colors.length)],
        }));
        setParticles(newParticles);
    };

    const handleAddToCart = async () => {
        if (stock <= 0) {
            toast.error('Bu ürün stokta bulunmuyor.');
            return;
        }

        try {
            await addItem(offerId, quantity);
            setIsAdded(true);

            if (showConfetti) {
                setShowAnimation(true);
                createParticles();

                // Create success ripple effect
                if (buttonRef.current) {
                    buttonRef.current.classList.add('animate-success-pulse');
                    setTimeout(() => {
                        buttonRef.current?.classList.remove('animate-success-pulse');
                    }, 600);
                }
            }

            toast.success(
                <div className="flex items-center gap-2">
                    <Check className="h-4 w-4 text-[#b8651a]" />
                    <span>
                        {quantity > 1 ? `${quantity} adet ürün` : 'Ürün'} sepetinize eklendi!
                    </span>
                </div>,
                {
                    action: {
                        label: 'Sepeti Gör',
                        onClick: () => setOpen(true),
                    },
                }
            );

            setTimeout(() => {
                setIsAdded(false);
                setShowAnimation(false);
                setParticles([]);
            }, 2500);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Ürün eklenemedi.');
        }
    };

    // Don't show add-to-cart for the seller's own listings
    if (sellerId && user?.id === sellerId) {
        return null;
    }

    if (isAdded) {
        return (
            <div className="relative">
                {/* Confetti particles */}
                {showAnimation && showConfetti && (
                    <div className="absolute inset-0 overflow-visible pointer-events-none z-50">
                        {particles.map((particle) => (
                            <ConfettiParticle
                                key={particle.id}
                                x={particle.x}
                                delay={particle.delay}
                                color={particle.color}
                            />
                        ))}
                    </div>
                )}

                <Button
                    ref={buttonRef}
                    variant="outline"
                    className={cn(
                        'gap-2 border-[#fbeede] bg-[#fbeede] text-[#934f12] hover:bg-[#fbeede] hover:text-[#934f12]',
                        'transition-all duration-300',
                        showAnimation && 'scale-105',
                        className
                    )}
                    onClick={() => setOpen(true)}
                >
                    <div className="relative">
                        <Check className={cn(
                            'h-4 w-4',
                            showAnimation && 'animate-bounce'
                        )} />
                        {showAnimation && showConfetti && (
                            <Sparkles className="absolute -top-1 -right-1 h-3 w-3 text-yellow-500" />
                        )}
                    </div>
                    Sepette
                </Button>
            </div>
        );
    }

    // Size classes for button
    const buttonSizeClasses = {
        sm: 'h-8 text-xs px-3',
        md: 'h-10 text-sm px-4',
        lg: 'h-12 text-base px-6',
    };

    const iconSizeClasses = {
        sm: 'h-3 w-3',
        md: 'h-4 w-4',
        lg: 'h-5 w-5',
    };

    // With quantity selector
    if (showQuantitySelector) {
        return (
            <div className={cn('flex items-center gap-2', className)}>
                <QuantitySelector
                    value={quantity}
                    onChange={setQuantity}
                    min={1}
                    max={stock}
                    disabled={disabled || stock <= 0}
                    size={size === 'lg' ? 'md' : 'sm'}
                />
                <Button
                    ref={buttonRef}
                    onClick={handleAddToCart}
                    disabled={disabled || isLoading || stock <= 0}
                    className={cn(
                        'gap-2 transition-all duration-200',
                        'hover:scale-[1.02] active:scale-[0.98]',
                        stock <= 0 && 'opacity-50 cursor-not-allowed',
                        buttonSizeClasses[size]
                    )}
                >
                    {isLoading ? (
                        <>
                            <span className={cn('animate-spin rounded-full border-2 border-current border-t-transparent', iconSizeClasses[size])} />
                            Ekleniyor...
                        </>
                    ) : stock <= 0 ? (
                        <>
                            <ShoppingCart className={iconSizeClasses[size]} />
                            Stokta Yok
                        </>
                    ) : (
                        <>
                            <ShoppingCart className={iconSizeClasses[size]} />
                            Ekle
                        </>
                    )}
                </Button>
            </div>
        );
    }

    // Default: without quantity selector
    return (
        <Button
            ref={buttonRef}
            onClick={handleAddToCart}
            disabled={disabled || isLoading || stock <= 0}
            className={cn(
                'gap-2 transition-all duration-200',
                'hover:scale-[1.02] active:scale-[0.98]',
                stock <= 0 && 'opacity-50 cursor-not-allowed',
                buttonSizeClasses[size],
                className
            )}
        >
            {isLoading ? (
                <>
                    <span className={cn('animate-spin rounded-full border-2 border-current border-t-transparent', iconSizeClasses[size])} />
                    Ekleniyor...
                </>
            ) : stock <= 0 ? (
                <>
                    <ShoppingCart className={iconSizeClasses[size]} />
                    Stokta Yok
                </>
            ) : (
                <>
                    <ShoppingCart className={iconSizeClasses[size]} />
                    Sepete Ekle
                </>
            )}
        </Button>
    );
}
