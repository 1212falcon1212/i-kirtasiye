'use client';

import { useState, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { motion, AnimatePresence } from 'framer-motion';
import { toast } from 'sonner';
import { Cross } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { authApi, api } from '@/lib/api';

function ResetPasswordForm() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const token = searchParams.get('token') || '';
  const email = searchParams.get('email') || '';

  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmation, setShowConfirmation] = useState(false);
  const [focusedField, setFocusedField] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password.length < 8) {
      setError('Şifre en az 8 karakter olmalıdır.');
      return;
    }

    if (password !== passwordConfirmation) {
      setError('Şifreler eşleşmiyor.');
      return;
    }

    if (!token || !email) {
      setError('Geçersiz sıfırlama bağlantısı. Lütfen yeni bir bağlantı talep edin.');
      return;
    }

    setIsLoading(true);

    const response = await authApi.resetPassword({
      email,
      token,
      password,
      password_confirmation: passwordConfirmation,
    });

    if (response.data) {
      api.setToken(response.data.token);
      toast.success('Şifre sıfırlandı!', {
        description: 'Yönlendiriliyorsunuz...',
        position: 'bottom-right',
      });
      router.push('/market');
    } else {
      const errorMessage = response.error || 'Şifre sıfırlama başarısız. Bağlantı geçersiz veya süresi dolmuş olabilir.';
      setError(errorMessage);
      toast.error('İşlem başarısız', {
        description: errorMessage,
      });
    }

    setIsLoading(false);
  };

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.2,
      },
    },
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: {
      opacity: 1,
      y: 0,
      transition: {
        duration: 0.5,
        ease: 'easeOut' as const,
      },
    },
  };

  return (
    <motion.div
      variants={containerVariants}
      initial="hidden"
      animate="visible"
      className="w-full"
    >
      {/* Mobile Logo */}
      <motion.div
        variants={itemVariants}
        className="lg:hidden flex justify-center mb-8"
      >
        <Link href="/" className="flex items-center gap-3">
          <div className="w-12 h-12 bg-[#ffedd5] rounded-xl flex items-center justify-center shadow-lg">
            <Cross className="w-7 h-7 text-white" />
          </div>
          <div className="flex flex-col">
            <span className="text-2xl font-bold text-slate-900">i-kirtasiye</span>
            <span className="text-[10px] font-medium text-[#ea580c] -mt-0.5 tracking-wider uppercase">B2B Kırtasiye</span>
          </div>
        </Link>
      </motion.div>

      {/* Header */}
      <motion.div variants={itemVariants} className="text-center mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Yeni Şifre Belirle</h1>
        <p className="text-gray-600">Hesabınız için yeni bir şifre oluşturun</p>
      </motion.div>

      <form onSubmit={handleSubmit} className="space-y-5">
        {/* Error Message */}
        <AnimatePresence mode="wait">
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -10, height: 0 }}
              animate={{ opacity: 1, y: 0, height: 'auto' }}
              exit={{ opacity: 0, y: -10, height: 0 }}
              transition={{ duration: 0.3 }}
              className="overflow-hidden"
            >
              <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div className="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <p className="text-sm text-red-700 font-medium">{error}</p>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Password Field */}
        <motion.div variants={itemVariants} className="space-y-2">
          <Label htmlFor="password" className="text-sm font-medium text-gray-700">
            Yeni Şifre
          </Label>
          <div className="relative">
            <motion.div
              animate={{
                scale: focusedField === 'password' ? 1 : 0.95,
                opacity: focusedField === 'password' ? 1 : 0,
              }}
              className="absolute inset-0 bg-gradient-to-r from-[#ffedd5]/20 to-teal-500/20 rounded-xl blur-sm -z-10"
            />
            <div className="relative flex items-center">
              <div className="absolute left-4 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <Input
                id="password"
                type={showPassword ? 'text' : 'password'}
                placeholder="En az 8 karakter"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                onFocus={() => setFocusedField('password')}
                onBlur={() => setFocusedField(null)}
                required
                minLength={8}
                className="h-12 pl-12 pr-12 bg-white border-gray-200 rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]/20 transition-all duration-300"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-4 text-gray-400 hover:text-gray-600 transition-colors"
              >
                {showPassword ? (
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                ) : (
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                )}
              </button>
            </div>
          </div>
        </motion.div>

        {/* Password Confirmation Field */}
        <motion.div variants={itemVariants} className="space-y-2">
          <Label htmlFor="password_confirmation" className="text-sm font-medium text-gray-700">
            Şifre Tekrar
          </Label>
          <div className="relative">
            <motion.div
              animate={{
                scale: focusedField === 'confirmation' ? 1 : 0.95,
                opacity: focusedField === 'confirmation' ? 1 : 0,
              }}
              className="absolute inset-0 bg-gradient-to-r from-[#ffedd5]/20 to-teal-500/20 rounded-xl blur-sm -z-10"
            />
            <div className="relative flex items-center">
              <div className="absolute left-4 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <Input
                id="password_confirmation"
                type={showConfirmation ? 'text' : 'password'}
                placeholder="Şifrenizi tekrar girin"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                onFocus={() => setFocusedField('confirmation')}
                onBlur={() => setFocusedField(null)}
                required
                minLength={8}
                className="h-12 pl-12 pr-12 bg-white border-gray-200 rounded-xl focus:border-[#ea580c] focus:ring-[#ea580c]/20 transition-all duration-300"
              />
              <button
                type="button"
                onClick={() => setShowConfirmation(!showConfirmation)}
                className="absolute right-4 text-gray-400 hover:text-gray-600 transition-colors"
              >
                {showConfirmation ? (
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                ) : (
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                )}
              </button>
            </div>
          </div>
        </motion.div>

        {/* Submit Button */}
        <motion.div variants={itemVariants}>
          <Button
            type="submit"
            disabled={isLoading}
            className="relative w-full h-12 bg-gradient-to-r from-[#ffedd5] to-teal-600 hover:from-[#ffedd5] hover:to-teal-700 text-white font-semibold rounded-xl shadow-sm transition-colors duration-150 hover:shadow-md overflow-hidden group"
          >
            <motion.div
              className="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0"
              initial={{ x: '-100%' }}
              animate={isLoading ? { x: '100%' } : { x: '-100%' }}
              transition={isLoading ? { duration: 1, repeat: Infinity, ease: 'linear' } : {}}
            />
            <span className="relative flex items-center justify-center gap-2">
              {isLoading ? (
                <>
                  <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Şifre Sıfırlanıyor...</span>
                </>
              ) : (
                <>
                  <span>Şifreyi Sıfırla</span>
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </>
              )}
            </span>
          </Button>
        </motion.div>

        {/* Back to Login */}
        <motion.div variants={itemVariants} className="text-center">
          <Link
            href="/login"
            className="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Giriş sayfasına dön</span>
          </Link>
        </motion.div>
      </form>
    </motion.div>
  );
}

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={null}>
      <ResetPasswordForm />
    </Suspense>
  );
}
