'use client';

import React, { createContext, useContext, useEffect, useState, useCallback, ReactNode } from 'react';
import { api, authApi, documentsApi, User } from '@/lib/api';
import { useRouter, usePathname } from 'next/navigation';
import { useInactivityTimeout } from '@/hooks/use-inactivity-timeout';
import { InactivityWarningDialog } from '@/components/auth/InactivityWarningDialog';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  documentsApproved: boolean | null;
  checkingDocuments: boolean;
  login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
  logout: () => Promise<void>;
  setUser: (user: User | null) => void;
  checkDocumentStatus: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Routes that don't require document approval
const PUBLIC_ROUTES = ['/login', '/register', '/documents'];
const PUBLIC_ROUTE_PREFIXES = ['/legal/', '/hakkimizda', '/iletisim', '/yardim'];

function isPublicPath(path: string): boolean {
    if (PUBLIC_ROUTES.includes(path)) {
        return true;
    }
    return PUBLIC_ROUTE_PREFIXES.some(prefix =>
        path === prefix || path.startsWith(prefix.endsWith('/') ? prefix : prefix + '/')
    );
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [documentsApproved, setDocumentsApproved] = useState<boolean | null>(null); // null = not checked yet
  const [checkingDocuments, setCheckingDocuments] = useState(true); // Start as true
  const [showInactivityWarning, setShowInactivityWarning] = useState(false);
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    checkAuth();
  }, []);

  // Check document status and redirect if needed
  useEffect(() => {
    if (!isLoading && user && !checkingDocuments) {
      // Super admins bypass document check
      if (user.role === 'super-admin') {
        setDocumentsApproved(true);
        return;
      }

      // Only redirect if explicitly not approved (documentsApproved === false)
      // Don't redirect if null (not checked yet) or true (approved)
      if (documentsApproved === false && !isPublicPath(pathname)) {
        router.push('/documents');
      }
    }
  }, [user, isLoading, documentsApproved, pathname, checkingDocuments]);

  const checkAuth = async () => {
    const token = api.getToken();
    if (!token) {
      setIsLoading(false);
      setCheckingDocuments(false);
      return;
    }

    const response = await authApi.getUser();
    if (response.data) {
      setUser(response.data.user);
      // Check document status after getting user
      await checkDocumentStatus();
    } else {
      api.setToken(null);
      setCheckingDocuments(false);
    }
    setIsLoading(false);
  };

  const checkDocumentStatus = async () => {
    setCheckingDocuments(true);
    try {
      const response = await documentsApi.getStatus();
      if (response.data) {
        setDocumentsApproved(response.data.documents_approved);
      }
    } catch (error) {
      console.error('Failed to check document status:', error);
    } finally {
      setCheckingDocuments(false);
    }
  };

  const login = async (email: string, password: string) => {
    const response = await authApi.login(email, password);

    if (response.data) {
      api.setToken(response.data.token);
      setUser(response.data.user);

      // Check document status after login
      await checkDocumentStatus();

      return { success: true };
    }

    return { success: false, error: response.error };
  };

  const logout = useCallback(async () => {
    setShowInactivityWarning(false);
    await authApi.logout();
    api.setToken(null);
    setUser(null);
    setDocumentsApproved(null);
    setCheckingDocuments(true);
    router.push('/login');
  }, [router]);

  // Inactivity timeout
  const handleInactivityWarning = useCallback(() => {
    setShowInactivityWarning(true);
  }, []);

  const handleInactivityTimeout = useCallback(() => {
    setShowInactivityWarning(false);
    logout();
  }, [logout]);

  const { resetTimer, remainingSeconds, isWarning } = useInactivityTimeout({
    timeoutMs: 30 * 60 * 1000,
    warningMs: 60 * 1000,
    enabled: !!user,
    onWarning: handleInactivityWarning,
    onTimeout: handleInactivityTimeout,
  });

  const handleContinueSession = useCallback(() => {
    setShowInactivityWarning(false);
    resetTimer();
  }, [resetTimer]);

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user,
        documentsApproved,
        checkingDocuments,
        login,
        logout,
        setUser,
        checkDocumentStatus,
      }}
    >
      {children}
      <InactivityWarningDialog
        open={showInactivityWarning}
        remainingSeconds={remainingSeconds}
        onContinue={handleContinueSession}
        onLogout={logout}
      />
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
