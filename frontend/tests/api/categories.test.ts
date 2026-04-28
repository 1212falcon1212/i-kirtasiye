import { describe, it, assert, assertOk, request } from './helpers';

export async function runCategoriesTests(): Promise<void> {
  let firstCategorySlug = '';
  let firstCategoryId: number | null = null;

  await describe('Categories - List', async () => {
    await it('should return all categories', async () => {
      const res = await request('GET', '/categories');
      assertOk(res.status, 'categories list status');
      assert(!!res.data, 'response has data');
      const categories = (res.data!.categories || res.data!.data || res.data!) as Record<string, unknown>[];
      assert(Array.isArray(categories), 'categories is an array');
      if (categories.length > 0) {
        const cat = categories[0];
        assert(typeof cat.name === 'string', 'category has name');
        assert(typeof cat.slug === 'string', 'category has slug');
        firstCategorySlug = cat.slug as string;
        firstCategoryId = cat.id as number;
      }
    });
  });

  await describe('Categories - Slug Lookup', async () => {
    await it('should return category by slug', async () => {
      if (!firstCategorySlug) {
        throw new Error('No categories available to test slug lookup');
      }
      const res = await request('GET', `/categories/slug/${firstCategorySlug}`);
      assertOk(res.status, 'category by slug status');
      assert(!!res.data, 'response has data');
    });

    await it('should return 404 for non-existent slug', async () => {
      const res = await request('GET', '/categories/slug/nonexistent-category-xyz');
      assert(res.status === 404, `expected 404, got ${res.status}`);
    });
  });

  await describe('Categories - Show by ID', async () => {
    await it('should return category by ID', async () => {
      if (!firstCategoryId) {
        throw new Error('No categories available to test by ID');
      }
      const res = await request('GET', `/categories/${firstCategoryId}`);
      assertOk(res.status, 'category by id status');
      assert(!!res.data, 'response has data');
    });

    await it('should return 404 for non-existent ID', async () => {
      const res = await request('GET', '/categories/999999');
      assert(res.status === 404, `expected 404, got ${res.status}`);
    });
  });
}
