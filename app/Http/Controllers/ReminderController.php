<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    /**
     * Store a newly created reminder.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'reminder_date' => 'required|date',
            'reminder_time' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $reminder = Reminder::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'reminder_date' => $validated['reminder_date'],
            'reminder_time' => $validated['reminder_time'] ?? Carbon::now()->format('h:i A'),
            'notes' => $validated['notes'] ?? null,
            'is_dismissed' => false,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reminder scheduled!',
                'reminder' => $reminder,
            ]);
        }

        return back()->with('success', 'Reminder scheduled!');
    }

    /**
     * Mark reminder as dismissed.
     */
    public function dismiss(Request $request, Reminder $reminder): JsonResponse|RedirectResponse
    {
        $reminder->update(['is_dismissed' => true]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Reminder dismissed.']);
        }

        return back()->with('success', 'Reminder dismissed.');
    }

    /**
     * Remove the specified reminder.
     */
    public function destroy(Request $request, Reminder $reminder): JsonResponse|RedirectResponse
    {
        $reminder->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Reminder deleted.']);
        }

        return back()->with('success', 'Reminder deleted.');
    }
}
