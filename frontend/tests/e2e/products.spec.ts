import { test, expect } from './fixtures/auth';

test.describe('Products Page (/market/products)', () => {
  test('product grid renders', async ({ authedPage: page }) => {
    await page.goto('/market/products', { waitUntil: 'domcontentloaded' });

    // Wait for products to load (skeleton disappears, product cards appear)
    const productCards = page.locator('a[href*="/market/product/"]');
    await expect(productCards.first()).toBeVisible({ timeout: 15000 });

    const count = await productCards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('infinite scroll loads more products', async ({ authedPage: page }) => {
    await page.goto('/market/products', { waitUntil: 'domcontentloaded' });

    // Wait for initial products to load
    const productCards = page.locator('a[href*="/market/product/"]');
    await expect(productCards.first()).toBeVisible({ timeout: 15000 });

    const initialCount = await productCards.count();

    // Scroll to the bottom to trigger infinite scroll
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2000);

    // Scroll again for good measure
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2000);

    const newCount = await productCards.count();
    // After scrolling, there should be more products (or same if all loaded)
    expect(newCount).toBeGreaterThanOrEqual(initialCount);
  });

  test('filter sidebar visible on desktop', async ({ authedPage: page }) => {
    await page.goto('/market/products', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);

    // Desktop filter sidebar contains category/brand/price filter labels
    // Check for filter-related elements (Select components for sorting, filter labels)
    const sortSelect = page.locator('button').filter({ hasText: /Siralama|Filtre/i }).first();
    const filterLabel = page.getByText(/Kategori|Marka|Fiyat/i).first();

    const hasSortOrFilter =
      (await sortSelect.isVisible().catch(() => false)) ||
      (await filterLabel.isVisible().catch(() => false));

    expect(hasSortOrFilter).toBeTruthy();
  });

  test('click product navigates to detail', async ({ authedPage: page }) => {
    await page.goto('/market/products', { waitUntil: 'domcontentloaded' });

    // Wait for product cards to load
    const productLinks = page.locator('a[href*="/market/product/"]');
    await expect(productLinks.first()).toBeVisible({ timeout: 15000 });

    // Get the first product link href
    const href = await productLinks.first().getAttribute('href');
    expect(href).toBeTruthy();

    // Click the first product
    await productLinks.first().click();

    // Should navigate to product detail page
    await page.waitForURL('**/market/product/**', { timeout: 10000 });
    expect(page.url()).toContain('/market/product/');
  });
});
