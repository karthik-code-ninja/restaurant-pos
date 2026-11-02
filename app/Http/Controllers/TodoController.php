<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    /**
     * Store a newly created todo item.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'nullable|in:high,medium,low',
            'due_date' => 'nullable|date',
        ]);

        $todo = Todo::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? Carbon::today()->toDateString(),
            'is_completed' => false,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Todo added successfully!',
                'todo' => $todo,
            ]);
        }

        return back()->with('success', 'Todo task added!');
    }

    /**
     * Toggle todo completion status (F12 shortcut).
     */
    public function toggle(Request $request, ?Todo $todo = null): JsonResponse|RedirectResponse
    {
        if (! $todo) {
            // If called without specific ID (e.g. shortcut F12), toggle first pending todo
            $todo = Todo::where('is_completed', false)->orderBy('id')->first();
        }

        if (! $todo) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'No active todo to mark complete.']);
            }

            return back()->with('info', 'No pending tasks to complete.');
        }

        $todo->is_completed = ! $todo->is_completed;
        $todo->completed_at = $todo->is_completed ? Carbon::now() : null;
        $todo->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_completed' => $todo->is_completed,
                'message' => $todo->is_completed ? 'Task marked completed!' : 'Task restored!',
                'todo' => $todo,
            ]);
        }

        return back()->with('success', 'Task status updated!');
    }

    /**
     * Remove the specified todo from storage.
     */
    public function destroy(Request $request, Todo $todo): JsonResponse|RedirectResponse
    {
        $todo->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Todo removed.']);
        }

        return back()->with('success', 'Todo deleted.');
    }
}
