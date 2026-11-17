<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model User - Người dùng & Phân quyền truy cập RBAC
 * Phân hệ nghiệp vụ: Điền
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isStaffOrAdmin(): bool
    {
        return in_array($this->role, ['staff', 'admin'], true);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class)->orderByDesc('is_default')->latest();
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function warrantyRequests()
    {
        return $this->hasMany(WarrantyRequest::class)->latest();
    }

    public function createdReceipts()
    {
        return $this->hasMany(StockReceipt::class, 'created_by');
    }
}