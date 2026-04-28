import { test, expect } from '@playwright/test';

test.describe('Login Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);
  });

  test('page loads with login form', async ({ page }) => {
    await expect(page.getByText('Hoş Geldiniz')).toBeVisible();
    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.getByText('Giriş Yap', { exact: false })).toBeVisible();
    await expect(page.getByText('Kayıt Olun')).toBeVisible();
  });

  test('valid login redirects to /market', async ({ page }) => {
    await page.locator('#email').fill('eczane4@test.com');
    await page.locator('#password').fill('password123');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL('**/market**', { timeout: 15000 });
    expect(page.url()).toContain('/market');
  });

  test('invalid login shows error', async ({ page }) => {
    await page.locator('#email').fill('invalid@test.com');
    await page.locator('#password').fill('wrongpassword');
    await page.locator('button[type="submit"]').click();
    await page.waitForTimeout(3000);
    // Error message or toast should appear
    const hasError = await page.locator('[class*="red"], [class*="error"], [class*="destructive"], [role="alert"]').count() > 0 ||
                     await page.getByText('Geçersiz').count() > 0;
    expect(hasError).toBeTruthy();
  });

  test('empty form shows validation', async ({ page }) => {
    await page.locator('button[type="submit"]').click();
    expect(page.url()).toContain('/login');
    const isInvalid = await page.locator('#email').evaluate(
      (el) => !(el as HTMLInputElement).checkValidity()
    );
    expect(isInvalid).toBe(true);
  });
});
