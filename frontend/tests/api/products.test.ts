import { describe, it, assert, assertOk, assertType, request } from './helpers';

let firstProductId: number | null = null;

export async function runProductsTests(): Promise<void> {
  await describe('Products - List', async () => {
    await it('should return paginated product list', async () => {
      const res = await request('GET', '/products');
      assertOk(res.status, 'products list status');
      assert(!!res.data, 'response has data');
      const data = res.data!;
      assert(Array.isArray(data.products) || Array.isArray(data.data), 'has products array');
      const products = (data.products || data.data) as Record<string, unknown>[];
      if (products.length > 0) {
        firstProductId = products[0].id as number;
        assert(typeof products[0].name === 'string', 'product has name');
        assert(typeof products[0].id === 'number', 'product has id');
      }
    });

    await it('should support pagination', async () => {
      const res = await request('GET', '/products?page=1&per_page=5');
      assertOk(res.status, 'paginated products status');
      assert(!!res.data, 'response has data');
      const data = res.data!;
      const pagination = data.pagination as Record<string, unknown> | undefined;
      if (pagination) {
        assert(typeof pagination.current_page === 'number', 'has current_page');
        assert(typeof pagination.per_page === 'number', 'has per_page');
      }
    });

    await it('should support page 2', async () => {
      const res = await request('GET', '/products?page=2&per_page=5');
      assertOk(res.status, 'page 2 status');
    });
  });

  await describe('Products - Search', async () => {
    await it('should search products by query', async () => {
      const res = await request('GET', '/products/search?q=vitamin');
      assertOk(res.status, 'search status');
      assert(!!res.data, 'response has data');
    });

    await it('should return empty results for gibberish query', async () => {
      const res = await request('GET', '/products/search?q=xyznonexistent999');
      assertOk(res.status, 'empty search status');
      assert(!!res.data, 'response has data');
      const products = (res.data!.products || res.data!.data || []) as unknown[];
      assert(products.length === 0, 'no products for gibberish query');
    });
  });

  await describe('Products - Detail', async () => {
    await it('should return product detail by ID', async () => {
      if (!firstProductId) {
        // Try to fetch first product
        const listRes = await request('GET', '/products?per_page=1');
        const products = ((listRes.data?.products || listRes.data?.data) as Record<string, unknown>[]) || [];
        if (products.length > 0) firstProductId = products[0].id as number;
      }
      if (!firstProductId) {
        throw new Error('No products available to test detail endpoint');
      }
      const res = await request('GET', `/products/${firstProductId}`);
      assertOk(res.status, 'product detail status');
      assert(!!res.data, 'response has data');
      const product = (res.data!.product || res.data!) as Record<string, unknown>;
      assert(typeof product.name === 'string', 'product has name');
    });

    await it('should return 404 for non-existent product', async () => {
      const res = await request('GET', '/products/999999');
      assert(res.status === 404, `expected 404, got ${res.status}`);
    });

    await it('should return offers for a product', async () => {
      if (!firstProductId) return;
      const res = await request('GET', `/products/${firstProductId}/offers`);
      assertOk(res.status, 'product offers status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('Products - Filters', async () => {
    await it('should filter by category slug', async () => {
      const res = await request('GET', '/products?category=ilac');
      // May return empty if no products in this category
      assertOk(res.status, 'category filter status');
    });

    await it('should filter by min_price and max_price', async () => {
      const res = await request('GET', '/products?min_price=10&max_price=100');
      assertOk(res.status, 'price filter status');
    });

    await it('should sort by price ascending', async () => {
      const res = await request('GET', '/products?sort_by=price_asc');
      assertOk(res.status, 'sort status');
    });
  });
}
