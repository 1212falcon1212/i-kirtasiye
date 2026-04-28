'use client';

import { useEffect } from 'react';
import { AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function RootError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error('Root error boundary:', error);
  }, [error]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4">
      <div className="text-center max-w-md space-y-6">
        <div className="flex justify-center">
          <AlertCircle className="size-16 text-destructive" />
        </div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
          Bir hata olustu
        </h1>
        <p className="text-slate-600 dark:text-slate-400">
          Beklenmeyen bir hata meydana geldi. Lutfen tekrar deneyin.
        </p>
        {process.env.NODE_ENV === 'development' && error.message && (
          <pre className="text-left text-sm bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 p-4 rounded-lg overflow-auto max-h-40 border border-red-200 dark:border-red-800">
            {error.message}
          </pre>
        )}
        <Button onClick={reset} size="lg">
          Tekrar Dene
        </Button>
      </div>
    </div>
  );
}
