<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Category (Danh mục công nghệ - Phụ trách: Tuyến)
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
