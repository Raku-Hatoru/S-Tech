@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-5">
        <a href="{{ route('admin.pengguna.index') }}" class="hover:text-slate-700 transition">Pengguna</a>
        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 font-medium">Edit: {{ $pengguna->name }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-7">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Edit Akun Pengguna</h1>
        <p class="text-sm text-slate-500 mb-6">
            Perbarui data akun. Kosongkan bidang kata sandi jika tidak ingin menggantinya.
        </p>

        {{-- Info status akun --}}
        @if (! $pengguna->is_active)
            <div class="mb-5 p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800 flex gap-2 items-start">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Akun ini saat ini <strong>nonaktif</strong>. Pengguna tidak bisa login.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pengguna.update', $pengguna) }}">
            @csrf
            @method('PUT')
            @include('admin.pengguna._form', ['user' => $pengguna])

            <div class="flex gap-3 mt-7 pt-6 border-t border-slate-100">
                <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold rounded-xl transition">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.pengguna.index') }}" class="px-5 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
