<?php

namespace App\Http\Controllers;

use App\Models\Todo;
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
}
