<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Services\AiAdvisorService;
use Illuminate\Http\Request;

/**
 * Controller Trợ lý AI Tư vấn Thiết bị Công nghệ (Phụ trách: Nguyễn Minh Tự)
 */
class AiAdvisorController extends Controller
{
    public function index(Request $request, AiAdvisorService $advisor)
    {
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        $recommendations = null;
        if ($request->has('budget')) {
            $recommendations = $advisor->recommend(
                $request->input('category', 'all'),
                (float) $request->input('budget', 35000000),
                $request->input('usage_type', 'all'),
                $request->input('brand')
            );
        }

        return view('ai_advisor.index', compact('categories', 'brands', 'recommendations'));
    }
}
