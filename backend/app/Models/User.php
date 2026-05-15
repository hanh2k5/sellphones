<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'login_attempts', 'locked_until', 'locale',
        'address', 'phone', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['is_locked', 'orders_count', 'total_spent'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Accessor: true nếu tài khoản đang bị khóa
    public function getIsLockedAttribute(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    // Accessor: số đơn hàng
    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    // Accessor: tổng chi tiêu
    public function getTotalSpentAttribute(): float
    {
        return $this->orders()
            ->whereIn('status', ['completed', 'processing', 'shipping'])
            ->sum('total_amount');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function aiChats()
    {
        return $this->hasMany(AiChat::class);
    }

    /**
     * Helper tìm user theo email (Báo cáo 4.2)
     */
    public static function findByEmail($email)
    {
        return self::where('email', $email)->first();
    }

    /**
     * Search & Filter users cho Admin (Best Practice)
     */
    public static function searchUsers($filters)
    {
        $query = self::query();
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%");
            });
        }
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        return $query->orderBy('id', 'desc');
    }
}