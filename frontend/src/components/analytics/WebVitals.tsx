'use client';

import { useReportWebVitals } from 'next/web-vitals';

export function WebVitals() {
  useReportWebVitals((metric) => {
    // Log to console in dev
    if (process.env.NODE_ENV === 'development') {
      console.log(metric);
    }

    // Send to analytics endpoint or Sentry
    if (typeof window !== 'undefined' && '__SENTRY__' in window) {
      // Sentry performance monitoring will capture these automatically
    }
  });

  return null;
}
