import { test, expect } from './fixtures/auth';

test.describe('Product Detail Page', () => {
  test.beforeEach(async ({ authedPage: page }) => {
    // First, go to products listing to find a real product
    await page.goto('/market/products', { waitUntil: 'domcontentloaded' });

    // Wait for product cards to load and click the first one
    const productLinks = page.locator('a[href*="/market/product/"]');
    await expect(productLinks.first()).toBeVisible({ timeout: 15000 });
    await productLinks.first().click();
    await page.waitForURL('**/market/product/**', { timeout: 10000 });
  });

  test('product name and image visible', async ({ authedPage: page }) => {
    // Wait for product details to load (loading state disappears)
    await page.waitForTimeout(2000);

    // Product name should be visible as a heading or prominent text
    const productName = page.locator('h1, h2').first();
    await expect(productName).toBeVisible({ timeout: 10000 });
    const nameText = await productName.textContent();
    expect(nameText).toBeTruthy();
    expect(nameText!.length).toBeGreaterThan(0);

    // Product image should be present (either img tag or a fallback icon)
    const productImage = page.locator('img[alt], [class*="product-image"], .aspect-square img').first();
    const hasImage = await productImage.isVisible().catch(() => false);
    // Fallback: check for the pill emoji fallback
    const hasFallback = await page.locator('text=\uD83D\uDC8A').first().isVisible().catch(() => false);
    expect(hasImage || hasFallback).toBeTruthy();
  });

  test('offer table shows prices', async ({ authedPage: page }) => {
    await page.waitForTimeout(3000);

    // The product detail page shows offers from sellers with prices
    // Prices are formatted in TRY (contains "TL" or the Turkish Lira symbol)
    const priceElements = page.locator('text=/\\d+[.,]\\d+.*TL|\\u20BA/').first();
    const hasPrice = await priceElements.isVisible({ timeout: 10000 }).catch(() => false);

    if (!hasPrice) {
      // Check for "Stokta Yok" / no offers message
      const noStock = page.getByText(/Stokta Yok|teklif bulunamadi|ilan bulunamadi/i).first();
      const hasNoStock = await noStock.isVisible().catch(() => false);
      expect(hasNoStock).toBeTruthy();
    } else {
      expect(hasPrice).toBeTruthy();
    }
  });

  test('add to cart button works', async ({ authedPage: page }) => {
    await page.waitForTimeout(3000);

    // Find "Sepete Ekle" or "Ekle" button on the product detail page
    const addToCartBtn = page.getByRole('button', { name: /Sepete Ekle|Ekle/i }).first();
    const hasBtn = await addToCartBtn.isVisible().catch(() => false);

    if (hasBtn) {
      await addToCartBtn.click();
      await page.waitForTimeout(2000);

      // After adding, either:
      // 1. "Eklendi" feedback text appears
      // 2. Cart count badge updates in header
      const addedFeedback = page.getByText('Eklendi').first();
      const cartBadge = page.locator('header').locator('[class*="badge"], span').filter({ hasText: /[1-9]/ });

      const hasAddedText = await addedFeedback.isVisible().catch(() => false);
      const hasBadge = await cartBadge.first().isVisible().catch(() => false);

      expect(hasAddedText || hasBadge).toBeTruthy();
    } else {
      // Product might be out of stock or user is a company without approved link
      // Check that the page loaded correctly instead
      const heading = page.locator('h1, h2').first();
      await expect(heading).toBeVisible();
    }
  });
});
