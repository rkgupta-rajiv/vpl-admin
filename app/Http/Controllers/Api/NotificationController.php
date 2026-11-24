<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->notifications()->latest()->limit(50)->get()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? '',
            'message' => $n->data['message'] ?? '',
            'kind' => $n->data['kind'] ?? 'info',
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String(),
        ]);

        return response()->json(['success' => true, 'notifications' => $items]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
