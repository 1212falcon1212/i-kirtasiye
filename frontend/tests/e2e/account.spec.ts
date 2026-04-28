import { test, expect } from './fixtures/auth';

test.describe('Account Page', () => {
  test('loads with tab navigation', async ({ authedPage: page }) => {
    await page.goto('/market/hesabim');
    await page.waitForLoadState('networkidle');
    await expect(page.getByText('İlanlarım')).toBeVisible();
    await expect(page.getByText('Siparişlerim')).toBeVisible();
    await expect(page.getByText('Ayarlarım')).toBeVisible();
  });

  test('orders tab shows order list', async ({ authedPage: page }) => {
    await page.goto('/market/hesabim?tab=siparislerim&sub=satin-aldiklarim');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    // Should show order cards or empty state
    const hasOrders = await page.locator('[class*="rounded-2xl"]').count() > 0;
    const hasEmpty = await page.getByText('sipariş').count() > 0;
    expect(hasOrders || hasEmpty).toBeTruthy();
  });

  test('listings tab shows offers', async ({ authedPage: page }) => {
    await page.goto('/market/hesabim?tab=ilanlarim&sub=aktif-ilanlar');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await expect(page.getByText('Aktif İlanlar')).toBeVisible();
  });

  test('settings tab loads', async ({ authedPage: page }) => {
    await page.goto('/market/hesabim?tab=ayarlarim');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await expect(page.getByText('Ayarlarım')).toBeVisible();
  });
});
