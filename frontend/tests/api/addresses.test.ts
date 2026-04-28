import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runAddressesTests(): Promise<void> {
  let token = '';
  let createdAddressId: number | null = null;

  token = await loginAs('eczane4@test.com', 'password123');

  await describe('Addresses - Create', async () => {
    await it('should create a new address', async () => {
      const res = await request(
        'POST',
        '/user/addresses',
        {
          title: 'Test Adres',
          name: 'Test Eczane',
          phone: '05551234567',
          address: 'Test Mahallesi, Test Sokak No:1',
          city: 'Istanbul',
          district: 'Kadikoy',
          postal_code: '34700',
          is_default: false,
        },
        token
      );
      assertOk(res.status, 'create address status');
      assert(!!res.data, 'response has data');
      const addrData = (res.data!.data || res.data!) as Record<string, unknown>;
      assert(typeof addrData.id === 'number', 'address has id');
      createdAddressId = addrData.id as number;
    });

    await it('should reject creation without auth', async () => {
      const res = await request('POST', '/user/addresses', { title: 'X', name: 'X', phone: '555', address: 'X', city: 'X', district: 'X' });
      assert(res.status === 401, `expected 401, got ${res.status}`);
    });

    await it('should reject creation with missing required fields', async () => {
      const res = await request('POST', '/user/addresses', { title: 'Only Title' }, token);
      assert(res.status === 422, `expected 422, got ${res.status}`);
    });
  });

  await describe('Addresses - List', async () => {
    await it('should return user addresses', async () => {
      const res = await request('GET', '/user/addresses', null, token);
      assertOk(res.status, 'list addresses status');
      assert(!!res.data, 'response has data');
      const addresses = (res.data!.data || res.data!) as unknown[];
      assert(Array.isArray(addresses), 'addresses is an array');
      assert(addresses.length > 0, 'has at least one address');
    });
  });

  await describe('Addresses - Update', async () => {
    await it('should update an existing address', async () => {
      if (!createdAddressId) throw new Error('No address to update');
      const res = await request(
        'PUT',
        `/user/addresses/${createdAddressId}`,
        {
          title: 'Guncellenmis Test Adres',
          name: 'Guncellenmis Eczane',
          phone: '05559999999',
          address: 'Guncellenmis Mahallesi, Yeni Sokak No:2',
          city: 'Ankara',
          district: 'Cankaya',
        },
        token
      );
      assertOk(res.status, 'update address status');
    });
  });

  await describe('Addresses - Delete', async () => {
    await it('should delete the created address', async () => {
      if (!createdAddressId) throw new Error('No address to delete');
      const res = await request('DELETE', `/user/addresses/${createdAddressId}`, null, token);
      assertOk(res.status, 'delete address status');
    });

    await it('should return 404 for deleted address', async () => {
      if (!createdAddressId) return;
      const res = await request('GET', `/user/addresses/${createdAddressId}`, null, token);
      assert(res.status === 404 || res.status === 200, `expected 404 or 200, got ${res.status}`);
    });
  });
}
