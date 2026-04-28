<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    // ==========================================
    // INDEX TESTS
    // ==========================================

    /**
     * Test user can list their tickets.
     */
    public function test_user_can_list_tickets(): void
    {
        SupportTicket::factory()->count(3)->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/support-tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJsonPath('pagination.total', 3);
    }

    /**
     * Test user only sees own tickets.
     */
    public function test_user_only_sees_own_tickets(): void
    {
        SupportTicket::factory()->count(2)->forUser($this->user)->create();
        $otherUser = User::factory()->create();
        SupportTicket::factory()->count(3)->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/support-tickets');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 2);
    }

    /**
     * Test unauthenticated user cannot list tickets.
     */
    public function test_unauthenticated_user_cannot_list_tickets(): void
    {
        $response = $this->getJson('/api/support-tickets');

        $response->assertStatus(401);
    }

    // ==========================================
    // STORE TESTS
    // ==========================================

    /**
     * Test user can create a ticket.
     */
    public function test_user_can_create_ticket(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/support-tickets', [
                'subject' => 'Sipariş Sorunu',
                'category' => 'order',
                'description' => 'Siparişim 3 gündür teslim edilmedi.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Destek talebiniz oluşturuldu.',
            ]);

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $this->user->id,
            'subject' => 'Sipariş Sorunu',
            'category' => 'order',
            'status' => 'open',
        ]);

        // Initial message should be created
        $ticket = SupportTicket::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can create a ticket with order reference.
     */
    public function test_user_can_create_ticket_with_order(): void
    {
        $order = Order::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/support-tickets', [
                'subject' => 'Ödeme Sorunu',
                'category' => 'payment',
                'description' => 'Ödeme yaptım ama sipariş onaylanmadı.',
                'order_id' => $order->id,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $this->user->id,
            'order_id' => $order->id,
        ]);
    }

    /**
     * Test user cannot create ticket referencing another user's order.
     */
    public function test_user_cannot_reference_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/support-tickets', [
                'subject' => 'Hack Attempt',
                'category' => 'order',
                'description' => 'Trying to reference another order.',
                'order_id' => $order->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test store validation requires mandatory fields.
     */
    public function test_store_validation_requires_mandatory_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/support-tickets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'category', 'description']);
    }

    /**
     * Test store validation checks category values.
     */
    public function test_store_validation_checks_category_values(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/support-tickets', [
                'subject' => 'Test',
                'category' => 'invalid_category',
                'description' => 'Test description.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    /**
     * Test user can view their own ticket.
     */
    public function test_user_can_view_own_ticket(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/support-tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ticket->id);
    }

    /**
     * Test user cannot view another user's ticket.
     */
    public function test_user_cannot_view_another_users_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/support-tickets/{$ticket->id}");

        $response->assertStatus(403);
    }

    /**
     * Test super admin can view any ticket.
     */
    public function test_super_admin_can_view_any_ticket(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $adminToken = $admin->createToken('admin-token')->plainTextToken;

        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$adminToken])
            ->getJson("/api/support-tickets/{$ticket->id}");

        $response->assertStatus(200);
    }

    // ==========================================
    // ADD MESSAGE TESTS
    // ==========================================

    /**
     * Test user can add message to own ticket.
     */
    public function test_user_can_add_message_to_own_ticket(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/support-tickets/{$ticket->id}/messages", [
                'message' => 'Ek bilgi vermek istiyorum.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Mesajınız gönderildi.',
            ]);

        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'message' => 'Ek bilgi vermek istiyorum.',
        ]);
    }

    /**
     * Test user cannot add message to another user's ticket.
     */
    public function test_user_cannot_add_message_to_another_users_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/support-tickets/{$ticket->id}/messages", [
                'message' => 'Should not be allowed.',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test cannot add message to closed ticket.
     */
    public function test_cannot_add_message_to_closed_ticket(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->closed()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/support-tickets/{$ticket->id}/messages", [
                'message' => 'Should fail.',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test cannot add message to resolved ticket.
     */
    public function test_cannot_add_message_to_resolved_ticket(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->resolved()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/support-tickets/{$ticket->id}/messages", [
                'message' => 'Should fail.',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test adding message to waiting ticket changes status to open.
     */
    public function test_adding_message_to_waiting_ticket_changes_status_to_open(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->waiting()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/support-tickets/{$ticket->id}/messages", [
                'message' => 'New response.',
            ]);

        $response->assertStatus(200);

        $this->assertEquals('open', $ticket->fresh()->status);
    }

    // ==========================================
    // CLOSE TESTS
    // ==========================================

    /**
     * Test user can close own ticket.
     */
    public function test_user_can_close_own_ticket(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/support-tickets/{$ticket->id}/close");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Destek talebiniz kapatıldı.',
            ]);

        $this->assertEquals('closed', $ticket->fresh()->status);
    }

    /**
     * Test user cannot close another user's ticket.
     */
    public function test_user_cannot_close_another_users_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/support-tickets/{$ticket->id}/close");

        $response->assertStatus(403);
    }

    /**
     * Test closing already closed ticket returns error.
     */
    public function test_closing_already_closed_ticket_returns_error(): void
    {
        $ticket = SupportTicket::factory()->forUser($this->user)->closed()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/support-tickets/{$ticket->id}/close");

        $response->assertStatus(422);
    }
}
