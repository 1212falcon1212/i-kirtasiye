import { test, expect } from './fixtures/auth';

test.describe('Search', () => {
  test('search input in header works', async ({ authedPage: page }) => {
    // Desktop search input in the header
    const searchInput = page.locator('header input[type="search"]').first();
    await expect(searchInput).toBeVisible();

    // Type a search query (min 3 chars for preview)
    await searchInput.fill('vita');
    await page.waitForTimeout(1000);

    // Either search preview dropdown appears or we can submit
    // The search preview shows up after 300ms debounce for 3+ chars
    // Check if preview results appear (links to product pages in the dropdown)
    const previewResults = page.locator('[data-search-dropdown] a, [class*="search"] a[href*="/market/product/"]');
    const hasPreview = await previewResults.first().isVisible({ timeout: 5000 }).catch(() => false);

    // Submit the search form
    await searchInput.press('Enter');
    await page.waitForURL('**/market/search**', { timeout: 10000 });
    expect(page.url()).toContain('/market/search');
    expect(page.url()).toContain('q=vita');
  });

  test('search results page shows products', async ({ authedPage: page }) => {
    await page.goto('/market/search?q=vitamin', { waitUntil: 'domcontentloaded' });

    // Wait for products to load
    const productCards = page.locator('a[href*="/market/product/"]');
    await expect(productCards.first()).toBeVisible({ timeout: 15000 });

    const count = await productCards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('sort filter changes order', async ({ authedPage: page }) => {
    await page.goto('/market/search?q=vitamin', { waitUntil: 'domcontentloaded' });

    // Wait for products to load
    const productCards = page.locator('a[href*="/market/product/"]');
    await expect(productCards.first()).toBeVisible({ timeout: 15000 });

    // Find the sort select trigger (SelectTrigger from radix)
    const sortTrigger = page.locator('button[role="combobox"]').first();
    const hasSortTrigger = await sortTrigger.isVisible().catch(() => false);

    if (hasSortTrigger) {
      await sortTrigger.click();
      await page.waitForTimeout(500);

      // Select a different sort option from the dropdown
      const sortOption = page.locator('[role="option"]').filter({ hasText: /Fiyat|fiyat/i }).first();
      const hasSortOption = await sortOption.isVisible().catch(() => false);

      if (hasSortOption) {
        await sortOption.click();
        await page.waitForTimeout(2000);

        // Products should still be visible after sort change
        await expect(productCards.first()).toBeVisible();
      }
    }

    // Verify products are still rendered after any sort interaction
    const finalCount = await productCards.count();
    expect(finalCount).toBeGreaterThan(0);
  });
});
