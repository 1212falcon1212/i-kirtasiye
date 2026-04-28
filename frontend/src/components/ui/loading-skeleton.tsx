import { cn } from '@/lib/utils';

interface LoadingSkeletonProps {
    variant?: 'card' | 'table-row' | 'list-item' | 'text' | 'avatar' | 'stat';
    count?: number;
    className?: string;
}

export function LoadingSkeleton({
    variant = 'card',
    count = 1,
    className,
}: LoadingSkeletonProps) {
    const skeletons = Array.from({ length: count }, (_, i) => i);

    const renderSkeleton = (key: number) => {
        switch (variant) {
            case 'card':
                return (
                    <div
                        key={key}
                        className={cn(
                            'bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 animate-pulse',
                            className
                        )}
                    >
                        <div className="flex items-center gap-4 mb-4">
                            <div className="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                            <div className="flex-1">
                                <div className="h-4 w-3/4 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                                <div className="h-3 w-1/2 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            </div>
                        </div>
                        <div className="space-y-2">
                            <div className="h-3 w-full bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div className="h-3 w-5/6 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                    </div>
                );

            case 'table-row':
                return (
                    <div
                        key={key}
                        className={cn(
                            'flex items-center gap-4 py-4 border-b border-slate-100 dark:border-slate-800 animate-pulse',
                            className
                        )}
                    >
                        <div className="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                        <div className="flex-1 grid grid-cols-4 gap-4">
                            <div className="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div className="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div className="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div className="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                    </div>
                );

            case 'list-item':
                return (
                    <div
                        key={key}
                        className={cn(
                            'flex items-center gap-4 p-4 animate-pulse',
                            className
                        )}
                    >
                        <div className="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                        <div className="flex-1">
                            <div className="h-4 w-3/4 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                            <div className="h-3 w-1/2 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                        <div className="h-6 w-16 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                    </div>
                );

            case 'stat':
                return (
                    <div
                        key={key}
                        className={cn(
                            'bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 animate-pulse',
                            className
                        )}
                    >
                        <div className="flex items-center justify-between mb-4">
                            <div className="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div className="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                        </div>
                        <div className="h-8 w-32 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                        <div className="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                    </div>
                );

            case 'avatar':
                return (
                    <div
                        key={key}
                        className={cn(
                            'w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-full animate-pulse',
                            className
                        )}
                    ></div>
                );

            case 'text':
            default:
                return (
                    <div
                        key={key}
                        className={cn(
                            'h-4 bg-slate-200 dark:bg-slate-700 rounded animate-pulse',
                            className
                        )}
                    ></div>
                );
        }
    };

    return <>{skeletons.map(renderSkeleton)}</>;
}

export default LoadingSkeleton;
