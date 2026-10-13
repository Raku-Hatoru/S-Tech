{{--
    Partial form untuk buat & edit akun pengguna.
    Variabel yang dibutuhkan:
      - $user  : instance User (null = create, User = edit)
      - $roles : Collection<Role>
      - $kabupatenList : Collection<Kabupaten>
--}}
<div class="space-y-5">
    {{-- Nama --}}
    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
        <input id="name" type="text" name="name" required
               value="{{ old('name', $user?->name) }}"
               placeholder="Nama lengkap pengguna"
               class="w-full px-3 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition {{ $errors->has('name') ? 'border-rose-400' : 'border-slate-300' }}">
        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Alamat Email <span class="text-rose-500">*</span></label>
        <input id="email" type="email" name="email" required
               value="{{ old('email', $user?->email) }}"
               placeholder="contoh@instansi.go.id"
               class="w-full px-3 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition {{ $errors->has('email') ? 'border-rose-400' : 'border-slate-300' }}">
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- Kata Sandi --}}
    <div>
        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">
            Kata Sandi {{ $user ? '(kosongkan jika tidak ingin mengubah)' : '' }}
            @if (! $user)<span class="text-rose-500">*</span>@endif
        </label>
        <input id="password" type="password" name="password"
               @if (! $user) required @endif
               placeholder="{{ $user ? '••••••••' : 'Min. 8 karakter, huruf & angka' }}"
               class="w-full px-3 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition {{ $errors->has('password') ? 'border-rose-400' : 'border-slate-300' }}">
        @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- Konfirmasi Kata Sandi --}}
    @if (! $user)
        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   placeholder="Ulangi kata sandi"
                   class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
        </div>
    @else
        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   placeholder="Ulangi kata sandi baru (jika diubah)"
                   class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
        </div>
    @endif

    {{-- Peran --}}
    <div>
        <label for="role_id" class="block text-sm font-semibold text-slate-700 mb-1.5">Peran <span class="text-rose-500">*</span></label>
        <select id="role_id" name="role_id" required
                class="w-full px-3 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition {{ $errors->has('role_id') ? 'border-rose-400' : 'border-slate-300' }}"
                onchange="toggleKabupaten(this)">
            <option value="">-- Pilih Peran --</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}"
                    {{ old('role_id', $user?->role_id) == $role->id ? 'selected' : '' }}
                    data-kode="{{ $role->kode }}">
                    {{ $role->nama }}
                </option>
            @endforeach
        </select>
        @error('role_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- Kabupaten (hanya tampil untuk BUMDes) --}}
    <div id="field-kabupaten" class="{{ (old('role_id', $user?->role?->kode) === 'bumdes' || collect($roles)->first(fn($r) => $r->id == old('role_id', $user?->role_id))?->kode === 'bumdes') ? '' : 'hidden' }}">
        <label for="kabupaten_id" class="block text-sm font-semibold text-slate-700 mb-1.5">
            Kabupaten / Kota (BUMDes) <span class="text-rose-500">*</span>
        </label>
        <select id="kabupaten_id" name="kabupaten_id"
                class="w-full px-3 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition {{ $errors->has('kabupaten_id') ? 'border-rose-400' : 'border-slate-300' }}">
            <option value="">-- Pilih Kabupaten / Kota --</option>
            @foreach ($kabupatenList as $kab)
                <option value="{{ $kab->id }}"
                    {{ old('kabupaten_id', $user?->kabupaten_id) == $kab->id ? 'selected' : '' }}>
                    {{ $kab->nama }} ({{ $kab->provinsi?->nama }})
                </option>
            @endforeach
        </select>
        @error('kabupaten_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- Organisasi --}}
    <div>
        <label for="organisasi" class="block text-sm font-semibold text-slate-700 mb-1.5">Organisasi / Instansi</label>
        <input id="organisasi" type="text" name="organisasi"
               value="{{ old('organisasi', $user?->organisasi) }}"
               placeholder="Nama perusahaan, BUMDes, atau instansi"
               class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
        @error('organisasi')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- Telepon --}}
    <div>
        <label for="telepon" class="block text-sm font-semibold text-slate-700 mb-1.5">Nomor Telepon</label>
        <input id="telepon" type="text" name="telepon"
               value="{{ old('telepon', $user?->telepon) }}"
               placeholder="08xx-xxxx-xxxx"
               class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
    </div>

    {{-- Status Aktif --}}
    <div class="flex items-center gap-3 pt-1">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1"
               {{ old('is_active', $user?->is_active ?? true) ? 'checked' : '' }}
               class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500 transition">
        <label for="is_active" class="text-sm text-slate-700">Akun aktif (pengguna dapat login)</label>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function toggleKabupaten(select) {
            const selectedOption = select.options[select.selectedIndex];
            const kode = selectedOption?.dataset.kode;
            const field = document.getElementById('field-kabupaten');
            const select2 = document.getElementById('kabupaten_id');
            if (kode === 'bumdes') {
                field.classList.remove('hidden');
                select2.required = true;
            } else {
                field.classList.add('hidden');
                select2.required = false;
                select2.value = '';
            }
        }

        // Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function () {
            const roleSelect = document.getElementById('role_id');
            if (roleSelect) toggleKabupaten(roleSelect);
        });
    </script>
    @endpush
@endonce
