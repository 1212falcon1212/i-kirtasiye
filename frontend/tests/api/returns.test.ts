import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runReturnsTests(): Promise<void> {
  await describe('Returns API', async () => {
    const token = await loginAs('eczane4@test.com', 'password123');

    await it('GET /returns/reasons -- lists return reasons', async () => {
      const res = await request('GET', '/returns/reasons', null, token);
      assertOk(res.status, 'reasons');
      assert(Array.isArray((res.data as any)?.reasons), 'reasons should be array');
    });

    await it('GET /returns/my-requests -- lists buyer return requests', async () => {
      const res = await request('GET', '/returns/my-requests', null, token);
      assertOk(res.status, 'my-requests');
      assert(res.data?.success === true, 'success should be true');
      assert(Array.isArray((res.data as any)?.data), 'data should be array');
    });

    await it('GET /returns/seller-requests -- lists seller return requests', async () => {
      const res = await request('GET', '/returns/seller-requests', null, token);
      assertOk(res.status, 'seller-requests');
      assert(res.data?.success === true, 'success should be true');
    });

    // Get an order with returns for order-specific test
    await it('GET /returns/order/:id -- lists returns for specific order', async () => {
      const ordersRes = await request('GET', '/orders', null, token);
      const orders = (ordersRes.data as any)?.orders || [];
      if (orders.length === 0) {
        console.log('    (skipped - no orders)');
        return;
      }
      const res = await request('GET', `/returns/order/${orders[0].id}`, null, token);
      // May be 200 with empty array or 200 with data
      assertOk(res.status, 'order returns');
    });
  });
}
