<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Category - Danh mục sản phẩm công nghệ
 * Phân hệ nghiệp vụ: Tuyến
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}