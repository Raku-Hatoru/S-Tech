<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Hanya Admin yang boleh mengubah akun.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Aturan validasi update akun.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $pengguna */
        $pengguna = $this->route('pengguna');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')->ignore($pengguna?->id)],
            'password' => ['nullable', Password::min(8)->letters()->numbers(), 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'kabupaten_id' => [
                Rule::requiredIf(function () {
                    $role = Role::find($this->input('role_id'));

                    return $role && $role->kode === 'bumdes';
                }),
                'nullable',
                'exists:kabupaten,id',
            ],
            'organisasi' => ['nullable', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:25'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah digunakan.',
            'role_id.required' => 'Pilih peran pengguna.',
            'role_id.exists' => 'Peran tidak valid.',
            'kabupaten_id.required' => 'Akun BUMDes wajib terikat ke satu kabupaten.',
            'kabupaten_id.exists' => 'Kabupaten tidak ditemukan.',
        ];
    }

    /**
     * Siapkan data sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $role = Role::find($this->input('role_id'));
        if ($role && $role->kode !== 'bumdes') {
            $this->merge(['kabupaten_id' => null]);
        }
    }

    /**
     * Kembalikan hanya data yang tervalidasi, abaikan password kosong.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Jangan update password bila tidak diisi
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
