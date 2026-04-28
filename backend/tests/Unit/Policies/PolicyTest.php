<?php

namespace Tests\Unit\Policies;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\ReturnRequestPolicy;
use App\Policies\SupportTicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // INVOICE POLICY TESTS
    // ==========================================

    /**
     * Test seller can view their own invoice.
     */
    public function test_invoice_policy_seller_can_view_own_invoice(): void
    {
        $seller = User::factory()->seller()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forSeller($seller)->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertTrue($policy->view($seller, $invoice));
    }

    /**
     * Test buyer can view invoice addressed to them.
     */
    public function test_invoice_policy_buyer_can_view_their_invoice(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forBuyer($buyer)->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertTrue($policy->view($buyer, $invoice));
    }

    /**
     * Test unauthorized user cannot view invoice.
     */
    public function test_invoice_policy_unauthorized_user_cannot_view(): void
    {
        $unauthorizedUser = User::factory()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertFalse($policy->view($unauthorizedUser, $invoice));
    }

    /**
     * Test super admin can view any invoice.
     */
    public function test_invoice_policy_super_admin_can_view_any(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertTrue($policy->view($admin, $invoice));
    }

    /**
     * Test seller can download their own invoice.
     */
    public function test_invoice_policy_seller_can_download_own(): void
    {
        $seller = User::factory()->seller()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forSeller($seller)->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertTrue($policy->download($seller, $invoice));
    }

    /**
     * Test unauthorized user cannot download invoice.
     */
    public function test_invoice_policy_unauthorized_user_cannot_download(): void
    {
        $unauthorizedUser = User::factory()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertFalse($policy->download($unauthorizedUser, $invoice));
    }

    /**
     * Test super admin can sync any invoice to ERP.
     */
    public function test_invoice_policy_super_admin_can_sync_to_erp(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertTrue($policy->syncToErp($admin, $invoice));
    }

    /**
     * Test only seller can sync their own invoice to ERP.
     */
    public function test_invoice_policy_seller_can_sync_own_to_erp(): void
    {
        $seller = User::factory()->seller()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forSeller($seller)->forOrder($order)->create();

        $policy = new InvoicePolicy;

        $this->assertTrue($policy->syncToErp($seller, $invoice));
        $this->assertFalse($policy->syncToErp($otherUser, $invoice));
    }

    // ==========================================
    // RETURN REQUEST POLICY TESTS
    // ==========================================

    /**
     * Test buyer can view their own return request.
     */
    public function test_return_request_policy_buyer_can_view_own(): void
    {
        $buyer = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->forBuyer($buyer)->create();

        $policy = new ReturnRequestPolicy;

        $this->assertTrue($policy->view($buyer, $returnRequest));
    }

    /**
     * Test seller can view return request sent to them.
     */
    public function test_return_request_policy_seller_can_view(): void
    {
        $seller = User::factory()->seller()->create();
        $returnRequest = ReturnRequest::factory()->forSeller($seller)->create();

        $policy = new ReturnRequestPolicy;

        $this->assertTrue($policy->view($seller, $returnRequest));
    }

    /**
     * Test unauthorized user cannot view return request.
     */
    public function test_return_request_policy_unauthorized_user_cannot_view(): void
    {
        $unauthorizedUser = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->create();

        $policy = new ReturnRequestPolicy;

        $this->assertFalse($policy->view($unauthorizedUser, $returnRequest));
    }

    /**
     * Test super admin can view any return request.
     */
    public function test_return_request_policy_super_admin_can_view_any(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $returnRequest = ReturnRequest::factory()->create();

        $policy = new ReturnRequestPolicy;

        $this->assertTrue($policy->view($admin, $returnRequest));
    }

    /**
     * Test only seller can approve return request.
     */
    public function test_return_request_policy_only_seller_can_approve(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->forSeller($seller)->forBuyer($buyer)->create();

        $policy = new ReturnRequestPolicy;

        $this->assertTrue($policy->approve($seller, $returnRequest));
        $this->assertFalse($policy->approve($buyer, $returnRequest));
    }

    /**
     * Test super admin can approve any return request.
     */
    public function test_return_request_policy_super_admin_can_approve(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $returnRequest = ReturnRequest::factory()->create();

        $policy = new ReturnRequestPolicy;

        $this->assertTrue($policy->approve($admin, $returnRequest));
    }

    /**
     * Test only seller can reject return request.
     */
    public function test_return_request_policy_only_seller_can_reject(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->forSeller($seller)->forBuyer($buyer)->create();

        $policy = new ReturnRequestPolicy;

        $this->assertTrue($policy->reject($seller, $returnRequest));
        $this->assertFalse($policy->reject($buyer, $returnRequest));
    }

    // ==========================================
    // SUPPORT TICKET POLICY TESTS
    // ==========================================

    /**
     * Test user can view their own ticket.
     */
    public function test_support_ticket_policy_user_can_view_own(): void
    {
        $user = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($user)->create();

        $policy = new SupportTicketPolicy;

        $this->assertTrue($policy->view($user, $ticket));
    }

    /**
     * Test user cannot view another user's ticket.
     */
    public function test_support_ticket_policy_user_cannot_view_others(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $policy = new SupportTicketPolicy;

        $this->assertFalse($policy->view($user, $ticket));
    }

    /**
     * Test super admin can view any ticket.
     */
    public function test_support_ticket_policy_super_admin_can_view_any(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($user)->create();

        $policy = new SupportTicketPolicy;

        $this->assertTrue($policy->view($admin, $ticket));
    }

    /**
     * Test user can add message to own ticket.
     */
    public function test_support_ticket_policy_user_can_add_message_to_own(): void
    {
        $user = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($user)->create();

        $policy = new SupportTicketPolicy;

        $this->assertTrue($policy->addMessage($user, $ticket));
    }

    /**
     * Test user cannot add message to another user's ticket.
     */
    public function test_support_ticket_policy_user_cannot_add_message_to_others(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $policy = new SupportTicketPolicy;

        $this->assertFalse($policy->addMessage($user, $ticket));
    }

    /**
     * Test user can close own ticket.
     */
    public function test_support_ticket_policy_user_can_close_own(): void
    {
        $user = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($user)->create();

        $policy = new SupportTicketPolicy;

        $this->assertTrue($policy->close($user, $ticket));
    }

    /**
     * Test user cannot close another user's ticket.
     */
    public function test_support_ticket_policy_user_cannot_close_others(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $policy = new SupportTicketPolicy;

        $this->assertFalse($policy->close($user, $ticket));
    }

    /**
     * Test super admin can close any ticket.
     */
    public function test_support_ticket_policy_super_admin_can_close_any(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($user)->create();

        $policy = new SupportTicketPolicy;

        $this->assertTrue($policy->close($admin, $ticket));
    }
}
