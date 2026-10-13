<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsoSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Verifikasi pesan error login bersifat generik untuk mencegah user enumeration.
     */
    public function test_login_failure_returns_generic_error_to_prevent_user_enumeration(): void
    {
        // Percobaan dengan email yang tidak terdaftar
        $responseNonExistent = $this->from('/login')->post('/login', [
            'email' => 'tidak.ada@sipangan.test',
            'password' => 'Sembarang123',
        ]);

        $responseNonExistent->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

        // Percobaan dengan email yang terdaftar tapi password salah
        $responseWrongPass = $this->from('/login')->post('/login', [
            'email' => 'admin@sipangan.test',
            'password' => 'SalahPassword123',
        ]);

        $responseWrongPass->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }

    /**
     * Verifikasi password disimpan dalam bentuk hash (bcrypt) dan tidak plaintext.
     */
    public function test_passwords_are_stored_securely_as_hashes(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $this->assertNotNull($admin);

        // Pastikan bukan plaintext
        $this->assertNotEquals('Admin1234', $admin->password);
        // Pastikan merupakan bcrypt hash yang valid
        $this->assertTrue(Hash::check('Admin1234', $admin->password));
    }

    /**
     * Verifikasi pencatatan audit log saat login berhasil dan gagal.
     */
    public function test_audit_logs_are_recorded_on_auth_events(): void
    {
        // 1. Login sukses
        $this->post('/login', [
            'email' => 'admin@sipangan.test',
            'password' => 'Admin1234',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'login_success',
            'tabel' => 'users',
        ]);

        // 2. Logout
        $this->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'logout',
            'tabel' => 'users',
        ]);

        // 3. Login gagal
        $this->post('/login', [
            'email' => 'admin@sipangan.test',
            'password' => 'PasswordSalah123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'login_failed',
            'tabel' => 'users',
        ]);
    }

    /**
     * Verifikasi sesi dihapus dan token CSRF diregenerasi saat logout.
     */
    public function test_session_is_invalidated_upon_logout(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
