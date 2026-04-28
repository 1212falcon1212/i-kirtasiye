'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function OdemeError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error('Odeme error:', error);
  }, [error]);

  return (
    <div className="flex items-center justify-center min-h-[60vh] px-4">
      <div className="text-center max-w-md space-y-6">
        <div className="flex justify-center">
          <AlertCircle className="size-14 text-destructive" />
        </div>
        <h1 className="text-xl font-bold text-slate-900 dark:text-white">
          Bir hata oluştu
        </h1>
        <p className="text-slate-600 dark:text-slate-400">
          Sayfa yüklenirken beklenmeyen bir sorun meydana geldi.
        </p>
        {process.env.NODE_ENV === 'development' && error.message && (
          <pre className="text-left text-sm bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 p-4 rounded-lg overflow-auto max-h-40 border border-red-200 dark:border-red-800">
            {error.message}
          </pre>
        )}
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Button onClick={reset}>Tekrar Dene</Button>
          <Button variant="outline" asChild>
            <Link href="/market">Ana Sayfaya Dön</Link>
          </Button>
        </div>
      </div>
    </div>
  );
}
