<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->paginate(10);
        return view('master.users', compact('users'));
    }

    public function create(): View
    {
        return view('master.users-edit', ['user' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,dosen',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        User::create($validated);

        return redirect()->route('users')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('master.users-edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,dosen',
        ]);

        $user->update($validated);

        return redirect()->route('users')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users')->with('error', 'Anda tidak bisa menghapus diri sendiri.');
        }

        $user->delete();
        return redirect()->route('users')->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $user->update(['password' => bcrypt('password123')]);
        return redirect()->route('users')->with('success', 'Password user ' . $user->name . ' telah di-reset menjadi "password123".');
    }
}
