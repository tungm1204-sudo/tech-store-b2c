<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Product - Sản phẩm công nghệ & Thông số kỹ thuật
 * Phân hệ nghiệp vụ: Tuyến
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'sku',
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'sold_count',
        'warranty_months',
        'cpu',
        'ram',
        'rom',
        'gpu',
        'screen',
        'battery',
        'usage_type',
        'specs_json',
        'thumbnail',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'specs_json' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->sale_price !== null && (float) $this->sale_price > 0 && (float) $this->sale_price < (float) $this->price) {
            return (float) $this->sale_price;
        }
        return (float) $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if ($this->sale_price !== null && (float) $this->sale_price > 0 && (float) $this->sale_price < (float) $this->price) {
            return (int) round((1 - ((float) $this->sale_price / (float) $this->price)) * 100);
        }
        return 0;
    }

    public function getAverageRatingAttribute(): float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round((float) $avg, 1) : 0.0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}