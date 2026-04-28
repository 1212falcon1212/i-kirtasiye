import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runCartTests(): Promise<void> {
  let token = '';
  let addedItemId: number | null = null;
  let testOfferId: number | null = null;

  // Login once for all cart tests
  token = await loginAs('eczane4@test.com', 'password123');

  await describe('Cart - Setup (find an offer)', async () => {
    await it('should find an active offer to use in cart tests', async () => {
      // Fetch products and find one with offers
      const res = await request('GET', '/products?per_page=20');
      assertOk(res.status, 'products list');
      const products = (res.data?.products || res.data?.data || []) as Record<string, unknown>[];

      for (const product of products) {
        const offersRes = await request('GET', `/products/${product.id}/offers`);
        if (offersRes.status === 200 && offersRes.data) {
          const offers = (offersRes.data.offers || []) as Record<string, unknown>[];
          // Find an offer not from the test user (can't buy own offer)
          const validOffer = offers.find(
            (o) => (o.status === 'active') && (o.stock as number) > 0
          );
          if (validOffer) {
            testOfferId = validOffer.id as number;
            break;
          }
        }
      }
      assert(testOfferId !== null, 'found a valid offer for cart tests');
    });
  });

  await describe('Cart - Clear (pre-test cleanup)', async () => {
    await it('should clear the cart before tests', async () => {
      const res = await request('DELETE', '/cart', null, token);
      assertOk(res.status, 'clear cart status');
    });
  });

  await describe('Cart - Add Item', async () => {
    await it('should add an item to the cart', async () => {
      if (!testOfferId) throw new Error('No test offer available');
      const res = await request('POST', '/cart/items', { offer_id: testOfferId, quantity: 1 }, token);
      assertOk(res.status, 'add to cart status');
      assert(!!res.data, 'response has data');
      const data = res.data!;
      assert(typeof data.item_count === 'number', 'has item_count');
      if (data.item) {
        addedItemId = (data.item as Record<string, unknown>).id as number;
      }
    });

    await it('should show the item in cart', async () => {
      const res = await request('GET', '/cart', null, token);
      assertOk(res.status, 'get cart status');
      assert(!!res.data, 'response has data');
      const data = res.data!;
      assert((data.item_count as number) >= 1, 'cart has at least 1 item');
      const items = (data.items || []) as Record<string, unknown>[];
      if (items.length > 0 && !addedItemId) {
        addedItemId = items[0].id as number;
      }
    });

    await it('should reject adding without auth', async () => {
      const res = await request('POST', '/cart/items', { offer_id: 1, quantity: 1 });
      assert(res.status === 401, `expected 401, got ${res.status}`);
    });
  });

  await describe('Cart - Update Quantity', async () => {
    await it('should update item quantity', async () => {
      if (!addedItemId) throw new Error('No cart item to update');
      const res = await request('PUT', `/cart/items/${addedItemId}`, { quantity: 2 }, token);
      assertOk(res.status, 'update quantity status');
    });
  });

  await describe('Cart - Validate', async () => {
    await it('should validate the cart', async () => {
      const res = await request('POST', '/cart/validate', null, token);
      assertOk(res.status, 'validate cart status');
      assert(!!res.data, 'response has data');
      assert(typeof res.data!.valid === 'boolean', 'has valid flag');
    });
  });

  await describe('Cart - Remove Item', async () => {
    await it('should remove item from cart', async () => {
      if (!addedItemId) throw new Error('No cart item to remove');
      const res = await request('DELETE', `/cart/items/${addedItemId}`, null, token);
      assertOk(res.status, 'remove item status');
    });
  });

  await describe('Cart - Clear', async () => {
    await it('should clear the entire cart', async () => {
      // Add item back first if needed
      if (testOfferId) {
        await request('POST', '/cart/items', { offer_id: testOfferId, quantity: 1 }, token);
      }
      const res = await request('DELETE', '/cart', null, token);
      assertOk(res.status, 'clear cart status');
      // Verify cart is empty
      const getRes = await request('GET', '/cart', null, token);
      assertOk(getRes.status, 'get cart after clear');
      const count = getRes.data?.item_count as number;
      assert(count === 0, `expected 0 items, got ${count}`);
    });
  });
}
