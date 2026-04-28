<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test class for Offer model functionality.
 * Tests offer creation, stock management, status changes, and expiry checks.
 */
class OfferServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a new offer with valid data.
     */
    public function test_create_offer_with_valid_data(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create([
                'batch_number' => 'BATCH001',
                'notes' => 'Test notes',
            ]);

        $this->assertInstanceOf(Offer::class, $offer);
        $this->assertEquals($product->id, $offer->product_id);
        $this->assertEquals($seller->id, $offer->seller_id);
        $this->assertEquals(100.00, $offer->price);
        $this->assertEquals(50, $offer->stock);
        $this->assertEquals('active', $offer->status);
        $this->assertEquals('BATCH001', $offer->batch_number);
    }

    /**
     * Test offer belongs to product.
     */
    public function test_offer_belongs_to_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create([
            'name' => 'Test Product',
        ]);
        $offer = Offer::factory()
            ->forProduct($product)
            ->available()
            ->create();

        $this->assertInstanceOf(Product::class, $offer->product);
        $this->assertEquals('Test Product', $offer->product->name);
    }

    /**
     * Test offer belongs to seller.
     */
    public function test_offer_belongs_to_seller(): void
    {
        $seller = User::factory()->seller()->create([
            'pharmacy_name' => 'Test Eczanesi',
        ]);
        $offer = Offer::factory()
            ->forSeller($seller)
            ->available()
            ->create();

        $this->assertInstanceOf(User::class, $offer->seller);
        $this->assertEquals('Test Eczanesi', $offer->seller->pharmacy_name);
    }

    /**
     * Test decreasing stock successfully.
     */
    public function test_decrease_stock_successfully(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->available()
            ->create();

        $result = $offer->decreaseStock(10);

        $this->assertTrue($result);
        $this->assertEquals(40, $offer->fresh()->stock);
        $this->assertEquals('active', $offer->fresh()->status);
    }

    /**
     * Test decreasing stock to zero changes status to sold_out.
     */
    public function test_decrease_stock_to_zero_changes_status_to_sold_out(): void
    {
        $offer = Offer::factory()
            ->withStock(10)
            ->available()
            ->create();

        $result = $offer->decreaseStock(10);

        $this->assertTrue($result);
        $this->assertEquals(0, $offer->fresh()->stock);
        $this->assertEquals('sold_out', $offer->fresh()->status);
    }

    /**
     * Test decreasing stock fails when amount exceeds stock.
     */
    public function test_decrease_stock_fails_when_amount_exceeds_stock(): void
    {
        $offer = Offer::factory()
            ->withStock(5)
            ->available()
            ->create();

        $result = $offer->decreaseStock(10);

        $this->assertFalse($result);
        $this->assertEquals(5, $offer->fresh()->stock); // Stock unchanged
    }

    /**
     * Test offer status can be changed to inactive.
     */
    public function test_change_status_to_inactive(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->available()
            ->create();

        $offer->update(['status' => 'inactive']);

        $this->assertEquals('inactive', $offer->fresh()->status);
    }

    /**
     * Test offer status can be changed to active.
     */
    public function test_change_status_to_active(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->inactive()
            ->create();

        $offer->update(['status' => 'active']);

        $this->assertEquals('active', $offer->fresh()->status);
    }

    /**
     * Test offer status can be changed to sold_out.
     */
    public function test_change_status_to_sold_out(): void
    {
        $offer = Offer::factory()
            ->withStock(0)
            ->available()
            ->create();

        $offer->update(['status' => 'sold_out']);

        $this->assertEquals('sold_out', $offer->fresh()->status);
    }

    /**
     * Test is_expired returns true for expired offer.
     */
    public function test_is_expired_returns_true_for_expired_offer(): void
    {
        $offer = Offer::factory()
            ->expired()
            ->create();

        $this->assertTrue($offer->isExpired());
    }

    /**
     * Test is_expired returns false for non-expired offer.
     */
    public function test_is_expired_returns_false_for_non_expired_offer(): void
    {
        $offer = Offer::factory()
            ->available()
            ->create();

        $this->assertFalse($offer->isExpired());
    }

    /**
     * Test has_stock returns true when stock is available.
     */
    public function test_has_stock_returns_true_when_stock_available(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->available()
            ->create();

        $this->assertTrue($offer->hasStock());
    }

    /**
     * Test has_stock returns false when stock is zero.
     */
    public function test_has_stock_returns_false_when_stock_is_zero(): void
    {
        $offer = Offer::factory()
            ->soldOut()
            ->create();

        $this->assertFalse($offer->hasStock());
    }

    /**
     * Test is_available returns true for active, in-stock, non-expired offer.
     */
    public function test_is_available_returns_true_for_valid_offer(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->available()
            ->create();

        $this->assertTrue($offer->isAvailable());
    }

    /**
     * Test is_available returns false for inactive offer.
     */
    public function test_is_available_returns_false_for_inactive_offer(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->inactive()
            ->create();

        $this->assertFalse($offer->isAvailable());
    }

    /**
     * Test is_available returns false for sold out offer.
     */
    public function test_is_available_returns_false_for_sold_out_offer(): void
    {
        $offer = Offer::factory()
            ->soldOut()
            ->create();

        $this->assertFalse($offer->isAvailable());
    }

    /**
     * Test is_available returns false for expired offer.
     */
    public function test_is_available_returns_false_for_expired_offer(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->expired()
            ->create(['status' => 'active']);

        $this->assertFalse($offer->isAvailable());
    }

    /**
     * Test active scope returns only active offers.
     */
    public function test_active_scope_returns_only_active_offers(): void
    {
        Offer::factory()->available()->count(3)->create();
        Offer::factory()->inactive()->count(2)->create();
        Offer::factory()->soldOut()->count(1)->create();

        $activeOffers = Offer::active()->get();

        $this->assertCount(3, $activeOffers);
        $this->assertTrue($activeOffers->every(fn($o) => $o->status === 'active'));
    }

    /**
     * Test in_stock scope returns only offers with stock.
     */
    public function test_in_stock_scope_returns_only_offers_with_stock(): void
    {
        Offer::factory()->withStock(50)->available()->count(3)->create();
        Offer::factory()->soldOut()->count(2)->create();

        $inStockOffers = Offer::inStock()->get();

        $this->assertCount(3, $inStockOffers);
        $this->assertTrue($inStockOffers->every(fn($o) => $o->stock > 0));
    }

    /**
     * Test not_expired scope returns only non-expired offers.
     */
    public function test_not_expired_scope_returns_only_non_expired_offers(): void
    {
        Offer::factory()->available()->count(3)->create();
        Offer::factory()->expired()->count(2)->create(['status' => 'active']);

        $notExpiredOffers = Offer::notExpired()->get();

        $this->assertCount(3, $notExpiredOffers);
        $this->assertTrue($notExpiredOffers->every(fn($o) => !$o->isExpired()));
    }

    /**
     * Test updating offer price.
     */
    public function test_update_offer_price(): void
    {
        $offer = Offer::factory()
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $offer->update(['price' => 150.00]);

        $this->assertEquals(150.00, $offer->fresh()->price);
    }

    /**
     * Test updating offer stock.
     */
    public function test_update_offer_stock(): void
    {
        $offer = Offer::factory()
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $offer->update(['stock' => 100]);

        $this->assertEquals(100, $offer->fresh()->stock);
    }

    /**
     * Test creating offer with factory states.
     */
    public function test_create_offer_with_factory_states(): void
    {
        // Available offer
        $availableOffer = Offer::factory()->available()->create();
        $this->assertEquals('active', $availableOffer->status);
        $this->assertTrue($availableOffer->stock > 0);

        // Inactive offer
        $inactiveOffer = Offer::factory()->inactive()->create();
        $this->assertEquals('inactive', $inactiveOffer->status);

        // Sold out offer
        $soldOutOffer = Offer::factory()->soldOut()->create();
        $this->assertEquals('sold_out', $soldOutOffer->status);
        $this->assertEquals(0, $soldOutOffer->stock);

        // Expired offer
        $expiredOffer = Offer::factory()->expired()->create();
        $this->assertTrue($expiredOffer->isExpired());
    }

    /**
     * Test offer soft deletes.
     */
    public function test_offer_can_be_soft_deleted(): void
    {
        $offer = Offer::factory()->available()->create();
        $offerId = $offer->id;

        $offer->delete();

        $this->assertSoftDeleted('offers', ['id' => $offerId]);
        $this->assertNull(Offer::find($offerId));
        $this->assertNotNull(Offer::withTrashed()->find($offerId));
    }

    /**
     * Test offer can be restored after soft delete.
     */
    public function test_offer_can_be_restored(): void
    {
        $offer = Offer::factory()->available()->create();
        $offerId = $offer->id;

        $offer->delete();
        $this->assertSoftDeleted('offers', ['id' => $offerId]);

        Offer::withTrashed()->find($offerId)->restore();
        $this->assertNotNull(Offer::find($offerId));
    }

    /**
     * Test offer price is cast as decimal.
     */
    public function test_offer_price_is_cast_as_decimal(): void
    {
        $offer = Offer::factory()
            ->withPrice(123.456)
            ->available()
            ->create();

        // Decimal:2 should round to 2 decimal places
        $this->assertEquals('123.46', $offer->price);
    }

    /**
     * Test offer expiry_date is cast as date.
     */
    public function test_offer_expiry_date_is_cast_as_date(): void
    {
        $offer = Offer::factory()->available()->create([
            'expiry_date' => '2025-12-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $offer->expiry_date);
        $this->assertEquals('2025-12-31', $offer->expiry_date->format('Y-m-d'));
    }

    /**
     * Test offer stock is cast as integer.
     */
    public function test_offer_stock_is_cast_as_integer(): void
    {
        $offer = Offer::factory()
            ->withStock(50)
            ->available()
            ->create();

        $this->assertIsInt($offer->stock);
    }
}
