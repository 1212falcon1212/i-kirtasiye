import { describe, it, assert, assertOk, request, loginAs } from './helpers';

export async function runSupportTests(): Promise<void> {
  await describe('Support Tickets API', async () => {
    const token = await loginAs('eczane4@test.com', 'password123');
    let ticketId: number | null = null;

    await it('POST /support-tickets -- creates a ticket', async () => {
      const res = await request('POST', '/support-tickets', {
        subject: 'Test Destek Talebi',
        message: 'Bu bir test destek talebidir. API testleri icin olusturuldu.',
        priority: 'medium',
      }, token);
      assertOk(res.status, 'create ticket');
      const data = res.data as any;
      assert(data?.data?.id || data?.ticket?.id, 'should return ticket with id');
      ticketId = data?.data?.id || data?.ticket?.id;
    });

    await it('GET /support-tickets -- lists tickets', async () => {
      const res = await request('GET', '/support-tickets', null, token);
      assertOk(res.status, 'list tickets');
      const data = res.data as any;
      assert(Array.isArray(data?.data) || Array.isArray(data?.tickets), 'should return array');
    });

    await it('GET /support-tickets/:id -- shows ticket detail', async () => {
      if (!ticketId) { console.log('    (skipped - no ticket)'); return; }
      const res = await request('GET', `/support-tickets/${ticketId}`, null, token);
      assertOk(res.status, 'ticket detail');
    });

    await it('POST /support-tickets/:id/messages -- sends message', async () => {
      if (!ticketId) { console.log('    (skipped - no ticket)'); return; }
      const res = await request('POST', `/support-tickets/${ticketId}/messages`, {
        message: 'Test mesaji - API testinden gonderildi.',
      }, token);
      assertOk(res.status, 'send message');
    });

    // Cleanup - close ticket
    await it('PUT /support-tickets/:id/close -- closes ticket', async () => {
      if (!ticketId) { console.log('    (skipped - no ticket)'); return; }
      const res = await request('PUT', `/support-tickets/${ticketId}/close`, {}, token);
      assertOk(res.status, 'close ticket');
    });
  });
}
