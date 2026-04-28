<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected string $sellerToken;

    protected string $buyerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->seller()->create();
        $this->buyer = User::factory()->create();
        $this->sellerToken = $this->seller->createToken('seller-token')->plainTextToken;
        $this->buyerToken = $this->buyer->createToken('buyer-token')->plainTextToken;
    }

    protected function sellerHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->sellerToken];
    }

    protected function buyerHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->buyerToken];
    }

    // ==========================================
    // INDEX TESTS
    // ==========================================

    /**
     * Test seller can list their invoices.
     */
    public function test_seller_can_list_invoices(): void
    {
        // Mock the InvoiceService since index calls getSellerInvoices
        $this->mock(InvoiceService::class, function ($mock) {
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                15,
                1
            );
            $mock->shouldReceive('getSellerInvoices')->once()->andReturn($paginator);
        });

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ]);
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    /**
     * Test seller can view their own invoice.
     */
    public function test_seller_can_view_own_invoice(): void
    {
        $order = Order::factory()->forUser($this->buyer)->create();
        $invoice = Invoice::factory()->forSeller($this->seller)->forBuyer($this->buyer)->forOrder($order)->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $invoice->id);
    }

    /**
     * Test buyer can view invoice addressed to them.
     */
    public function test_buyer_can_view_invoice_addressed_to_them(): void
    {
        $order = Order::factory()->forUser($this->buyer)->create();
        $invoice = Invoice::factory()->forSeller($this->seller)->forBuyer($this->buyer)->forOrder($order)->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test unauthorized user cannot view invoice.
     */
    public function test_unauthorized_user_cannot_view_invoice(): void
    {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('other-token')->plainTextToken;

        $order = Order::factory()->forUser($this->buyer)->create();
        $invoice = Invoice::factory()->forSeller($this->seller)->forBuyer($this->buyer)->forOrder($order)->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$otherToken])
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(403);
    }

    /**
     * Test super admin can view any invoice.
     */
    public function test_super_admin_can_view_any_invoice(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $adminToken = $admin->createToken('admin-token')->plainTextToken;

        $order = Order::factory()->forUser($this->buyer)->create();
        $invoice = Invoice::factory()->forSeller($this->seller)->forBuyer($this->buyer)->forOrder($order)->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$adminToken])
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200);
    }

    // ==========================================
    // DOWNLOAD PDF TESTS
    // ==========================================

    /**
     * Test buyer can download order invoice PDF.
     */
    public function test_buyer_can_download_order_invoice_pdf(): void
    {
        $order = Order::factory()->forUser($this->buyer)->paid()->create();
        OrderItem::factory()->forOrder($order)->forSeller($this->seller)->create();

        // Mock PdfService - use Mockery to create a proper mock of the PDF class
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('download')->once()->andReturn(
            response('pdf-content', 200, ['Content-Type' => 'application/pdf'])
        );
        $mockPdf = $this->mock(PdfService::class);
        $mockPdf->shouldReceive('generateOrderInvoice')->once()->andReturn($pdfMock);

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson("/api/invoices/orders/{$order->id}/pdf");

        $response->assertStatus(200);
    }

    /**
     * Test unauthorized user cannot download PDF.
     */
    public function test_unauthorized_user_cannot_download_pdf(): void
    {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('other-token')->plainTextToken;

        $order = Order::factory()->forUser($this->buyer)->paid()->create();
        OrderItem::factory()->forOrder($order)->forSeller($this->seller)->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$otherToken])
            ->getJson("/api/invoices/orders/{$order->id}/pdf");

        $response->assertStatus(403);
    }

    /**
     * Test seller can download PDF for their order items.
     */
    public function test_seller_can_download_pdf_for_their_items(): void
    {
        $order = Order::factory()->forUser($this->buyer)->paid()->create();
        OrderItem::factory()->forOrder($order)->forSeller($this->seller)->create();

        // Mock PdfService - use Mockery to create a proper mock of the PDF class
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('download')->once()->andReturn(
            response('pdf-content', 200, ['Content-Type' => 'application/pdf'])
        );
        $mockPdf = $this->mock(PdfService::class);
        $mockPdf->shouldReceive('generateOrderInvoice')->once()->andReturn($pdfMock);

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson("/api/invoices/orders/{$order->id}/pdf");

        $response->assertStatus(200);
    }

    // ==========================================
    // INVOICE FILE DOWNLOAD TESTS
    // ==========================================

    /**
     * Test download returns 404 when invoice has no PDF.
     */
    public function test_download_returns_404_when_no_pdf(): void
    {
        $order = Order::factory()->forUser($this->buyer)->create();
        $invoice = Invoice::factory()->forSeller($this->seller)->forBuyer($this->buyer)->forOrder($order)->create([
            'pdf_path' => null,
        ]);

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson("/api/invoices/{$invoice->id}/download");

        $response->assertStatus(404);
    }

    // ==========================================
    // COMMISSION SUMMARY TESTS
    // ==========================================

    /**
     * Test seller can view commission summary.
     */
    public function test_seller_can_view_commission_summary(): void
    {
        $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldReceive('getSellerCommissionSummary')->once()->andReturn([
                'total_sales' => 10000.00,
                'total_commission' => 1000.00,
                'total_payout' => 9000.00,
                'order_count' => 50,
                'item_count' => 100,
            ]);
        });

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/invoices/commission-summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_sales',
                    'total_commission',
                    'total_payout',
                    'order_count',
                    'item_count',
                    'average_commission_rate',
                ],
            ]);
    }

    // ==========================================
    // UNAUTHENTICATED ACCESS
    // ==========================================

    /**
     * Test unauthenticated user cannot access invoice endpoints.
     */
    public function test_unauthenticated_user_cannot_access_invoice_endpoints(): void
    {
        $this->getJson('/api/invoices')->assertStatus(401);
        $this->getJson('/api/invoices/commission-summary')->assertStatus(401);
    }
}
