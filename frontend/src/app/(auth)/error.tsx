'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function AuthError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error('Auth error boundary:', error);
  }, [error]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4">
      <div className="text-center max-w-sm space-y-6">
        <AlertCircle className="size-12 text-destructive mx-auto" />
        <h1 className="text-xl font-bold text-slate-900 dark:text-white">
          Giriş sayfasi yuklenemedi
        </h1>
        <p className="text-sm text-slate-600 dark:text-slate-400">
          Lutfen tekrar deneyin veya giris sayfasina donun.
        </p>
        <div className="flex flex-col gap-3">
          <Button onClick={reset}>Tekrar Dene</Button>
          <Button variant="ghost" asChild>
            <Link href="/login">Giriş Sayfasina Don</Link>
          </Button>
        </div>
      </div>
    </div>
  );
}
