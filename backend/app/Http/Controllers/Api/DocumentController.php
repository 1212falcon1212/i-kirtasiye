<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Get current user's documents
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $documents = $user->documents()->orderBy('type')->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'type' => $doc->type,
                'type_label' => $doc->type_label,
                'original_name' => $doc->original_name,
                'file_url' => $doc->file_url,
                'status' => $doc->status,
                'status_label' => $doc->status_label,
                'rejection_reason' => $doc->rejection_reason,
                'created_at' => $doc->created_at,
                'reviewed_at' => $doc->reviewed_at,
            ];
        });

        // Check which required documents are missing or rejected (role-based)
        $requiredTypes = SellerDocument::getRequiredTypes($user->role);
        $uploadedTypes = $documents->pluck('type')->toArray();
        $approvedTypes = $documents->where('status', 'approved')->pluck('type')->toArray();

        $missingTypes = array_diff($requiredTypes, $uploadedTypes);
        $rejectedTypes = $documents->where('status', 'rejected')->pluck('type')->toArray();

        $allApproved = empty($missingTypes) &&
            count(array_intersect($approvedTypes, $requiredTypes)) === count($requiredTypes);

        return response()->json([
            'documents' => $documents,
            'required_types' => collect($requiredTypes)->map(fn ($type) => [
                'type' => $type,
                'label' => SellerDocument::TYPE_LABELS[$type] ?? $type,
            ]),
            'missing_types' => $missingTypes,
            'rejected_types' => $rejectedTypes,
            'all_approved' => $allApproved,
            'type_labels' => SellerDocument::TYPE_LABELS,
            'status_labels' => SellerDocument::STATUS_LABELS,
            'user_role' => $user->role,
        ]);
    }

    /**
     * Upload a document
     */
    public function upload(Request $request): JsonResponse
    {
        $this->authorize('upload', SellerDocument::class);

        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(SellerDocument::TYPE_LABELS)),
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $user = $request->user();
        $file = $request->file('file');

        // Check if document of this type already exists
        $existing = $user->documents()->where('type', $validated['type'])->first();

        if ($existing && $existing->status === 'approved') {
            return response()->json([
                'message' => 'Bu belge tipi zaten onaylanmış durumda.',
            ], 422);
        }

        // Delete old file if exists
        if ($existing) {
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        // Store new file
        $path = $file->store('documents/'.$user->id, 'public');

        $document = SellerDocument::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Belge başarıyla yüklendi.',
            'document' => [
                'id' => $document->id,
                'type' => $document->type,
                'type_label' => $document->type_label,
                'original_name' => $document->original_name,
                'file_url' => $document->file_url,
                'status' => $document->status,
                'status_label' => $document->status_label,
            ],
        ], 201);
    }

    /**
     * Delete a document
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $document = SellerDocument::find($id);

        if (! $document) {
            return response()->json(['message' => 'Belge bulunamadı.'], 404);
        }

        $this->authorize('delete', $document);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Belge silindi.']);
    }

    /**
     * Get document status summary for auth check
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $requiredTypes = SellerDocument::getRequiredTypes($user->role);
        $documents = $user->documents()->get();

        $approvedTypes = $documents->where('status', 'approved')->pluck('type')->toArray();
        $pendingTypes = $documents->where('status', 'pending')->pluck('type')->toArray();
        $rejectedTypes = $documents->where('status', 'rejected')->pluck('type')->toArray();

        $allRequired = count(array_intersect($approvedTypes, $requiredTypes)) === count($requiredTypes);
        $hasRejected = ! empty(array_intersect($rejectedTypes, $requiredTypes));
        $hasPending = ! empty(array_intersect($pendingTypes, $requiredTypes));

        return response()->json([
            'documents_approved' => $allRequired,
            'has_pending' => $hasPending,
            'has_rejected' => $hasRejected,
            'required_count' => count($requiredTypes),
            'approved_count' => count(array_intersect($approvedTypes, $requiredTypes)),
        ]);
    }
}
