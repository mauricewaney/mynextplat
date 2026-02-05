<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * List contact messages with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::with(['user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Search in subject or message
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate($request->get('per_page', 20));

        return response()->json($messages);
    }

    /**
     * Get a single contact message.
     */
    public function show(int $id): JsonResponse
    {
        $message = ContactMessage::with(['user:id,name,email'])->findOrFail($id);

        return response()->json($message);
    }

    /**
     * Update contact message status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactMessage::STATUSES))],
        ]);

        $message->status = $validated['status'];
        $message->save();

        return response()->json([
            'message' => 'Status updated.',
            'contact_message' => $message->fresh(['user:id,name,email']),
        ]);
    }

    /**
     * Delete a contact message.
     */
    public function destroy(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Message deleted.']);
    }
}
