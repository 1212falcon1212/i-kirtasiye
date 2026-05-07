/**
 * Route-level loading UI shown while the market segment is fetching its
 * data on first navigation. Mirrors the actual layout (hero strip + chip
 * row + 2-column primary block) so the visual height stays stable and we
 * avoid CLS when the real content hydrates.
 *
 * NOTE: `market/layout.tsx` renders its own auth-gated skeleton when the
 * AuthContext is still loading; that branch wins on first paint. This
 * file is mainly observed during in-app navigations to /market.
 */
export default function MarketLoading() {
    return (
        <div className="min-h-screen pb-20">
            {/* Hero band */}
            <div
                className="w-full mx-auto animate-pulse"
                style={{
                    maxWidth: 1920,
                    aspectRatio: '4 / 1',
                    background: 'var(--bg-muted)',
                }}
                aria-hidden
            />

            {/* Info chip row */}
            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-4">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {Array.from({ length: 3 }).map((_, i) => (
                        <div
                            key={i}
                            className="h-16 rounded-lg animate-pulse"
                            style={{ background: 'var(--bg-muted)' }}
                        />
                    ))}
                </div>
            </section>

            {/* Daily deal + quick order */}
            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-6">
                <div className="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-4">
                    <div
                        className="h-60 rounded-lg animate-pulse"
                        style={{ background: 'var(--bg-muted)' }}
                    />
                    <div
                        className="h-60 rounded-lg animate-pulse"
                        style={{ background: 'var(--bg-muted)' }}
                    />
                </div>
            </section>

            {/* Weekly highlights */}
            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {Array.from({ length: 2 }).map((_, i) => (
                        <div
                            key={i}
                            className="h-72 rounded-lg animate-pulse"
                            style={{ background: 'var(--bg-muted)' }}
                        />
                    ))}
                </div>
            </section>
        </div>
    );
}
