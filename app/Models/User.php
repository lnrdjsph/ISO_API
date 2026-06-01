<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'user_location',
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
    ];

    protected function role(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Allow role switch only for user IDs 1,2,3
                if (in_array($this->id, [1, 2, 3]) && session()->has('switched_role')) {
                    return session('switched_role');
                }
                return $value;
            },
        );
    }

    protected function userLocation(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Allow location switch only for user IDs 1,2,3
                if (in_array($this->id, [1, 2, 3]) && session()->has('switched_location')) {
                    return session('switched_location');
                }
                return $value;
            },
        );
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasPermission(string $permission): bool
    {
        $rolePermissions = [
            'super admin' => ['bulk_edit_products', 'bulk_archive_products', 'view_products'],
            'admin' => ['bulk_edit_products', 'bulk_archive_products', 'view_products'],
            'store manager' => ['bulk_edit_products', 'bulk_archive_products', 'view_products'],
            'user' => ['view_products'],
        ];

        return in_array($permission, $rolePermissions[$this->role] ?? []);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }
}
