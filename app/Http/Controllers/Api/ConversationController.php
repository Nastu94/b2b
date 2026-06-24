<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ConversationModerationService;

class ConversationController extends Controller
{
    protected $moderationService;

    public function __construct(ConversationModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'vendor_account_id' => 'required|exists:vendor_accounts,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'prestashop_customer_id' => 'required|integer',
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'message' => 'required|string',
            'source' => 'nullable|string'
        ]);

        // 1. Cerchiamo se c'è già una conversazione aperta per questo utente e vendor
        $existingThread = \App\Models\ConversationThread::where('vendor_account_id', $validated['vendor_account_id'])
            ->where('prestashop_customer_id', $validated['prestashop_customer_id'])
            ->where('offering_id', $validated['offering_id'] ?? null)
            ->where('status', 'open')
            ->first();

        if ($existingThread) {
            // Conversazione trovata: accodiamo il messaggio
            $this->createMessage($existingThread, $validated['message'], 'customer', $validated['prestashop_customer_id']);
            
            $existingThread->update([
                'vendor_unread_count' => $existingThread->vendor_unread_count + 1,
                'admin_unread_count' => $existingThread->admin_unread_count + 1,
                'last_message_at' => now(),
            ]);

            // Se i non letti prima di questo messaggio erano 0, notifichiamo il vendor
            if ($existingThread->vendor_unread_count === 1 && $existingThread->vendorAccount && $existingThread->vendorAccount->user) {
                \Illuminate\Support\Facades\Mail::to($existingThread->vendorAccount->user->email)
                    ->queue(new \App\Mail\NewConversationMessageVendorMail($existingThread));
            }

            return response()->json([
                'success' => true,
                'conversation_id' => $existingThread->id,
            ]);
        }

        // 2. Nessuna conversazione aperta trovata: ne creiamo una nuova
        $thread = \App\Models\ConversationThread::create([
            'vendor_account_id' => $validated['vendor_account_id'],
            'offering_id' => $validated['offering_id'] ?? null,
            'booking_id' => $validated['booking_id'] ?? null,
            'prestashop_customer_id' => $validated['prestashop_customer_id'],
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_email' => $validated['customer_email'] ?? null,
            'source' => $validated['source'] ?? 'prestashop',
            'status' => 'open',
            'vendor_unread_count' => 1,
            'admin_unread_count' => 1,
            'last_message_at' => now(),
        ]);

        $this->createMessage($thread, $validated['message'], 'customer', $validated['prestashop_customer_id']);

        if ($thread->vendorAccount && $thread->vendorAccount->user) {
            \Illuminate\Support\Facades\Mail::to($thread->vendorAccount->user->email)
                ->queue(new \App\Mail\NewConversationMessageVendorMail($thread));
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $thread->id,
        ]);
    }

    public function messages(Request $request, \App\Models\ConversationThread $conversation)
    {
        $customerId = $request->input('prestashop_customer_id');

        if (!$customerId || (int)$customerId !== (int)$conversation->prestashop_customer_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        $mapped = $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'body' => $msg->body_filtered ?? $msg->body_original,
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'messages' => $mapped
        ]);
    }

    public function storeMessage(Request $request, \App\Models\ConversationThread $conversation)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'prestashop_customer_id' => 'required|integer',
        ]);

        if ((int)$validated['prestashop_customer_id'] !== (int)$conversation->prestashop_customer_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to send message'], 403);
        }

        if ($conversation->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'Conversation is not open'], 403);
        }

        $msg = $this->createMessage($conversation, $validated['message'], 'customer', $validated['prestashop_customer_id']);

        $conversation->update([
            'vendor_unread_count' => $conversation->vendor_unread_count + 1,
            'admin_unread_count' => $conversation->admin_unread_count + 1,
            'last_message_at' => now(),
        ]);

        if ($conversation->vendorAccount && $conversation->vendorAccount->user) {
            \Illuminate\Support\Facades\Mail::to($conversation->vendorAccount->user->email)
                ->queue(new \App\Mail\NewConversationMessageVendorMail($conversation));
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'body' => $msg->body_filtered ?? $msg->body_original,
                'created_at' => $msg->created_at->toIso8601String(),
            ]
        ]);
    }

    public function markAsRead(Request $request, \App\Models\ConversationThread $conversation)
    {
        $customerId = $request->input('prestashop_customer_id');

        if (!$customerId || (int)$customerId !== (int)$conversation->prestashop_customer_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $type = $request->input('user_type', 'customer');
        if ($type === 'customer' && $conversation->customer_unread_count > 0) {
            $conversation->update(['customer_unread_count' => 0]);
        }

        return response()->json(['success' => true]);
    }

    public function unreadCount(Request $request)
    {
        $customerId = $request->input('prestashop_customer_id');

        $count = 0;
        if ($customerId) {
            $count = \App\Models\ConversationThread::where('prestashop_customer_id', $customerId)->sum('customer_unread_count');
        }

        return response()->json(['success' => true, 'unread_count' => $count]);
    }

    public function indexCustomer(Request $request)
    {
        $customerId = $request->input('prestashop_customer_id');

        if (!$customerId) {
            return response()->json(['success' => false, 'message' => 'Missing customer ID'], 400);
        }

        $threads = \App\Models\ConversationThread::with(['vendorAccount' => function($q) {
                $q->select('id', 'company_name', 'profile_image_path');
            }, 'offering' => function($q) {
                $q->select('id', 'name');
            }])
            ->where('prestashop_customer_id', $customerId)
            ->orderBy('last_message_at', 'desc')
            ->get();

        $mapped = $threads->map(function ($thread) {
            $lastMsg = $thread->messages()->latest()->first();
            return [
                'conversation_id' => $thread->id,
                'vendor_id' => $thread->vendor_account_id,
                'vendor_name' => $thread->vendorAccount ? $thread->vendorAccount->company_name : 'Partner',
                'vendor_logo' => $thread->vendorAccount && $thread->vendorAccount->profile_image_path ? url('storage/' . $thread->vendorAccount->profile_image_path) : null,
                'offering_id' => $thread->offering_id,
                'offering_title' => $thread->offering ? $thread->offering->name : null,
                'last_message' => $lastMsg ? \Illuminate\Support\Str::limit($lastMsg->body_filtered ?? $lastMsg->body_original, 50) : null,
                'last_message_at' => $thread->last_message_at ? $thread->last_message_at->toIso8601String() : null,
                'unread_count' => $thread->customer_unread_count,
                'status' => $thread->status,
            ];
        });

        return response()->json([
            'success' => true,
            'conversations' => $mapped
        ]);
    }

    protected function createMessage(\App\Models\ConversationThread $thread, string $text, string $senderType, $senderId = null)
    {
        $moderated = $this->moderationService->moderate($text);

        return $thread->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'body_original' => $moderated['original'],
            'body_filtered' => $moderated['filtered'],
            'moderation_status' => $moderated['status'],
            'moderation_flags' => $moderated['flags'],
        ]);
    }

}
