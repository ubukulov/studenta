<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PromotionController extends BaseApiController
{
    public function promotions(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Promotion::whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->with('category', 'organization', 'images');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('establishments_name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('organization', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        // Фильтр по координатам
        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = $request->lat;
            $lng = $request->lng;
            $radius = $request->get('radius');

            // Формула Haversine для MySQL
            $haversine = "(6371000 * acos(cos(radians($lat))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians($lng))
                    + sin(radians($lat))
                    * sin(radians(latitude))))";

            // Добавляем вычисляемое поле distance и фильтруем
            $query->select('*')
                ->selectRaw("$haversine AS distance")
                //->having('distance', '<=', $radius)
                ->orderBy('distance');
        }

        // --- Новый блок фильтров ---
        if ($request->filled('filter')) {
            $filter = $request->filter;

            switch ($filter) {
                case 'popular':
                    $query->orderBy('size', 'DESC');
                    break;

                case 'recommended':
                    $query->where('is_recommended', 'yes')
                        ->orderBy('created_at', 'DESC');
                    break;

                case 'distance':
                    if ($request->filled('lat') && $request->filled('lng')) {
                        $query->having('distance', '<=', 15000);
                        $query->orderBy('distance', 'ASC');
                    }
                    break;

                case 'ratings':
                    // если есть поле rating
                    //$query->orderBy('rating', 'DESC');
                    break;
            }
        }

        $promotions = $query->get();

        foreach($promotions as $promotion) {
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

    public function getPromotionFilters()
    {
        $filters = [
            [
                'id' => 1, 'type' => 'popular', 'name' => 'По Акции', 'icon' => env('APP_URL') . '/files/popular-icon.svg'
            ],
            [
                'id' => 2, 'type' => 'recommended', 'name' => 'Рекомендовано', 'icon' => env('APP_URL') . '/files/recommend-icon.svg'
            ],
            [
                'id' => 3, 'type' => 'distance', 'name' => 'Расстояние', 'icon' => env('APP_URL') . '/files/distance-icon.svg'
            ],
        ];

        return response()->json($filters);
    }
}
