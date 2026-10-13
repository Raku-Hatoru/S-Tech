<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Kabupaten;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar semua pengguna.
     */
    public function index(Request $request): View
    {
        $users = User::with(['role', 'kabupaten.provinsi'])
            ->when($request->search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('organisasi', 'like', "%{$search}%");
            }))
            ->when($request->role, fn ($q, $role) => $q->whereHas('role', fn ($q2) => $q2->where('kode', $role)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('nama')->get();

        return view('admin.pengguna.index', compact('users', 'roles'));
    }

    /**
     * Form buat akun baru.
     */
    public function create(): View
    {
        $roles = Role::orderBy('nama')->get();
        $kabupatenList = Kabupaten::with('provinsi')->orderBy('nama')->get();

        return view('admin.pengguna.create', compact('roles', 'kabupatenList'));
    }

    /**
     * Simpan akun baru.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        AuditService::log('create_user', 'users', $user->id, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'is_active' => $user->is_active,
        ]);

        return redirect()
            ->route('admin.pengguna.index')
            ->with('sukses', 'Akun pengguna berhasil dibuat.');
    }

    /**
     * Form edit akun.
     */
    public function edit(User $pengguna): View
    {
        $roles = Role::orderBy('nama')->get();
        $kabupatenList = Kabupaten::with('provinsi')->orderBy('nama')->get();

        return view('admin.pengguna.edit', compact('pengguna', 'roles', 'kabupatenList'));
    }

    /**
     * Perbarui data akun.
     */
    public function update(UpdateUserRequest $request, User $pengguna): RedirectResponse
    {
        $validated = $request->validated();

        // ISO 27001 A.5.15: Cegah admin mendemosi peran diri sendiri atau menonaktifkan akun sendiri
        if ($pengguna->id === auth()->id()) {
            $roleAdmin = Role::where('kode', 'admin')->first();
            if (isset($validated['role_id']) && (int) $validated['role_id'] !== (int) $roleAdmin?->id) {
                return back()->with('galat', 'Anda tidak dapat mengubah peran akun administrator Anda sendiri.');
            }
            if (isset($validated['is_active']) && ! $validated['is_active']) {
                return back()->with('galat', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }
        }

        $oldData = [
            'name' => $pengguna->name,
            'email' => $pengguna->email,
            'role_id' => $pengguna->role_id,
            'is_active' => $pengguna->is_active,
        ];

        $pengguna->update($validated);

        AuditService::log('update_user', 'users', $pengguna->id, $oldData, [
            'name' => $pengguna->name,
            'email' => $pengguna->email,
            'role_id' => $pengguna->role_id,
            'is_active' => $pengguna->is_active,
        ]);

        return redirect()
            ->route('admin.pengguna.index')
            ->with('sukses', 'Akun pengguna berhasil diperbarui.');
    }

    /**
     * Nonaktifkan akun (toggle is_active).
     */
    public function toggleAktif(User $pengguna): RedirectResponse
    {
        // Cegah admin menonaktifkan diri sendiri
        if ($pengguna->id === auth()->id()) {
            return back()->with('galat', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $oldStatus = $pengguna->is_active;
        $pengguna->update(['is_active' => ! $pengguna->is_active]);

        AuditService::log('toggle_user_active', 'users', $pengguna->id, [
            'is_active' => $oldStatus,
        ], [
            'is_active' => $pengguna->is_active,
        ]);

        $pesan = $pengguna->is_active ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.';

        return back()->with('sukses', $pesan);
    }
}
