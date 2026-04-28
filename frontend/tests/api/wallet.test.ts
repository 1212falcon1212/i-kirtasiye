import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runWalletTests(): Promise<void> {
  await describe('Wallet & Settlements API', async () => {
    const token = await loginAs('eczane4@test.com', 'password123');

    await it('GET /wallet -- returns wallet balance', async () => {
      const res = await request('GET', '/wallet', null, token);
      assertOk(res.status, 'wallet');
      const data = res.data as any;
      assert(data?.balance !== undefined || data?.data?.balance !== undefined, 'should have balance');
    });

    await it('GET /wallet/transactions -- lists transactions', async () => {
      const res = await request('GET', '/wallet/transactions', null, token);
      assertOk(res.status, 'transactions');
    });

    await it('GET /wallet/settlements/upcoming -- lists upcoming settlements', async () => {
      const res = await request('GET', '/wallet/settlements/upcoming', null, token);
      assertOk(res.status, 'upcoming settlements');
    });

    await it('GET /wallet/settlements/past -- lists past settlements', async () => {
      const res = await request('GET', '/wallet/settlements/past', null, token);
      assertOk(res.status, 'past settlements');
    });

    await it('GET /wallet/settlements/summary -- returns settlement summary', async () => {
      const res = await request('GET', '/wallet/settlements/summary', null, token);
      assertOk(res.status, 'settlement summary');
    });
  });
}
