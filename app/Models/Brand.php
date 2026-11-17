<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Brand (Thương hiệu thiết bị - Phụ trách: Tuyến)
 */
class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'country', 'logo_url', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
