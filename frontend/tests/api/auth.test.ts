import { describe, it, assert, assertEqual, assertOk, assertStatus, request, loginAs } from './helpers';

export async function runAuthTests(): Promise<void> {
  let token = '';

  await describe('Auth - Login', async () => {
    await it('should login with valid credentials', async () => {
      const res = await request('POST', '/auth/login', {
        email: 'eczane4@test.com',
        password: 'password123',
      });
      assertOk(res.status, 'login status');
      assert(!!res.data, 'response has data');
      assert(typeof res.data!.token === 'string', 'token is a string');
      assert(!!res.data!.user, 'response has user');
      token = res.data!.token as string;
    });

    await it('should reject invalid password', async () => {
      const res = await request('POST', '/auth/login', {
        email: 'eczane4@test.com',
        password: 'wrongpassword',
      });
      assert(res.status === 401 || res.status === 422, `expected 401 or 422, got ${res.status}`);
    });

    await it('should reject non-existent email', async () => {
      const res = await request('POST', '/auth/login', {
        email: 'nonexistent@nowhere.com',
        password: 'password123',
      });
      assert(res.status === 401 || res.status === 422, `expected 401 or 422, got ${res.status}`);
    });

    await it('should reject missing fields', async () => {
      const res = await request('POST', '/auth/login', {});
      assert(res.status === 422 || res.status === 401, `expected 422 or 401, got ${res.status}`);
    });
  });

  await describe('Auth - Get User', async () => {
    await it('should return current user with valid token', async () => {
      const res = await request('GET', '/auth/user', null, token);
      assertOk(res.status, 'get user status');
      assert(!!res.data, 'response has data');
      const user = (res.data!.user || res.data) as Record<string, unknown>;
      assert(typeof user.id === 'number', 'user has id');
      assert(typeof user.email === 'string', 'user has email');
      assertEqual(user.email, 'eczane4@test.com', 'user email');
    });

    await it('should reject request without token', async () => {
      const res = await request('GET', '/auth/user');
      assertStatus(res.status, 401, 'unauthenticated');
    });

    await it('should reject request with invalid token', async () => {
      const res = await request('GET', '/auth/user', null, 'invalid-token-12345');
      assertStatus(res.status, 401, 'invalid token');
    });
  });

  await describe('Auth - Logout', async () => {
    await it('should logout successfully', async () => {
      // Login with direct request (not cached) to get a disposable token
      const loginRes = await request('POST', '/auth/login', { email: 'eczane4@test.com', password: 'password123' });
      const disposableToken = (loginRes.data as any)?.token;
      assert(!!disposableToken, 'got disposable token');
      const res = await request('POST', '/auth/logout', null, disposableToken);
      assertOk(res.status, 'logout status');
    });

    await it('should invalidate token after logout', async () => {
      const loginRes = await request('POST', '/auth/login', { email: 'eczane4@test.com', password: 'password123' });
      const disposableToken = (loginRes.data as any)?.token;
      assert(!!disposableToken, 'got disposable token');
      await request('POST', '/auth/logout', null, disposableToken);
      const res = await request('GET', '/auth/user', null, disposableToken);
      assertStatus(res.status, 401, 'token invalidated after logout');
    });
  });
}
