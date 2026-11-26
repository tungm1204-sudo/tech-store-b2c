<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Service Trợ lý AI Tư vấn chọn Thiết bị Công nghệ theo Ngân sách & Nhu cầu (Phụ trách: Nguyễn Minh Tự)
 */
class AiAdvisorService
{
    public function recommend(?string $categorySlug, float $maxBudget, string $usageNeed, ?string $preferredBrandSlug = null): Collection
    {
        $query = Product::with(['category', 'brand'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0);

        if ($categorySlug && $categorySlug !== 'all') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        // Cho phép biên độ +10% để tư vấn thêm lựa chọn nâng cấp nhẹ nếu vượt chút ngân sách
        $ceiling = $maxBudget > 0 ? $maxBudget * 1.10 : 999999999;
        $candidates = $query->get()->filter(fn (Product $p) => $p->effective_price <= $ceiling);

        return $candidates->map(function (Product $p) use ($maxBudget, $usageNeed, $preferredBrandSlug) {
            $score = 50;
            $reasons = [];

            // 1. Phù hợp nhu cầu (Gaming / Văn phòng / Đồ họa)
            if ($usageNeed === 'all' || $p->usage_type === $usageNeed) {
                $score += 25;
                $reasons[] = "Tối ưu phần cứng cho nhu cầu " . $this->usageLabel($usageNeed);
            }

            // 2. Cân bằng hiệu năng / ngân sách
            $effPrice = $p->effective_price;
            if ($maxBudget > 0) {
                if ($effPrice <= $maxBudget && $effPrice >= $maxBudget * 0.65) {
                    $score += 15;
                    $reasons[] = "Tối ưu hiệu năng cao nhất trong tầm ngân sách";
                } elseif ($effPrice < $maxBudget * 0.65) {
                    $score += 10;
                    $reasons[] = "Tiết kiệm đáng kể chi phí so với dự kiến";
                } else {
                    $score += 4;
                    $reasons[] = "Cấu hình nhỉnh hơn ngân sách nhẹ (+<=10%), đáng cân nhắc nâng cấp";
                }
            }

            // 3. Đánh giá tốt & Thương hiệu ưu tiên & Khuyến mãi
            if ($preferredBrandSlug && $p->brand && $p->brand->slug === $preferredBrandSlug) {
                $score += 5;
                $reasons[] = "Đúng thương hiệu yêu thích ({$p->brand->name})";
            }
            if ($p->discount_percent > 0) {
                $score += 5;
                $reasons[] = "Đang giảm giá {$p->discount_percent}% tại TechStore";
            }
            $avgRating = (float) ($p->approved_reviews_avg_rating ?? 0);
            if ($avgRating >= 4.5) {
                $score += 5;
                $reasons[] = "Được cộng đồng đánh giá cao ({$avgRating}/5★)";
            }

            $p->ai_match_score = min(100, $score);
            $p->ai_reasons = $reasons;
            return $p;
        })
        ->sortByDesc('ai_match_score')
        ->values()
        ->take(6);
    }

    private function usageLabel(string $usage): string
    {
        return match ($usage) {
            'gaming' => 'Gaming & Esports',
            'do-hoa' => 'Đồ họa / Dựng phim / Lập trình nặng',
            'van-phong' => 'Văn phòng & Học tập di động',
            default => 'Đa nhiệm tổng hợp',
        };
    }
}
