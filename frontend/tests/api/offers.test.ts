import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runOffersTests(): Promise<void> {
  let token = '';
  let createdOfferId: number | null = null;
  let testProductId: number | null = null;

  token = await loginAs('eczane4@test.com', 'password123');

  // Find a product to create an offer for
  const productsRes = await request('GET', '/products?per_page=5');
  const products = (productsRes.data?.products || productsRes.data?.data || []) as Record<string, unknown>[];
  if (products.length > 0) {
    testProductId = products[0].id as number;
  }

  await describe('Offers - My Offers', async () => {
    await it('should list my offers', async () => {
      const res = await request('GET', '/my-offers', null, token);
      assertOk(res.status, 'my offers status');
      assert(!!res.data, 'response has data');
    });

    await it('should support pagination on my offers', async () => {
      const res = await request('GET', '/my-offers?page=1&per_page=5', null, token);
      assertOk(res.status, 'paginated offers status');
    });

    await it('should reject without auth', async () => {
      const res = await request('GET', '/my-offers');
      assert(res.status === 401, `expected 401, got ${res.status}`);
    });
  });

  await describe('Offers - Create', async () => {
    await it('should create a new offer', async () => {
      if (!testProductId) throw new Error('No product available for offer creation');

      // Set expiry_date 6 months in the future
      const expiryDate = new Date();
      expiryDate.setMonth(expiryDate.getMonth() + 6);
      const expiryStr = expiryDate.toISOString().split('T')[0];

      const res = await request(
        'POST',
        '/offers',
        {
          product_id: testProductId,
          price: 99.99,
          stock: 10,
          expiry_date: expiryStr,
          batch_number: 'TEST-BATCH-001',
          notes: 'API test offer - to be deleted',
        },
        token
      );
      assertOk(res.status, 'create offer status');
      assert(!!res.data, 'response has data');
      const offer = (res.data!.offer || res.data!) as Record<string, unknown>;
      assert(typeof offer.id === 'number', 'offer has id');
      createdOfferId = offer.id as number;
    });

    await it('should reject offer without required fields', async () => {
      const res = await request('POST', '/offers', { price: 10 }, token);
      assert(res.status === 422, `expected 422, got ${res.status}`);
    });
  });

  await describe('Offers - Toggle Status', async () => {
    await it('should toggle offer status', async () => {
      if (!createdOfferId) throw new Error('No offer to toggle');
      const res = await request('POST', `/offers/${createdOfferId}/toggle-status`, null, token);
      assertOk(res.status, 'toggle status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('Offers - Cleanup', async () => {
    await it('should delete the test offer', async () => {
      if (!createdOfferId) throw new Error('No offer to delete');
      const res = await request('DELETE', `/offers/${createdOfferId}`, null, token);
      assertOk(res.status, 'delete offer status');
    });
  });
}
