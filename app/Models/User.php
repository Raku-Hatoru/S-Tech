<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'kabupaten_id',
        'name',
        'email',
        'password',
        'organisasi',
        'telepon',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke peran pengguna.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relasi ke kabupaten/kota (khusus akun BUMDes: satu akun = satu kabupaten).
     */
    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }

    /**
     * Pengiriman yang dibuat oleh distributor ini.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'user_id');
    }

    /**
     * Laporan panen bulanan yang diinput oleh BUMDes ini.
     */
    public function harvestReports(): HasMany
    {
        return $this->hasMany(HarvestReport::class, 'user_id');
    }

    /**
     * Riwayat status pengiriman yang dicatat oleh pengguna ini.
     */
    public function shipmentEvents(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class, 'dicatat_oleh');
    }

    /**
     * Helper cek apakah pengguna adalah Admin.
     */
    public function isAdmin(): bool
    {
        return $this->role?->kode === 'admin';
    }

    /**
     * Helper cek apakah pengguna adalah BUMDes.
     */
    public function isBumdes(): bool
    {
        return $this->role?->kode === 'bumdes';
    }

    /**
     * Helper cek apakah pengguna adalah Distributor.
     */
    public function isDistributor(): bool
    {
        return $this->role?->kode === 'distributor';
    }
}
