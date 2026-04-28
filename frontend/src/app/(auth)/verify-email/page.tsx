'use client';

import { useState, useEffect, Suspense, useCallback } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { toast } from 'sonner';
import { Cross } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { authApi } from '@/lib/api';

type VerificationStatus = 'loading' | 'success' | 'error';

function VerifyEmailContent() {
  const searchParams = useSearchParams();
  const id = searchParams.get('id') || '';
  const hash = searchParams.get('hash') || '';
  const expires = searchParams.get('expires') || '';
  const signature = searchParams.get('signature') || '';

  const [status, setStatus] = useState<VerificationStatus>('loading');
  const [errorMessage, setErrorMessage] = useState('');
  const [isResending, setIsResending] = useState(false);

  const verifyEmail = useCallback(async () => {
    if (!id || !hash || !expires || !signature) {
      setStatus('error');
      setErrorMessage('Geçersiz doğrulama bağlantısı. Gerekli parametreler eksik.');
      return;
    }

    setStatus('loading');
    const response = await authApi.verifyEmail({ id, hash, expires, signature });

    if (response.data) {
      setStatus('success');
      toast.success('E-posta doğrulandı!', {
        position: 'bottom-right',
      });
    } else {
      setStatus('error');
      setErrorMessage(response.error || 'Doğrulama başarısız. Bağlantı geçersiz veya süresi dolmuş olabilir.');
    }
  }, [id, hash, expires, signature]);

  useEffect(() => {
    verifyEmail();
  }, [verifyEmail]);

  const handleResend = async () => {
    setIsResending(true);
    const response = await authApi.resendVerification();

    if (response.data) {
      toast.success('Doğrulama e-postası gönderildi!', {
        description: 'Lütfen gelen kutunuzu kontrol edin.',
        position: 'bottom-right',
      });
    } else {
      toast.error('Gönderim başarısız', {
        description: response.error || 'Lütfen daha sonra tekrar deneyin.',
      });
    }

    setIsResending(false);
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
          <div className="w-12 h-12 bg-[#fbeede] rounded-xl flex items-center justify-center shadow-lg">
            <Cross className="w-7 h-7 text-white" />
          </div>
          <div className="flex flex-col">
            <span className="text-2xl font-bold text-slate-900">i-kirtasiye</span>
            <span className="text-[10px] font-medium text-[#b8651a] -mt-0.5 tracking-wider uppercase">B2B Kırtasiye</span>
          </div>
        </Link>
      </motion.div>

      {/* Header */}
      <motion.div variants={itemVariants} className="text-center mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">E-posta Doğrulama</h1>
        <p className="text-gray-600">E-posta adresinizi doğruluyoruz</p>
      </motion.div>

      {/* Loading State */}
      {status === 'loading' && (
        <motion.div
          variants={itemVariants}
          className="flex flex-col items-center gap-4 p-8"
        >
          <div className="w-16 h-16 bg-[#fbeede] rounded-full flex items-center justify-center">
            <svg className="animate-spin h-8 w-8 text-[#b8651a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <p className="text-lg font-medium text-gray-700">E-posta doğrulanıyor...</p>
          <p className="text-sm text-gray-500">Lütfen bekleyin</p>
        </motion.div>
      )}

      {/* Success State */}
      {status === 'success' && (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.5 }}
          className="space-y-6"
        >
          <div className="flex flex-col items-center gap-4 p-6 bg-green-50 border border-green-200 rounded-xl text-center">
            <div className="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" className="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h3 className="text-lg font-semibold text-green-800 mb-1">E-posta Adresiniz Doğrulandı!</h3>
              <p className="text-sm text-green-700">
                Hesabınız başarıyla doğrulandı. Artık giriş yapabilirsiniz.
              </p>
            </div>
          </div>

          <div className="text-center">
            <Link href="/login">
              <Button className="h-12 px-8 bg-gradient-to-r from-[#fbeede] to-teal-600 hover:from-[#fbeede] hover:to-teal-700 text-white font-semibold rounded-xl shadow-sm transition-colors duration-150 hover:shadow-md">
                <span className="flex items-center gap-2">
                  <span>Giriş Yap</span>
                  <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </span>
              </Button>
            </Link>
          </div>
        </motion.div>
      )}

      {/* Error State */}
      {status === 'error' && (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.5 }}
          className="space-y-6"
        >
          <div className="flex flex-col items-center gap-4 p-6 bg-red-50 border border-red-200 rounded-xl text-center">
            <div className="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" className="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h3 className="text-lg font-semibold text-red-800 mb-1">Doğrulama Başarısız</h3>
              <p className="text-sm text-red-700">{errorMessage}</p>
            </div>
          </div>

          <div className="flex flex-col items-center gap-3">
            <Button
              onClick={handleResend}
              disabled={isResending}
              className="h-12 px-8 bg-gradient-to-r from-[#fbeede] to-teal-600 hover:from-[#fbeede] hover:to-teal-700 text-white font-semibold rounded-xl shadow-sm transition-colors duration-150 hover:shadow-md"
            >
              <span className="flex items-center gap-2">
                {isResending ? (
                  <>
                    <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Gönderiliyor...</span>
                  </>
                ) : (
                  <>
                    <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Tekrar Gönder</span>
                  </>
                )}
              </span>
            </Button>

            <Link
              href="/login"
              className="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Giriş sayfasına dön</span>
            </Link>
          </div>
        </motion.div>
      )}
    </motion.div>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense fallback={null}>
      <VerifyEmailContent />
    </Suspense>
  );
}
