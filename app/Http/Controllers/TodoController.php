<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');

        $query = Todo::query()
            ->with('user:id,name,email')
            ->when($search, fn ($q) => $q->where('text', 'like', "%{$search}%")
            )
            ->latest();

        // Normal user sees only their own todos; admin sees all
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $todos = $query->paginate(10)->withQueryString();

        return Inertia::render('Todos/Index', [
            'todos' => $todos,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'is_completed' => ['boolean'],
        ]);

        Auth::user()->todos()->create($validated);

        return redirect()
            ->route('todos.index')
            ->with('success', 'Todo created.');
    }

    public function update(Request $request, Todo $todo): RedirectResponse
    {
        // Authorize: only the owner or an admin can update
        if (! Auth::user()->isAdmin() && $todo->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'is_completed' => ['boolean'],
        ]);

        $todo->update($validated);

        return redirect()
            ->route('todos.index')
            ->with('success', 'Todo updated.');
    }

    public function destroy(Todo $todo): RedirectResponse
    {
        if (! Auth::user()->isAdmin() && $todo->user_id !== Auth::id()) {
            abort(403);
        }

        $todo->delete();

        return redirect()
            ->route('todos.index')
            ->with('success', 'Todo deleted.');
    }
}
