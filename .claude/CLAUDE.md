# UCES Core Directives

> Reference: ~/.claude/CONVENTIONS.md for detailed standards

---

## Intelligent Routing

Route requests to the appropriate skill module:

| Keywords | Module |
|----------|--------|
| component, page, UI, form, button, modal, React, Next.js, Tailwind, styling | `ui` |
| API, endpoint, route, controller, service, model, Laravel, backend | `laravel-api` |
| admin, filament, panel, settings page, resource | `filament` |
| bug, error, fix, debug, broken, failing, test, spec | `debug` |
| plan, design, architecture, PRD, spec, requirements, system | `architect` |
| analytics, metrics, query, data, dashboard, report, KPI, SQL | `data` |
| deploy, push, canli, production, sunucu, server, pm2, ssh | `deploy` |

---

## Zero-Tolerance Enforcement

### Blocked Patterns
- Placeholder data → Connect real sources
- Deferred implementations → Complete now
- Stub handlers → Full logic required
- Partial state handling → All states mandatory
- Loose typing → Strict TypeScript
- Silent failures → User feedback required

### Completion Criteria
```
[✓] Integrated with actual data sources
[✓] States covered: loading, error, empty, success
[✓] Errors surface to user appropriately
[✓] TypeScript: zero errors
[✓] Production-ready quality
```

---

## Verification Protocol

### Before marking complete:

1. **Execute validation:**
   ```bash
   npx tsc --noEmit    # Must pass
   npm test            # Must pass
   ```

2. **UI deliverables:** Describe rendered state

3. **API deliverables:** Show response structure

4. **Uncertainty:** Explicitly state unknowns

### Communication standards:
- Never claim unverified success
- Surface all warnings/errors
- Ask rather than assume

---

## Implementation Templates

### API Endpoint
```typescript
// 1. Authentication
const session = await getSession()
if (!session) return unauthorized()

// 2. Input validation
const validated = Schema.safeParse(input)
if (!validated.success) return badRequest(validated.error)

// 3. Authorization
if (resource.ownerId !== session.userId) return forbidden()

// 4. Execution with error handling
try {
  const result = await execute(validated.data)
  return success(result)
} catch (e) {
  log.error(e)
  return serverError()
}
```

### UI Component
```typescript
// Mandatory state handling
if (loading) return <LoadingSkeleton />
if (error) return <ErrorDisplay retry={refetch} />
if (empty) return <EmptyState action={<CreateNew />} />
return <Content data={data} />
```

### Form Handler
```typescript
// Complete form pattern
<form onSubmit={handleSubmit}>
  <Field {...register('name')} error={errors.name} />
  <Submit loading={submitting}>
    {submitting ? 'Processing...' : 'Submit'}
  </Submit>
</form>
```

---

## Module Reference

| Module | Use Case |
|--------|----------|
| `ui` | React/Next.js frontend components |
| `laravel-api` | Laravel controllers, services, models |
| `filament` | Admin panel pages & resources |
| `debug` | Bug fixing & testing |
| `architect` | System design & planning |
| `data` | MySQL queries & analytics |
| `deploy` | VPS deployment (SSH/PM2) |

---

## External Documentation

```
1. resolveLibrary({ name: "library", query: "topic" })
2. queryDocs({ libraryId: "/org/lib", query: "specific question" })
```

---

## Technology Stack

**Backend:** Laravel 12, PHP 8.4, MySQL, Sanctum, Filament, Eloquent ORM

**Frontend:** Next.js 16, React 19, TypeScript strict, Tailwind CSS, shadcn/ui, Zustand

**Deploy:** VPS (i-kirtasiye.com), PM2, SSH, GitHub webhooks

---

## Session Memory

Capture discoveries during work:

```bash
echo '{"category":"discovery","content":"Description"}' | bash .claude/hooks/memory.sh
```

Categories: `discovery`, `pattern`, `note`, `warning`

Learnings persist across sessions.

---

## Restrictions

- No environment file modifications
- No loose typing (any/unknown without assertion)
- No unauthenticated API routes
- No placeholder implementations
- No deferred work (TODO/FIXME)
- No incomplete state handling
- No force push without confirmation
- No unverified completion claims
