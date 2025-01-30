<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function personDidDelete(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by', 'id');
    }

    public function structures(): HasManyThrough
    {
        return $this->hasManyThrough(
            Structure::class,       // Nihai model
            RoleStructure::class,      // Pivot tablo
            'role_id',              // Pivot tablodaki role_id
            'id',
            'id',
            'structure_id'// Pivot tablodaki structure_id
        );
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            $model->deleted_by = auth()->user()->id;
            $model->is_active = false;
            $model->save();
        });
    }
}
