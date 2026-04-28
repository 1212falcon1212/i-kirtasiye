import { describe, it, assert, assertOk, request } from './helpers';

export async function runBrandsTests(): Promise<void> {
  let firstBrandSlug = '';

  await describe('Brands - List', async () => {
    await it('should return all brands', async () => {
      const res = await request('GET', '/brands');
      assertOk(res.status, 'brands list status');
      assert(!!res.data, 'response has data');
      const brands = (res.data!.brands || res.data!.data || res.data!) as Record<string, unknown>[];
      if (Array.isArray(brands) && brands.length > 0) {
        assert(typeof brands[0].name === 'string', 'brand has name');
        if (brands[0].slug) {
          firstBrandSlug = brands[0].slug as string;
        }
      }
    });
  });

  await describe('Brands - Featured', async () => {
    await it('should return featured brands', async () => {
      const res = await request('GET', '/brands/featured');
      assertOk(res.status, 'featured brands status');
      assert(!!res.data, 'response has data');
    });
  });

  await describe('Brands - Detail', async () => {
    await it('should return brand by slug', async () => {
      if (!firstBrandSlug) {
        // Try fetching brands to get a slug
        const listRes = await request('GET', '/brands');
        const brands = (listRes.data?.brands || listRes.data?.data || []) as Record<string, unknown>[];
        if (Array.isArray(brands) && brands.length > 0 && brands[0].slug) {
          firstBrandSlug = brands[0].slug as string;
        }
      }
      if (!firstBrandSlug) {
        throw new Error('No brands available to test detail');
      }
      const res = await request('GET', `/brands/${firstBrandSlug}`);
      assertOk(res.status, 'brand detail status');
      assert(!!res.data, 'response has data');
    });

    await it('should return 404 for non-existent brand slug', async () => {
      const res = await request('GET', '/brands/nonexistent-brand-slug-xyz');
      assert(res.status === 404, `expected 404, got ${res.status}`);
    });
  });
}
