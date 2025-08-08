<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromotionController extends BaseApiController
{
    public function promotions(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Promotion::orderBy('size', 'DESC')
            ->with('category', 'organization', 'images');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('establishments_name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        $promotions = $query->get();

        foreach($promotions as $promotion){
            foreach($promotion->images as $image){
                if(!is_null($image->image)){
                    $image->image = Storage::disk('public')->url($image->image);
                }
                if(!is_null($image->video)){
                    $image->video = Storage::disk('public')->url($image->video);
                }
            }
        }
        return response()->json($promotions);
    }

    public function getPromotionById($id): \Illuminate\Http\JsonResponse
    {
        $promotion = Promotion::with('category', 'organization', 'images')
            ->findOrFail($id);
        foreach($promotion->images as $image){
            if(!is_null($image->image)){
                $image->image = Storage::disk('public')->url($image->image);
            }
            if(!is_null($image->video)){
                $image->video = Storage::disk('public')->url($image->video);
            }
        }
        return response()->json($promotion);
    }

    public function getPromotionImagesById($id): \Illuminate\Http\JsonResponse
    {
        $promotion_images = PromotionImage::where(['promotion_id' => $id])->get();
        return response()->json($promotion_images);
    }
}
