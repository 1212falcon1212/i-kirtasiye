<?php

namespace Tests\Feature\Api;

use App\Models\Banner;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CmsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ==========================================
    // LAYOUT ENDPOINT TESTS
    // ==========================================

    /**
     * Test layout endpoint returns expected structure.
     */
    public function test_layout_endpoint_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/cms/layout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'menus' => [
                        'header',
                        'footer',
                        'categories',
                        'mobile',
                    ],
                    'settings' => [
                        'site_name',
                        'logo_url',
                    ],
                    'footer_settings' => [
                        'description',
                        'phone',
                        'email',
                        'copyright',
                    ],
                ],
            ])
            ->assertJsonPath('status', 'success');
    }

    /**
     * Test layout endpoint caches result.
     */
    public function test_layout_endpoint_caches_result(): void
    {
        // First call populates cache
        $this->getJson('/api/cms/layout')->assertStatus(200);

        // Verify cache was set
        $this->assertTrue(Cache::has('cms.layout'));
    }

    // ==========================================
    // HOMEPAGE ENDPOINT TESTS
    // ==========================================

    /**
     * Test homepage endpoint returns expected structure.
     */
    public function test_homepage_endpoint_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/cms/homepage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'banners' => [
                    'hero',
                    'promo',
                    'middle',
                    'brand',
                    'grid',
                    'bottom',
                    'showcase',
                ],
                'sections',
                'categories',
                'brands',
                'best_sellers',
                'recommended',
                'category_sections',
                'seo_text',
            ]);
    }

    /**
     * Test homepage returns active banners only.
     */
    public function test_homepage_returns_banners(): void
    {
        Banner::factory()->count(2)->create([
            'location' => 'home_hero',
            'is_active' => true,
        ]);

        Cache::flush(); // Clear any cached data

        $response = $this->getJson('/api/cms/homepage');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('banners.hero'));
    }

    /**
     * Test homepage returns categories.
     */
    public function test_homepage_returns_categories(): void
    {
        Category::factory()->count(3)->create([
            'is_active' => true,
            'parent_id' => null,
        ]);

        Cache::flush();

        $response = $this->getJson('/api/cms/homepage');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('categories')));
    }

    // ==========================================
    // BANNERS ENDPOINT TESTS
    // ==========================================

    /**
     * Test banners endpoint returns banners for a specific location.
     */
    public function test_banners_endpoint_returns_banners_for_location(): void
    {
        Banner::factory()->count(2)->create([
            'location' => 'home_hero',
            'is_active' => true,
        ]);

        Banner::factory()->count(1)->create([
            'location' => 'home_promo',
            'is_active' => true,
        ]);

        Cache::flush();

        $response = $this->getJson('/api/cms/banners/home_hero');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test banners endpoint returns empty for unknown location.
     */
    public function test_banners_endpoint_returns_empty_for_unknown_location(): void
    {
        $response = $this->getJson('/api/cms/banners/nonexistent_location');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(0, 'data');
    }

    // ==========================================
    // PAGE ENDPOINT TESTS
    // ==========================================

    /**
     * Test page endpoint returns published page.
     */
    public function test_page_endpoint_returns_published_page(): void
    {
        \App\Models\Page::create([
            'title' => 'Hakkimizda',
            'slug' => 'hakkimizda',
            'content' => '<p>Test content</p>',
            'status' => 'published',
        ]);

        Cache::flush();

        $response = $this->getJson('/api/cms/pages/hakkimizda');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Hakkimizda')
            ->assertJsonPath('data.slug', 'hakkimizda');
    }

    /**
     * Test page endpoint returns 404 for nonexistent page.
     */
    public function test_page_endpoint_returns_404_for_nonexistent_page(): void
    {
        $response = $this->getJson('/api/cms/pages/nonexistent-slug');

        $response->assertStatus(404);
    }

    // ==========================================
    // FEATURED SECTIONS ENDPOINT TESTS
    // ==========================================

    /**
     * Test featured sections endpoint returns expected structure.
     */
    public function test_featured_sections_endpoint_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/cms/featured-sections');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'season_highlights',
                'week_products',
                'deal_of_day',
                'recently_sold',
            ]);
    }

    // ==========================================
    // CACHE CLEARING TESTS
    // ==========================================

    /**
     * Test clearAllCaches clears all CMS caches.
     */
    public function test_clear_all_caches_clears_cms_caches(): void
    {
        // Populate some caches
        Cache::put('cms.layout', 'test', 3600);
        Cache::put('cms.homepage.seo_text', 'test', 3600);

        $cleared = \App\Http\Controllers\Api\CmsController::clearAllCaches();

        $this->assertGreaterThan(0, $cleared);
        $this->assertFalse(Cache::has('cms.layout'));
        $this->assertFalse(Cache::has('cms.homepage.seo_text'));
    }
}
