<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderMessageController extends Controller
{
    /**
     * Get all transparent chat messages for an order.
     */
    public function index(int $orderId): JsonResponse
    {
        $order = Order::withoutGlobalScope('tenant')->findOrFail($orderId);

        $messages = $order->messages()->orderBy('created_at', 'asc')->get()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'sender_role' => $msg->sender_role,
                'sender_name' => $msg->sender_name,
                'message' => $msg->message,
                'created_at' => $msg->created_at->format('H:i'),
                'date' => $msg->created_at->format('d M Y'),
                'is_system' => $msg->sender_role === 'SYSTEM',
            ];
        });

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->delivery_status,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a new message in the 4-party delivery thread (Sender, Courier, Dispatcher, Recipient).
     */
    public function store(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'sender_role' => 'required|string|in:DRIVER,DISPATCHER,SENDER,RECIPIENT,SYSTEM',
            'sender_name' => 'required|string|max:100',
            'message' => 'required|string|max:1000',
        ]);

        $order = Order::withoutGlobalScope('tenant')->findOrFail($orderId);

        $message = OrderMessage::create([
            'order_id' => $order->id,
            'sender_role' => $validated['sender_role'],
            'sender_name' => $validated['sender_name'],
            'message' => $validated['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'sender_role' => $message->sender_role,
                'sender_name' => $message->sender_name,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('d M Y'),
                'is_system' => $message->sender_role === 'SYSTEM',
            ],
        ], 201);
    }
}
