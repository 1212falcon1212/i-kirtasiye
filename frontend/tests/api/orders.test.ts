import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runOrdersTests(): Promise<void> {
  let token = '';

  token = await loginAs('eczane4@test.com', 'password123');

  await describe('Orders - List', async () => {
    await it('should return order list', async () => {
      const res = await request('GET', '/orders', null, token);
      assertOk(res.status, 'orders list status');
      assert(!!res.data, 'response has data');
      const orders = (res.data!.orders || res.data!.data || []) as unknown[];
      assert(Array.isArray(orders), 'orders is an array');
    });

    await it('should support pagination', async () => {
      const res = await request('GET', '/orders?page=1&per_page=5', null, token);
      assertOk(res.status, 'paginated orders status');
      assert(!!res.data, 'response has data');
    });

    await it('should reject without auth', async () => {
      const res = await request('GET', '/orders');
      assert(res.status === 401, `expected 401, got ${res.status}`);
    });
  });

  await describe('Orders - Detail', async () => {
    await it('should return order detail if orders exist', async () => {
      const listRes = await request('GET', '/orders?per_page=1', null, token);
      const orders = (listRes.data?.orders || listRes.data?.data || []) as Record<string, unknown>[];
      if (orders.length === 0) {
        // No orders to test - skip gracefully
        assert(true, 'no orders available, skipping detail test');
        return;
      }
      const orderId = orders[0].id as number;
      const res = await request('GET', `/orders/${orderId}`, null, token);
      assertOk(res.status, 'order detail status');
      assert(!!res.data, 'response has data');
      const order = (res.data!.order || res.data!) as Record<string, unknown>;
      assert(typeof order.order_number === 'string' || typeof order.id === 'number', 'order has identifier');
    });

    await it('should return 404 or 403 for non-existent order', async () => {
      const res = await request('GET', '/orders/999999', null, token);
      assert(res.status === 404 || res.status === 403, `expected 404/403, got ${res.status}`);
    });
  });

  await describe('Orders - Create (validation)', async () => {
    await it('should reject order creation with empty cart', async () => {
      // Clear cart first
      await request('DELETE', '/cart', null, token);
      const res = await request(
        'POST',
        '/orders',
        {
          shipping_address: {
            name: 'Test',
            phone: '05551234567',
            address: 'Test Adres',
            city: 'Istanbul',
            district: 'Kadikoy',
          },
          payment_method: 'havale',
        },
        token
      );
      // Should fail because cart is empty
      assert(
        res.status === 422 || res.status === 400 || res.status === 409,
        `expected 4xx for empty cart order, got ${res.status}`
      );
    });

    await it('should reject order without shipping address', async () => {
      const res = await request('POST', '/orders', {}, token);
      assert(res.status === 422, `expected 422, got ${res.status}`);
    });
  });
}
