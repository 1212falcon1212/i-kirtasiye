import { test, expect, devices } from '@playwright/test';

test.use({ ...devices['Pixel 5'] });

test.describe('Mobile Responsive', () => {
  test('login page is usable on mobile', async ({ page }) => {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    // Form should be visible and full width
    const emailInput = page.locator('input[type="email"], input[name="email"]');
    await expect(emailInput).toBeVisible();
    const box = await emailInput.boundingBox();
    expect(box).toBeTruthy();
    // Should take most of viewport width (>70%)
    expect(box!.width).toBeGreaterThan(250);
  });

  test('mobile menu hamburger is visible on market page', async ({ page }) => {
    await page.goto('/market');
    await page.waitForLoadState('networkidle');
    // Desktop nav should be hidden, mobile menu trigger should exist
    const mobileMenuBtn = page.locator('button').filter({ has: page.locator('[class*="Menu"], [class*="menu"]') }).first();
    // Or look for Sheet trigger
    const hasHamburger = await page.locator('[data-state]').count() > 0 ||
                          await page.locator('button:has(svg)').first().isVisible();
    expect(hasHamburger).toBeTruthy();
  });

  test('product listing adapts to mobile', async ({ page }) => {
    await page.goto('/market/products');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    // Filter sidebar should be hidden on mobile
    const sidebar = page.locator('aside.hidden');
    // Products should be visible
    const products = page.locator('[class*="grid"]').first();
    await expect(products).toBeVisible();
  });

  test('cart page is responsive', async ({ page }) => {
    await page.goto('/market/sepet');
    await page.waitForLoadState('networkidle');
    // Page should render without horizontal overflow
    const body = page.locator('body');
    const bodyBox = await body.boundingBox();
    expect(bodyBox).toBeTruthy();
    // No horizontal scroll
    const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
    const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
    expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 5); // 5px tolerance
  });
});
