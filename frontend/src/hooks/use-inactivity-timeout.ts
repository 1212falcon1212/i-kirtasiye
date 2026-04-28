'use client';

import { useEffect, useRef, useCallback, useState } from 'react';

interface UseInactivityTimeoutOptions {
  timeoutMs?: number;
  warningMs?: number;
  enabled?: boolean;
  onWarning?: () => void;
  onTimeout?: () => void;
}

interface UseInactivityTimeoutReturn {
  resetTimer: () => void;
  remainingSeconds: number;
  isWarning: boolean;
}

const ACTIVITY_EVENTS = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'] as const;
const THROTTLE_MS = 30_000;

export function useInactivityTimeout({
  timeoutMs = 30 * 60 * 1000,
  warningMs = 60 * 1000,
  enabled = false,
  onWarning,
  onTimeout,
}: UseInactivityTimeoutOptions = {}): UseInactivityTimeoutReturn {
  const [remainingSeconds, setRemainingSeconds] = useState(0);
  const [isWarning, setIsWarning] = useState(false);
  const lastActivityRef = useRef<number>(Date.now());
  const warningTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const timeoutTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const countdownRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const throttleRef = useRef<number>(0);

  const clearTimers = useCallback(() => {
    if (warningTimerRef.current) {
      clearTimeout(warningTimerRef.current);
      warningTimerRef.current = null;
    }
    if (timeoutTimerRef.current) {
      clearTimeout(timeoutTimerRef.current);
      timeoutTimerRef.current = null;
    }
    if (countdownRef.current) {
      clearInterval(countdownRef.current);
      countdownRef.current = null;
    }
  }, []);

  const startCountdown = useCallback(() => {
    const warningSeconds = Math.ceil(warningMs / 1000);
    setRemainingSeconds(warningSeconds);

    countdownRef.current = setInterval(() => {
      setRemainingSeconds((prev) => {
        if (prev <= 1) {
          if (countdownRef.current) clearInterval(countdownRef.current);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
  }, [warningMs]);

  const startTimers = useCallback(() => {
    clearTimers();
    lastActivityRef.current = Date.now();

    const warningDelay = timeoutMs - warningMs;

    warningTimerRef.current = setTimeout(() => {
      setIsWarning(true);
      startCountdown();
      onWarning?.();

      timeoutTimerRef.current = setTimeout(() => {
        setIsWarning(false);
        setRemainingSeconds(0);
        onTimeout?.();
      }, warningMs);
    }, warningDelay);
  }, [timeoutMs, warningMs, onWarning, onTimeout, clearTimers, startCountdown]);

  const resetTimer = useCallback(() => {
    setIsWarning(false);
    setRemainingSeconds(0);
    startTimers();
  }, [startTimers]);

  useEffect(() => {
    if (!enabled) {
      clearTimers();
      setIsWarning(false);
      setRemainingSeconds(0);
      return;
    }

    startTimers();

    const handleActivity = () => {
      const now = Date.now();
      if (now - throttleRef.current < THROTTLE_MS) return;
      throttleRef.current = now;

      if (!isWarning) {
        startTimers();
      }
    };

    ACTIVITY_EVENTS.forEach((event) => {
      window.addEventListener(event, handleActivity, { passive: true });
    });

    return () => {
      clearTimers();
      ACTIVITY_EVENTS.forEach((event) => {
        window.removeEventListener(event, handleActivity);
      });
    };
  }, [enabled, isWarning, startTimers, clearTimers]);

  return { resetTimer, remainingSeconds, isWarning };
}
