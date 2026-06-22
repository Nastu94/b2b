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
            'prestashop_customer_id' => 'nullable|integer',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'message' => 'required|string',
            'source' => 'nullable|string'
        ]);

        $existingThread = null;

        // 1. Cerchiamo se c'è già una conversazione aperta per questo utente e vendor
        if (!empty($validated['prestashop_customer_id'])) {
            $existingThread = \App\Models\ConversationThread::where('vendor_account_id', $validated['vendor_account_id'])
                ->where('prestashop_customer_id', $validated['prestashop_customer_id'])
                ->where('offering_id', $validated['offering_id'] ?? null)
                ->where('status', 'open')
                ->first();
        } elseif ($request->has('guest_token')) {
            $hash = hash('sha256', $request->input('guest_token'));
            $existingThread = \App\Models\ConversationThread::where('vendor_account_id', $validated['vendor_account_id'])
                ->where('guest_token_hash', $hash)
                ->where('offering_id', $validated['offering_id'] ?? null)
                ->where('status', 'open')
                ->first();
        } elseif (!empty($validated['guest_email'])) {
            $existingThread = \App\Models\ConversationThread::where('vendor_account_id', $validated['vendor_account_id'])
                ->where('guest_email', $validated['guest_email'])
                ->where('offering_id', $validated['offering_id'] ?? null)
                ->where('status', 'open')
                ->first();
        }

        if ($existingThread) {
            // Se l'utente era un guest anonimo e ora ha fornito nome/email, aggiorniamo il record
            $needsUpdate = false;
            $updateData = [];
            
            if (empty($existingThread->guest_email) && !empty($validated['guest_email'])) {
                $updateData['guest_email'] = $validated['guest_email'];
                $needsUpdate = true;
            }
            if (empty($existingThread->guest_name) && !empty($validated['guest_name'])) {
                $updateData['guest_name'] = $validated['guest_name'];
                $needsUpdate = true;
            }
            
            if ($needsUpdate) {
                $existingThread->update($updateData);
            }

            // Conversazione trovata: accodiamo il messaggio
            $this->createMessage($existingThread, $validated['message'], empty($validated['prestashop_customer_id']) ? 'guest' : 'customer', $validated['prestashop_customer_id'] ?? null);
            
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
                'guest_token' => $request->input('guest_token') ?? null, // Restituiamo il token passato se esisteva
            ]);
        }

        // 2. Nessuna conversazione aperta trovata: ne creiamo una nuova
        $token = null;
        $guestTokenHash = null;
        
        if (empty($validated['prestashop_customer_id'])) {
            if ($request->has('guest_token') && !empty($request->input('guest_token'))) {
                $token = $request->input('guest_token');
                $guestTokenHash = hash('sha256', $token);
            } else {
                $token = \Illuminate\Support\Str::random(60);
                $guestTokenHash = hash('sha256', $token);
            }
        }

        $thread = \App\Models\ConversationThread::create([
            'vendor_account_id' => $validated['vendor_account_id'],
            'offering_id' => $validated['offering_id'] ?? null,
            'booking_id' => $validated['booking_id'] ?? null,
            'prestashop_customer_id' => $validated['prestashop_customer_id'] ?? null,
            'guest_name' => $validated['guest_name'] ?? null,
            'guest_email' => $validated['guest_email'] ?? null,
            'guest_token_hash' => $guestTokenHash,
            'guest_token_expires_at' => $guestTokenHash ? now()->addDays(30) : null,
            'source' => $validated['source'] ?? 'prestashop',
            'status' => 'open',
            'vendor_unread_count' => 1,
            'admin_unread_count' => 1,
            'last_message_at' => now(),
        ]);

        $this->createMessage($thread, $validated['message'], empty($validated['prestashop_customer_id']) ? 'guest' : 'customer', $validated['prestashop_customer_id'] ?? null);

        if ($thread->vendorAccount && $thread->vendorAccount->user) {
            \Illuminate\Support\Facades\Mail::to($thread->vendorAccount->user->email)
                ->queue(new \App\Mail\NewConversationMessageVendorMail($thread));
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $thread->id,
            'guest_token' => $token,
        ]);
    }

    public function messages(Request $request, \App\Models\ConversationThread $conversation)
    {
        $guestToken = $request->input('guest_token');
        $customerId = $request->input('prestashop_customer_id');

        $authorized = false;
        if ($guestToken && hash('sha256', $guestToken) === $conversation->guest_token_hash) {
            $authorized = true;
        } elseif ($customerId && (int)$customerId === (int)$conversation->prestashop_customer_id) {
            $authorized = true;
        }

        if (!$authorized) {
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
            'guest_token' => 'nullable|string',
            'prestashop_customer_id' => 'nullable|integer',
        ]);

        $senderType = null;
        $senderId = null;

        if (!empty($validated['guest_token']) && hash('sha256', $validated['guest_token']) === $conversation->guest_token_hash) {
            $senderType = 'guest';
        } elseif (!empty($validated['prestashop_customer_id']) && (int)$validated['prestashop_customer_id'] === (int)$conversation->prestashop_customer_id) {
            $senderType = 'customer';
            $senderId = $validated['prestashop_customer_id'];
        }

        if (!$senderType) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to send message'], 403);
        }

        $msg = $this->createMessage($conversation, $validated['message'], $senderType, $senderId);

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
        $type = $request->input('user_type', 'customer');
        if (in_array($type, ['customer', 'guest'])) {
            $conversation->update(['customer_unread_count' => 0]);
        }

        return response()->json(['success' => true]);
    }

    public function getByToken(Request $request, $token)
    {
        $hash = hash('sha256', $token);
        
        $query = \App\Models\ConversationThread::with(['vendorAccount', 'offering'])
            ->where('guest_token_hash', $hash);
            
        if ($request->has('vendor_account_id')) {
            $query->where('vendor_account_id', $request->input('vendor_account_id'));
        }
        
        $thread = $query->first();

        if (!$thread) {
            // Se non trova il thread specifico per questo vendor, non è un errore del token, ma solo che non c'è ancora conversazione.
            // Controlliamo se il token è valido in generale (esiste almeno un thread).
            $tokenExists = \App\Models\ConversationThread::where('guest_token_hash', $hash)->exists();
            if ($tokenExists) {
                return response()->json(['success' => true, 'conversation' => null]);
            }
            return response()->json(['success' => false, 'message' => 'Token invalid or expired'], 404);
        }

        if ($thread->guest_token_expires_at && $thread->guest_token_expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Token invalid or expired'], 404);
        }

        return response()->json([
            'success' => true,
            'conversation' => $thread
        ]);
    }

    public function unreadCount(Request $request)
    {
        $token = $request->input('guest_token');
        $customerId = $request->input('prestashop_customer_id');

        $count = 0;
        if ($token) {
            $hash = hash('sha256', $token);
            $count = \App\Models\ConversationThread::where('guest_token_hash', $hash)->sum('customer_unread_count');
        } elseif ($customerId) {
            $count = \App\Models\ConversationThread::where('prestashop_customer_id', $customerId)->sum('customer_unread_count');
        }

        return response()->json(['success' => true, 'unread_count' => $count]);
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
