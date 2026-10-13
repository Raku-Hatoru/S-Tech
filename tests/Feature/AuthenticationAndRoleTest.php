<?php

namespace Tests\Feature;

use App\Models\Kabupaten;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
    }

    public function test_admin_can_login_and_is_redirected_to_admin_pengguna(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $this->assertNotNull($admin);

        $response = $this->post('/login', [
            'email' => 'admin@sipangan.test',
            'password' => 'Admin1234',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.pengguna.index'));
    }

    public function test_bumdes_can_login_and_is_redirected_to_panen(): void
    {
        $bumdes = User::where('email', 'bumdes.banyumas@sipangan.test')->first();
        $this->assertNotNull($bumdes);

        $response = $this->post('/login', [
            'email' => 'bumdes.banyumas@sipangan.test',
            'password' => 'Bumdes1234',
        ]);

        $this->assertAuthenticatedAs($bumdes);
        $response->assertRedirect(route('panen.index'));
    }

    public function test_distributor_can_login_and_is_redirected_to_pengiriman(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();
        $this->assertNotNull($distributor);

        $response = $this->post('/login', [
            'email' => 'distributor1@sipangan.test',
            'password' => 'Distrib1234',
        ]);

        $this->assertAuthenticatedAs($distributor);
        $response->assertRedirect(route('pengiriman.index'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@sipangan.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();
        $distributor->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'distributor1@sipangan.test',
            'password' => 'Distrib1234',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.']);
    }

    public function test_login_rate_limiting_triggers_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'throttletest@sipangan.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'throttletest@sipangan.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_user_can_logout(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $response = $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->get(route('admin.pengguna.index'))->assertRedirect(route('login'));
        $this->get(route('pengiriman.index'))->assertRedirect(route('login'));
        $this->get(route('panen.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_gets_403_when_accessing_admin_routes(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();
        $bumdes = User::where('email', 'bumdes.banyumas@sipangan.test')->first();

        $this->actingAs($distributor)
            ->get(route('admin.pengguna.index'))
            ->assertStatus(403);

        $this->actingAs($bumdes)
            ->get(route('admin.pengguna.index'))
            ->assertStatus(403);
    }

    public function test_distributor_cannot_access_bumdes_panen(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();

        $this->actingAs($distributor)
            ->get(route('panen.index'))
            ->assertStatus(403);
    }

    public function test_bumdes_cannot_access_pengiriman_buat(): void
    {
        $bumdes = User::where('email', 'bumdes.banyumas@sipangan.test')->first();

        $this->actingAs($bumdes)
            ->get(route('pengiriman.create'))
            ->assertStatus(403);
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $response = $this->actingAs($admin)->get(route('admin.pengguna.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('admin@sipangan.test');
        $response->assertSee('distributor1@sipangan.test');
        $response->assertSee('bumdes.banyumas@sipangan.test');
    }

    public function test_admin_can_create_new_distributor_user(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $roleDistributor = Role::where('kode', 'distributor')->first();

        $response = $this->actingAs($admin)->post(route('admin.pengguna.store'), [
            'name' => 'Distributor Baru',
            'email' => 'distributor.baru@sipangan.test',
            'password' => 'Dist123456',
            'password_confirmation' => 'Dist123456',
            'role_id' => $roleDistributor->id,
            'organisasi' => 'PT Mitra Baru Logistik',
            'telepon' => '081122334455',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('users', [
            'email' => 'distributor.baru@sipangan.test',
            'name' => 'Distributor Baru',
            'organisasi' => 'PT Mitra Baru Logistik',
            'kabupaten_id' => null,
        ]);
    }

    public function test_admin_cannot_create_bumdes_without_kabupaten(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $roleBumdes = Role::where('kode', 'bumdes')->first();

        $response = $this->actingAs($admin)->post(route('admin.pengguna.store'), [
            'name' => 'BUMDes Tanpa Wilayah',
            'email' => 'bumdes.kosong@sipangan.test',
            'password' => 'Bumdes123456',
            'password_confirmation' => 'Bumdes123456',
            'role_id' => $roleBumdes->id,
            'kabupaten_id' => null,
            'organisasi' => 'BUMDes Kosong',
        ]);

        $response->assertSessionHasErrors('kabupaten_id');
        $this->assertDatabaseMissing('users', ['email' => 'bumdes.kosong@sipangan.test']);
    }

    public function test_admin_can_create_bumdes_with_valid_kabupaten(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $roleBumdes = Role::where('kode', 'bumdes')->first();
        $cilacap = Kabupaten::where('kode', '3301')->first();

        $response = $this->actingAs($admin)->post(route('admin.pengguna.store'), [
            'name' => 'Pengurus BUMDes Cilacap Baru',
            'email' => 'bumdes.cilacap2@sipangan.test',
            'password' => 'Bumdes123456',
            'password_confirmation' => 'Bumdes123456',
            'role_id' => $roleBumdes->id,
            'kabupaten_id' => $cilacap->id,
            'organisasi' => 'BUMDes Cilacap Berkah',
            'telepon' => '089988776655',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'bumdes.cilacap2@sipangan.test',
            'kabupaten_id' => $cilacap->id,
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $distributor = User::where('email', 'distributor2@sipangan.test')->first();

        $response = $this->actingAs($admin)->put(route('admin.pengguna.update', $distributor), [
            'name' => 'Sari Dewi Diperbarui',
            'email' => 'distributor2@sipangan.test',
            'role_id' => $distributor->role_id,
            'organisasi' => 'CV Distribusi Utama Terpadu',
            'telepon' => '081234567999',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('users', [
            'id' => $distributor->id,
            'name' => 'Sari Dewi Diperbarui',
            'organisasi' => 'CV Distribusi Utama Terpadu',
        ]);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();
        $distributor = User::where('email', 'distributor2@sipangan.test')->first();
        $this->assertTrue((bool) $distributor->is_active);

        $response = $this->actingAs($admin)->post(route('admin.pengguna.toggle-aktif', $distributor));

        $response->assertSessionHas('sukses');
        $this->assertFalse((bool) $distributor->fresh()->is_active);

        // Toggle back to active
        $response = $this->actingAs($admin)->post(route('admin.pengguna.toggle-aktif', $distributor));
        $response->assertSessionHas('sukses');
        $this->assertTrue((bool) $distributor->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $response = $this->actingAs($admin)->post(route('admin.pengguna.toggle-aktif', $admin));

        $response->assertSessionHas('galat');
        $this->assertTrue((bool) $admin->fresh()->is_active);
    }
}
