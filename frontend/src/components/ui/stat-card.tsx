import { useState, useEffect } from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

interface StatCardProps {
    title: string;
    value: string | number;
    change?: string;
    changeType?: 'positive' | 'negative' | 'neutral';
    icon?: React.ComponentType<{ className?: string }>;
    loading?: boolean;
    suffix?: string;
    prefix?: string;
}

export function StatCard({
    title,
    value,
    change,
    changeType = 'neutral',
    icon: Icon,
    loading = false,
    suffix,
    prefix,
}: StatCardProps) {
    const [animatedValue, setAnimatedValue] = useState<string | number>(value);

    useEffect(() => {
        setAnimatedValue(value);
    }, [value]);

    const getChangeIcon = () => {
        switch (changeType) {
            case 'positive':
                return <TrendingUp className="w-3 h-3" />;
            case 'negative':
                return <TrendingDown className="w-3 h-3" />;
            default:
                return <Minus className="w-3 h-3" />;
        }
    };

    const getChangeColor = () => {
        switch (changeType) {
            case 'positive':
                return 'text-[#b8651a] dark:text-accent bg-[#fbeede] dark:bg-cyan-900/20';
            case 'negative':
                return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20';
            default:
                return 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800';
        }
    };

    if (loading) {
        return (
            <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 animate-pulse">
                <div className="flex items-center justify-between mb-4">
                    <div className="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
                    <div className="h-8 w-8 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                </div>
                <div className="h-8 w-32 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                <div className="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded"></div>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 transition-all duration-200 hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-800">
            <div className="flex items-center justify-between mb-4">
                <span className="text-sm font-medium text-slate-500 dark:text-slate-400">
                    {title}
                </span>
                {Icon && (
                    <div className="w-10 h-10 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg flex items-center justify-center">
                        <Icon className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                )}
            </div>

            <div className="flex items-baseline gap-1">
                {prefix && (
                    <span className="text-lg font-bold text-slate-700 dark:text-slate-300">
                        {prefix}
                    </span>
                )}
                <span className="text-2xl font-bold text-slate-900 dark:text-white">
                    {animatedValue}
                </span>
                {suffix && (
                    <span className="text-lg font-medium text-slate-500 dark:text-slate-400 ml-1">
                        {suffix}
                    </span>
                )}
            </div>

            {change && (
                <div className={`inline-flex items-center gap-1 mt-2 px-2 py-1 rounded-full text-xs font-medium ${getChangeColor()}`}>
                    {getChangeIcon()}
                    <span>{change}</span>
                </div>
            )}
        </div>
    );
}

export default StatCard;
