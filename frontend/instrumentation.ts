export async function register() {
  // Dev'de Sentry telemetri/tracing hook'ları yüklenmesin: Next.js 16'nın
  // yeni 'AppRender.fetch' span tipiyle "Unexpected root span type" uyarısı
  // tetikliyor. Production'da Sentry aktif kalır.
  if (process.env.NODE_ENV !== 'production') {
    return;
  }

  if (process.env.NEXT_RUNTIME === 'nodejs') {
    await import('./sentry.server.config');
  }
  if (process.env.NEXT_RUNTIME === 'edge') {
    await import('./sentry.edge.config');
  }
}
