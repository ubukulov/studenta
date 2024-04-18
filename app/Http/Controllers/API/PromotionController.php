<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionImage;
use Illuminate\Http\Request;

class PromotionController extends BaseApiController
{
    public function promotions()
    {
        $promotions = Promotion::orderBy('size', 'DESC')
            ->with('category', 'organization', 'images')
            ->get();
        return response()->json($promotions);
    }

    public function getPromotionById($id)
    {
        $promotion = Promotion::with('category', 'organization', 'images')
            ->findOrFail($id);
        return response()->json($promotion);
    }

    public function getPromotionImagesById($id)
    {
        $promotion_images = PromotionImage::where(['promotion_id' => $id])->get();
        return response()->json($promotion_images);
    }
}
