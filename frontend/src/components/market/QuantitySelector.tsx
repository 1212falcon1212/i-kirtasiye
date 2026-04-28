'use client';

import { useState, useEffect } from 'react';
import { Minus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface QuantitySelectorProps {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    step?: number;
    disabled?: boolean;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}

export function QuantitySelector({
    value,
    onChange,
    min = 1,
    max = 9999,
    step = 1,
    disabled = false,
    size = 'md',
    className,
}: QuantitySelectorProps) {
    const [inputValue, setInputValue] = useState(value.toString());

    useEffect(() => {
        setInputValue(value.toString());
    }, [value]);

    const handleDecrease = () => {
        const newValue = Math.max(min, value - step);
        onChange(newValue);
    };

    const handleIncrease = () => {
        const newValue = Math.min(max, value + step);
        onChange(newValue);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const val = e.target.value;
        setInputValue(val);

        // Allow empty input while typing
        if (val === '') return;

        const numValue = parseInt(val, 10);
        if (!isNaN(numValue)) {
            const clampedValue = Math.min(Math.max(numValue, min), max);
            onChange(clampedValue);
        }
    };

    const handleBlur = () => {
        // Reset to current value if input is empty or invalid
        if (inputValue === '' || isNaN(parseInt(inputValue, 10))) {
            setInputValue(value.toString());
        } else {
            const numValue = parseInt(inputValue, 10);
            const clampedValue = Math.min(Math.max(numValue, min), max);
            setInputValue(clampedValue.toString());
            onChange(clampedValue);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            handleIncrease();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            handleDecrease();
        } else if (e.key === 'Enter') {
            e.currentTarget.blur();
        }
    };

    const sizeClasses = {
        sm: {
            button: 'h-7 w-7',
            input: 'h-7 w-12 text-sm',
            icon: 'h-3 w-3',
        },
        md: {
            button: 'h-9 w-9',
            input: 'h-9 w-14 text-sm',
            icon: 'h-4 w-4',
        },
        lg: {
            button: 'h-11 w-11',
            input: 'h-11 w-16 text-base',
            icon: 'h-5 w-5',
        },
    };

    const sizes = sizeClasses[size];

    return (
        <div
            className={cn(
                'inline-flex items-center rounded-lg border border-slate-200 bg-white overflow-hidden',
                'shadow-sm transition-shadow hover:shadow-md focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-300',
                disabled && 'opacity-50 pointer-events-none',
                className
            )}
        >
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className={cn(
                    sizes.button,
                    'rounded-none border-r border-slate-200 text-slate-600 hover:text-blue-600 hover:bg-blue-50',
                    'transition-colors'
                )}
                onClick={handleDecrease}
                disabled={disabled || value <= min}
            >
                <Minus className={sizes.icon} />
            </Button>

            <Input
                type="text"
                inputMode="numeric"
                pattern="[0-9]*"
                value={inputValue}
                onChange={handleInputChange}
                onBlur={handleBlur}
                onKeyDown={handleKeyDown}
                disabled={disabled}
                className={cn(
                    sizes.input,
                    'rounded-none border-0 text-center font-semibold focus-visible:ring-0 focus-visible:ring-offset-0',
                    '[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none'
                )}
            />

            <Button
                type="button"
                variant="ghost"
                size="icon"
                className={cn(
                    sizes.button,
                    'rounded-none border-l border-slate-200 text-slate-600 hover:text-blue-600 hover:bg-blue-50',
                    'transition-colors'
                )}
                onClick={handleIncrease}
                disabled={disabled || value >= max}
            >
                <Plus className={sizes.icon} />
            </Button>
        </div>
    );
}
