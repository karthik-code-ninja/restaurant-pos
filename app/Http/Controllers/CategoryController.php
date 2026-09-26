<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::withCount('foods');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category = Category::create($validated);

        AuditLog::log(
            action: 'category_created',
            module: 'food',
            referenceId: (string) $category->id,
            description: "Category '{$category->name}' created"
        );

        return redirect()->route('categories.index')->with('success', 'Category added successfully!');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $oldValues = $category->only(['name', 'description', 'sort_order', 'is_active']);
        $category->update($validated);

        AuditLog::log(
            action: 'category_updated',
            module: 'food',
            referenceId: (string) $category->id,
            oldValues: $oldValues,
            newValues: $validated,
            description: "Category '{$category->name}' updated"
        );

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->foods()->count() > 0) {
            return back()->with('error', 'Cannot delete category that contains food items. Deactivate it or reassign foods first.');
        }

        $name = $category->name;
        $category->delete();

        AuditLog::log(
            action: 'category_deleted',
            module: 'food',
            referenceId: (string) $category->id,
            description: "Category '{$name}' deleted"
        );

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully!');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        $statusStr = $category->is_active ? 'activated' : 'deactivated';

        AuditLog::log(
            action: 'category_status_changed',
            module: 'food',
            referenceId: (string) $category->id,
            description: "Category '{$category->name}' was {$statusStr}"
        );

        return back()->with('success', "Category {$statusStr} successfully!");
    }
}
