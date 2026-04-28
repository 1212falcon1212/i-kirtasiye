<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportTicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can add a message to the ticket.
     */
    public function addMessage(User $user, SupportTicket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can close the ticket.
     */
    public function close(User $user, SupportTicket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }
}
