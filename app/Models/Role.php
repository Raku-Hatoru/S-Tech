<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'kode',
        'nama',
    ];

    /**
     * Relasi ke pengguna dengan peran ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
