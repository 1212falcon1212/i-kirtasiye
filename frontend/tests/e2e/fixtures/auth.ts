import { test as base, expect, Page } from '@playwright/test';

const API_URL = 'http://localhost:8001/api';
const TEST_EMAIL = 'eczane4@test.com';
const TEST_PASSWORD = 'password123';

interface AuthTokenResponse {
  user: {
    id: number;
    email: string;
    pharmacy_name?: string;
    role?: string;
  };
  token: string;
  message: string;
}

let cachedAuth: { token: string; user: AuthTokenResponse['user'] } | null = null;

async function getAuthToken(): Promise<{ token: string; user: AuthTokenResponse['user'] }> {
  if (cachedAuth) return cachedAuth;

  const response = await fetch(`${API_URL}/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ email: TEST_EMAIL, password: TEST_PASSWORD }),
  });

  if (!response.ok) {
    throw new Error(`Login API failed with status ${response.status}`);
  }

  const data: AuthTokenResponse = await response.json();
  cachedAuth = { token: data.token, user: data.user };
  return cachedAuth;
}

/**
 * Authenticated test fixture.
 * Logs in via API, injects token into localStorage before each test.
 */
export const test = base.extend<{ authedPage: Page }>({
  authedPage: async ({ page }, use) => {
    const { token, user } = await getAuthToken();

    // Navigate to the app origin first so we can set localStorage
    await page.goto('/login', { waitUntil: 'domcontentloaded' });

    // Inject token and user into localStorage
    await page.evaluate(
      ({ token, user }) => {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
      },
      { token, user }
    );

    // Navigate to market to confirm auth
    await page.goto('/market', { waitUntil: 'domcontentloaded' });

    await use(page);
  },
});

export { expect };
