@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-5">
        <a href="{{ route('admin.pengguna.index') }}" class="hover:text-slate-700 transition">Pengguna</a>
        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 font-medium">Buat Akun Baru</span>
    </nav>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-7">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Buat Akun Pengguna</h1>
        <p class="text-sm text-slate-500 mb-6">Isi detail akun yang akan diterbitkan. Akun BUMDes wajib terikat ke satu kabupaten.</p>

        <form method="POST" action="{{ route('admin.pengguna.store') }}">
            @csrf
            @include('admin.pengguna._form', ['user' => null])

            <div class="flex gap-3 mt-7 pt-6 border-t border-slate-100">
                <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold rounded-xl transition">
                    Buat Akun
                </button>
                <a href="{{ route('admin.pengguna.index') }}" class="px-5 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
