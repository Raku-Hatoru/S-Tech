<?php

namespace Database\Factories;

use App\Models\Kabupaten;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::first()?->id ?? 1,
            'kabupaten_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'organisasi' => fake()->company(),
            'telepon' => fake()->phoneNumber(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::where('kode', 'admin')->value('id') ?? 3,
        ]);
    }

    /**
     * Indicate that the user is a BUMDes.
     */
    public function bumdes(?int $kabupatenId = null): static
    {
        return $this->state(fn () => [
            'role_id' => Role::where('kode', 'bumdes')->value('id') ?? 2,
            'kabupaten_id' => $kabupatenId ?? Kabupaten::first()?->id,
        ]);
    }

    /**
     * Indicate that the user is a distributor.
     */
    public function distributor(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::where('kode', 'distributor')->value('id') ?? 1,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
