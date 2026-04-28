<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::forUser($request->user()->id)
            ->with(['order:id,order_number', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }, 'messages.user:id,business_name,role'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        $tickets->getCollection()->transform(function ($ticket) {
            $ticket->ticket_number = $ticket->ticket_number;
            $ticket->status_label = $ticket->status_label;
            $ticket->category_label = $ticket->category_label;
            $ticket->last_message = $ticket->messages->first();
            unset($ticket->messages);

            return $ticket;
        });

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'pagination' => [
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:order,payment,shipping,product,account,other',
            'description' => 'required|string|max:5000',
            'order_id' => 'nullable|exists:orders,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
        ]);

        if ($validated['order_id'] ?? null) {
            $ownsOrder = $request->user()->orders()->where('id', $validated['order_id'])->exists();
            if (! $ownsOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu sipariş size ait değil.',
                ], 403);
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'order_id' => $validated['order_id'] ?? null,
            'status' => 'open',
        ]);

        $attachments = $this->storeAttachments($request, $ticket->id);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['description'],
            'is_staff_reply' => false,
            'attachments' => $attachments ?: null,
        ]);

        $ticket->load('order:id,order_number');
        $ticket->status_label = $ticket->status_label;
        $ticket->category_label = $ticket->category_label;

        return response()->json([
            'success' => true,
            'message' => 'Destek talebiniz oluşturuldu.',
            'data' => $ticket,
        ], 201);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('view', $supportTicket);

        $supportTicket->load([
            'order:id,order_number,status,total_amount',
            'messages.user:id,business_name,role',
        ]);
        $supportTicket->status_label = $supportTicket->status_label;
        $supportTicket->category_label = $supportTicket->category_label;

        $supportTicket->messages->transform(function ($msg) {
            if ($msg->attachments) {
                $msg->attachments = collect($msg->attachments)->map(function ($att) {
                    $att['url'] = Storage::disk('public')->url($att['path']);

                    return $att;
                })->toArray();
            }

            return $msg;
        });

        return response()->json([
            'success' => true,
            'data' => $supportTicket,
        ]);
    }

    public function addMessage(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('addMessage', $supportTicket);

        if (in_array($supportTicket->status, ['closed', 'resolved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kapatılmış veya çözülmüş taleplere mesaj eklenemez.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
        ]);

        $attachments = $this->storeAttachments($request, $supportTicket->id);

        $message = SupportTicketMessage::create([
            'ticket_id' => $supportTicket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_staff_reply' => false,
            'attachments' => $attachments ?: null,
        ]);

        if ($supportTicket->status === 'waiting') {
            $supportTicket->update(['status' => 'open']);
        }

        $message->load('user:id,business_name,role');

        if ($message->attachments) {
            $message->attachments = collect($message->attachments)->map(function ($att) {
                $att['url'] = Storage::disk('public')->url($att['path']);

                return $att;
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'Mesajınız gönderildi.',
            'data' => $message,
        ]);
    }

    public function close(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('close', $supportTicket);

        if ($supportTicket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Bu talep zaten kapatılmış.',
            ], 422);
        }

        $supportTicket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Destek talebiniz kapatıldı.',
        ]);
    }

    private function storeAttachments(Request $request, int $ticketId): array
    {
        $stored = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("support-tickets/{$ticketId}", 'public');
                $stored[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        return $stored;
    }
}
