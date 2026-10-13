<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', ['users' => User::orderBy('name')->paginate(20)]);
    }

    public function store(Request $request)
    {
        User::create($request->validate($this->rules()));

        return back()->with('status', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user));
        if (empty($data['password'])) {
            unset($data['password']);
        }
        DB::transaction(function () use ($user, $data) {
            $admins = User::where('role', 'admin')->where('is_active', true)->lockForUpdate()->get();
            $user->refresh();
            if ($user->role === 'admin' && $user->is_active && $admins->count() <= 1
                && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Minimal satu admin aktif harus tetap tersedia.']);
            }
            $user->update($data);
            if (! $user->is_active || isset($data['password'])) {
                $user->tokens()->delete();
            }
        });

        return back()->with('status', 'Pengguna diperbarui.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(12)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => 'required|boolean',
        ];
    }
}
