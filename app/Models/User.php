<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
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
        'must_reset_password',
        'self_service_invited_at',
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
        'is_active' => 'boolean',
        'must_reset_password' => 'boolean',
        'self_service_invited_at' => 'datetime',
    ];

    public function personDidDelete(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by', 'id');
    }

    /**
     * İstifadəçinin əməkdaş kartı — YALNIZ admin tərəfindən yaradılmış açıq bağ
     * (`user_personnel_links`) üzərindən. E-poçt uyğunluğu eyniləşdirmə deyil.
     * İcazə qərarlarında bunun əvəzinə `UserPersonnelLinkResolver::resolve()` işlət —
     * o, işdən çıxmış əməkdaşın bağını da nəzərə almır.
     *
     * @return HasOneThrough<Personnel, UserPersonnelLink, $this>
     */
    public function personnel(): HasOneThrough
    {
        return $this->hasOneThrough(
            Personnel::class,
            UserPersonnelLink::class,
            'user_id',
            'id',
            'id',
            'personnel_id',
        );
    }

    /** @return HasOne<UserPersonnelLink, $this> */
    public function personnelLink(): HasOne
    {
        return $this->hasOne(UserPersonnelLink::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function (self $model): void {
            $model->deleted_by = auth()->id();
            $model->is_active = false;
            $model->saveQuietly();
        });
    }
}
