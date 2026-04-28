'use client';

import { useState, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { toast } from 'sonner';
import { Eye, EyeOff, Mail, Lock, ArrowRight, AlertCircle, Loader2 } from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';

function LoginForm() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const { login } = useAuth();
    const router = useRouter();
    const searchParams = useSearchParams();

    const getRedirectUrl = (): string => {
        const redirect = searchParams.get('redirect');
        if (redirect && redirect.startsWith('/')) return redirect;
        return '/market';
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setIsLoading(true);
        const result = await login(email, password);
        if (result.success) {
            toast.success('Giriş başarılı', { description: 'Yönlendiriliyorsunuz...' });
            router.push(getRedirectUrl());
        } else {
            const msg = result.error || 'Giriş başarısız';
            setError(msg);
            toast.error('Giriş başarısız', { description: msg });
        }
        setIsLoading(false);
    };

    return (
        <div className="w-full">
            <div className="mb-6">
                <span className="eyebrow">Giriş</span>
                <h1 className="text-2xl font-bold mt-1" style={{ color: 'var(--fg)' }}>
                    Hoş geldin
                </h1>
                <p className="text-sm mt-1" style={{ color: 'var(--fg-muted)' }}>
                    Hesabına giriş yap.
                </p>
            </div>

            {error && (
                <div className="mb-4 p-3 rounded-md flex items-start gap-2" style={{ background: 'var(--danger-soft)', color: 'var(--danger)' }}>
                    <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <p className="text-sm font-medium">{error}</p>
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="email" className="block text-sm font-semibold mb-1.5" style={{ color: 'var(--fg)' }}>
                        E-posta
                    </label>
                    <div className="relative">
                        <Mail
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                            style={{ color: 'var(--fg-soft)' }}
                        />
                        <input
                            id="email"
                            type="email"
                            placeholder="info@firma.com"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                            className="input"
                            style={{ paddingLeft: 36 }}
                        />
                    </div>
                </div>

                <div>
                    <div className="flex items-center justify-between mb-1.5">
                        <label htmlFor="password" className="text-sm font-semibold" style={{ color: 'var(--fg)' }}>
                            Şifre
                        </label>
                        <Link href="/forgot-password" className="text-xs font-semibold" style={{ color: 'var(--accent)' }}>
                            Şifremi unuttum
                        </Link>
                    </div>
                    <div className="relative">
                        <Lock
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                            style={{ color: 'var(--fg-soft)' }}
                        />
                        <input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            placeholder="••••••••"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            className="input"
                            style={{ paddingLeft: 36, paddingRight: 36 }}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-3 top-1/2 -translate-y-1/2"
                            style={{ color: 'var(--fg-soft)' }}
                        >
                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    disabled={isLoading}
                    className="btn btn-primary w-full"
                    style={{ height: 44 }}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin" /> Giriş yapılıyor
                        </>
                    ) : (
                        <>
                            Giriş yap <ArrowRight className="w-4 h-4" />
                        </>
                    )}
                </button>

                <div className="text-center pt-2 text-sm" style={{ color: 'var(--fg-muted)' }}>
                    Hesabın yok mu?{' '}
                    <Link href="/register" className="font-semibold" style={{ color: 'var(--accent)' }}>
                        Kayıt ol
                    </Link>
                </div>
            </form>
        </div>
    );
}

export default function LoginPage() {
    return (
        <Suspense fallback={null}>
            <LoginForm />
        </Suspense>
    );
}
