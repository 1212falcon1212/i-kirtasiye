<?php

namespace App\Policies;

use App\Models\SellerDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, SellerDocument $document): bool
    {
        // Super admin can view any document
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can view their own documents
        return $document->user_id === $user->id;
    }

    /**
     * Determine whether the user can upload documents.
     */
    public function upload(User $user): bool
    {
        // Super admin cannot upload documents (they don't need verification)
        if ($user->isSuperAdmin()) {
            return false;
        }

        // Pharmacists can upload documents
        return $user->isRetailer();
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, SellerDocument $document): bool
    {
        // Super admin can delete any document
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only delete their own pending documents
        if ($document->user_id !== $user->id) {
            return false;
        }

        // Can only delete pending documents
        return $document->status === 'pending';
    }
}
