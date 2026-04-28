import { describe, it, assert, assertOk, request } from './helpers';

export async function runCmsTests(): Promise<void> {
  await describe('CMS - Homepage', async () => {
    await it('should return homepage data', async () => {
      const res = await request('GET', '/cms/homepage');
      assertOk(res.status, 'homepage status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('CMS - Layout', async () => {
    await it('should return layout data', async () => {
      const res = await request('GET', '/cms/layout');
      assertOk(res.status, 'layout status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('CMS - Banners', async () => {
    await it('should return banners for homepage location', async () => {
      const res = await request('GET', '/cms/banners/homepage');
      assertOk(res.status, 'banners status');
      assert(!!res.data, 'response has data');
    });

    await it('should return banners for market location', async () => {
      const res = await request('GET', '/cms/banners/market');
      // May return 200 with empty data or 404 depending on config
      assert(res.status === 200 || res.status === 404, `banners market: got ${res.status}`);
    });
  });

  await describe('CMS - Featured Sections', async () => {
    await it('should return featured sections', async () => {
      const res = await request('GET', '/cms/featured-sections');
      assertOk(res.status, 'featured sections status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('CMS - Landing Content', async () => {
    await it('should return landing page content', async () => {
      const res = await request('GET', '/landing-content');
      assertOk(res.status, 'landing content status');
      assert(!!res.data, 'response has data');
      // Landing content should have expected sections
      const data = res.data!;
      assert('hero' in data || 'how_it_works' in data || 'faq' in data, 'has landing sections');
    });
  });

  await describe('CMS - Pages', async () => {
    await it('should handle non-existent page slug', async () => {
      const res = await request('GET', '/cms/pages/nonexistent-slug-xyz');
      assert(res.status === 404 || res.status === 200, `page not found: got ${res.status}`);
    });
  });
}
