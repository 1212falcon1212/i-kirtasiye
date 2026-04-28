import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runWishlistTests(): Promise<void> {
  let token = '';
  let testProductId: number | null = null;

  token = await loginAs('eczane4@test.com', 'password123');

  // Find a product to use
  const productsRes = await request('GET', '/products?per_page=1');
  const products = (productsRes.data?.products || productsRes.data?.data || []) as Record<string, unknown>[];
  if (products.length > 0) {
    testProductId = products[0].id as number;
  }

  await describe('Wishlist - Toggle', async () => {
    await it('should toggle a product into the wishlist', async () => {
      if (!testProductId) throw new Error('No product available for wishlist test');
      const res = await request('POST', '/wishlist/toggle', { product_id: testProductId }, token);
      assertOk(res.status, 'toggle wishlist status');
      assert(!!res.data, 'response has data');
      assert(typeof res.data!.in_wishlist === 'boolean', 'has in_wishlist flag');
    });

    await it('should reject toggle without auth', async () => {
      const res = await request('POST', '/wishlist/toggle', { product_id: 1 });
      assert(res.status === 401, `expected 401, got ${res.status}`);
    });
  });

  await describe('Wishlist - List', async () => {
    await it('should return wishlist items', async () => {
      const res = await request('GET', '/wishlist', null, token);
      assertOk(res.status, 'wishlist list status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('Wishlist - Cleanup', async () => {
    await it('should toggle product back out of wishlist', async () => {
      if (!testProductId) return;
      // Toggle again to remove
      const res = await request('POST', '/wishlist/toggle', { product_id: testProductId }, token);
      assertOk(res.status, 'toggle back status');
    });
  });
}
