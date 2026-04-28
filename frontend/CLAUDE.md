# Frontend - B2B Eczane

## Tech Stack

- **Framework:** Next.js 16 (App Router)
- **UI:** React 19, TypeScript strict mode
- **Styling:** Tailwind CSS 4, shadcn/ui (Radix primitives)
- **State:** Zustand (persist middleware), React Context (AuthContext)
- **Forms:** React Hook Form + Zod validation
- **Icons:** lucide-react
- **Notifications:** sonner (toast)

## Directory Structure

```
src/
├── app/
│   ├── (auth)/          # Login, register (public, minimal layout)
│   ├── (dashboard)/     # Protected admin/dashboard pages
│   ├── (public)/        # Public pages (landing, blog)
│   ├── market/          # Marketplace (authenticated)
│   │   ├── product/[id] # Product detail
│   │   ├── sepet/       # Cart
│   │   └── hesabim/     # Account & orders
│   └── seller/          # Seller panel
├── components/
│   ├── ui/              # shadcn/ui base components (Button, Skeleton, Dialog...)
│   └── market/          # Marketplace components (ProductCard, MarketHeader...)
├── stores/              # Zustand stores (useCartStore, useNotificationStore)
├── contexts/            # AuthContext (token, user, login/logout)
├── hooks/               # Custom hooks (use-debounce, use-inactivity-timeout)
└── lib/
    ├── api.ts           # ApiClient singleton with caching, token management
    └── utils.ts         # cn() helper (clsx + tailwind-merge)
```

## Key Patterns

### API Client (`lib/api.ts`)
- Singleton `api` instance with `setToken()`, `get()`, `post()`, `put()`, `delete()`
- Built-in GET cache with TTL per endpoint
- Returns `ApiResponse<T>` with `data`, `error`, `status`
- Named exports: `api`, `cartApi`, `wishlistApi`, etc.

### Components
- `'use client'` directive only when needed (hooks, event handlers)
- Props defined as interfaces above the component
- Use `React.memo` for expensive render components (e.g., ProductCard)
- Import UI from `@/components/ui/*` (Button, Skeleton, etc.)
- All user-facing text must be in Turkish

### State Management
- **AuthContext:** Token, user info, `isAuthenticated`, `isLoading`
- **Zustand stores:** Cart state persisted to localStorage, notification state
- Pattern: `create<State>()(persist((set, get) => ({ ... }), { name: 'key' }))`

### Layouts
- `market/layout.tsx`: Authenticated layout with header/footer, redirects to login
- Uses `medicalBgImage` background pattern
- Loading state shows skeleton header to prevent CLS

### Styling
- Tailwind utility-first, dark mode via `dark:` prefix
- Semantic colors: `slate-*` for neutrals, `destructive` for errors
- Responsive: mobile-first with `sm:`, `md:`, `lg:`, `xl:` breakpoints
- Consistent spacing: `p-4`, `gap-4`, `space-y-4`

## Commands

```bash
npm run dev          # Development server (port 3000)
npm run build        # Production build
npx tsc --noEmit     # Type check
```

## Naming Conventions

- Components: PascalCase (`ProductCard.tsx`)
- Hooks/stores: camelCase with `use` prefix (`useCartStore.ts`)
- Pages: `page.tsx`, layouts: `layout.tsx`, errors: `error.tsx`, loading: `loading.tsx`
- Path aliases: `@/` maps to `src/`
