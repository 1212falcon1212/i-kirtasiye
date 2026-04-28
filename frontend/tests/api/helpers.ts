const BASE_URL = 'http://localhost:8001/api';

let passCount = 0;
let failCount = 0;
let skipCount = 0;
let currentSuite = '';
const errors: { suite: string; test: string; error: string }[] = [];

const GREEN = '\x1b[32m';
const RED = '\x1b[31m';
const YELLOW = '\x1b[33m';
const CYAN = '\x1b[36m';
const DIM = '\x1b[2m';
const BOLD = '\x1b[1m';
const RESET = '\x1b[0m';

export function describe(name: string, fn: () => void | Promise<void>): Promise<void> {
  currentSuite = name;
  console.log(`\n${BOLD}${CYAN}━━━ ${name} ━━━${RESET}`);
  return Promise.resolve(fn());
}

export async function it(name: string, fn: () => Promise<void>): Promise<void> {
  try {
    await fn();
    passCount++;
    console.log(`  ${GREEN}✓${RESET} ${name}`);
  } catch (err: unknown) {
    failCount++;
    const message = err instanceof Error ? err.message : String(err);
    console.log(`  ${RED}✗${RESET} ${name}`);
    console.log(`    ${RED}${message}${RESET}`);
    errors.push({ suite: currentSuite, test: name, error: message });
  }
}

export function assert(condition: boolean, msg: string): void {
  if (!condition) {
    throw new Error(`Assertion failed: ${msg}`);
  }
}

export function assertEqual(a: unknown, b: unknown, label: string): void {
  if (a !== b) {
    throw new Error(`${label}: expected ${JSON.stringify(b)}, got ${JSON.stringify(a)}`);
  }
}

export function assertIncludes(arr: unknown[], value: unknown, label: string): void {
  if (!arr.includes(value)) {
    throw new Error(`${label}: expected array to include ${JSON.stringify(value)}`);
  }
}

export function assertType(value: unknown, type: string, label: string): void {
  if (typeof value !== type) {
    throw new Error(`${label}: expected type ${type}, got ${typeof value}`);
  }
}

export function assertOk(status: number, label: string): void {
  if (status < 200 || status >= 300) {
    throw new Error(`${label}: expected 2xx status, got ${status}`);
  }
}

export function assertStatus(actual: number, expected: number, label: string): void {
  if (actual !== expected) {
    throw new Error(`${label}: expected status ${expected}, got ${actual}`);
  }
}

export interface ApiResult {
  status: number;
  data: Record<string, unknown> | null;
  headers: Headers;
}

export async function request(
  method: string,
  endpoint: string,
  body?: Record<string, unknown> | null,
  token?: string
): Promise<ApiResult> {
  const url = `${BASE_URL}${endpoint}`;
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const options: RequestInit = { method, headers };
  if (body && ['POST', 'PUT', 'PATCH'].includes(method.toUpperCase())) {
    options.body = JSON.stringify(body);
  }

  const response = await fetch(url, options);

  let data: Record<string, unknown> | null = null;
  const contentType = response.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    data = (await response.json()) as Record<string, unknown>;
  }

  return { status: response.status, data, headers: response.headers };
}

const tokenCache = new Map<string, string>();

export async function loginAs(email: string, password: string): Promise<string> {
  // Return cached token to avoid rate limiting
  const cached = tokenCache.get(email);
  if (cached) return cached;

  const res = await request('POST', '/auth/login', { email, password });
  if (res.status !== 200 || !res.data) {
    throw new Error(`Login failed for ${email}: status ${res.status}, body: ${JSON.stringify(res.data)?.slice(0, 200)}`);
  }
  const token = res.data.token as string;
  if (!token) {
    throw new Error(`Login response missing token for ${email}: ${JSON.stringify(res.data)?.slice(0, 200)}`);
  }
  tokenCache.set(email, token);
  return token;
}

export function printResults(): void {
  const total = passCount + failCount;
  console.log(`\n${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}`);
  console.log(`${BOLD}  Test Results${RESET}`);
  console.log(`${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}`);
  console.log(`  ${GREEN}Passed: ${passCount}${RESET}`);
  console.log(`  ${RED}Failed: ${failCount}${RESET}`);
  console.log(`  ${DIM}Total:  ${total}${RESET}`);

  if (errors.length > 0) {
    console.log(`\n${RED}${BOLD}  Failed Tests:${RESET}`);
    for (const e of errors) {
      console.log(`  ${RED}✗ [${e.suite}] ${e.test}${RESET}`);
      console.log(`    ${DIM}${e.error}${RESET}`);
    }
  }

  console.log(`${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}\n`);

  if (failCount > 0) {
    process.exitCode = 1;
  }
}

export function getPassCount(): number {
  return passCount;
}

export function getFailCount(): number {
  return failCount;
}
