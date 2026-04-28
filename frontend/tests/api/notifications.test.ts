import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runNotificationsTests(): Promise<void> {
  await describe('Notifications API', async () => {
    const token = await loginAs('eczane4@test.com', 'password123');

    await it('GET /notifications -- lists notifications', async () => {
      const res = await request('GET', '/notifications', null, token);
      assertOk(res.status, 'notifications list');
    });

    await it('GET /notifications/unread-count -- returns unread count', async () => {
      const res = await request('GET', '/notifications/unread-count', null, token);
      assertOk(res.status, 'unread count');
      assert(typeof (res.data as any)?.count === 'number' || typeof (res.data as any)?.unread_count === 'number', 'should have count');
    });

    await it('POST /notifications/read-all -- marks all as read', async () => {
      const res = await request('POST', '/notifications/read-all', {}, token);
      assertOk(res.status, 'read all');
    });
  });
}
