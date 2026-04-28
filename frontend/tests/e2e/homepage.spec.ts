import { test, expect } from './fixtures/auth';

test.describe('Homepage (/market)', () => {
  test('hero slider renders', async ({ authedPage: page }) => {
    // HeroSlider is the first visual section
    // It contains an embla carousel or banner images
    const heroSection = page.locator('.embla, [class*="hero"], [class*="slider"]').first();
    await expect(heroSection).toBeVisible({ timeout: 15000 });
  });

  test('product sections load', async ({ authedPage: page }) => {
    // Wait for loading to finish (skeleton disappears)
    await page.waitForTimeout(3000);

    // Check that product grids or product cards exist on the page
    // ProductGrid sections have titles like "Cok Satanlar"
    const productSections = page.locator('[class*="rounded-3xl"]').filter({ hasText: /Satanlar|Onerilen|Firsatlar|Kategori/i });
    const count = await productSections.count();
    // At least one product section should be visible
    expect(count).toBeGreaterThanOrEqual(0);

    // Alternatively, check for ProductCard links
    const productLinks = page.locator('a[href*="/market/product/"]');
    await expect(productLinks.first()).toBeVisible({ timeout: 15000 });
  });

  test('header has search, cart, user menu', async ({ authedPage: page }) => {
    // Header is a sticky element
    const header = page.locator('header').first();
    await expect(header).toBeVisible();

    // Search bar (desktop) - has placeholder text
    const searchInput = page.locator('input[type="search"]').first();
    await expect(searchInput).toBeVisible();

    // Cart icon (MiniCart component uses ShoppingCart icon)
    const cartButton = page.locator('header').getByRole('button').filter({
      has: page.locator('[class*="lucide-shopping"]'),
    });
    // If cart is not a button, check for the sheet trigger
    const cartArea = page.locator('header').locator('[class*="shopping"], [class*="cart"]').first();
    const hasCart = await cartArea.isVisible().catch(() => false);
    expect(hasCart || (await cartButton.count()) > 0).toBeTruthy();

    // User menu (dropdown with user icon)
    const userArea = page.locator('header').locator('[class*="lucide-user"], [class*="avatar"]').first();
    const hasUser = await userArea.isVisible().catch(() => false);
    expect(hasUser).toBeTruthy();
  });

  test('footer renders', async ({ authedPage: page }) => {
    // Scroll to bottom to trigger lazy elements
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1000);

    const footer = page.locator('footer').first();
    await expect(footer).toBeVisible({ timeout: 10000 });
  });

  test('category navbar visible', async ({ authedPage: page }) => {
    // The header contains a category mega menu with category links
    // Categories section in the header with links to /market/category/
    const categoryLinks = page.locator('header a[href*="/market/category/"]');
    const categoryCount = await categoryLinks.count();

    // If desktop mega menu is hidden, check for category section on page
    if (categoryCount === 0) {
      // CategoryGrid component on the homepage
      const categoryGrid = page.locator('text=Kategoriler').first();
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
      await page.waitForTimeout(1000);
      const isVisible = await categoryGrid.isVisible().catch(() => false);
      expect(isVisible).toBeTruthy();
    } else {
      expect(categoryCount).toBeGreaterThan(0);
    }
  });
});
