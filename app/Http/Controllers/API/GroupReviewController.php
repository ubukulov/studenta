<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GroupReview;
use Illuminate\Http\Request;

class GroupReviewController extends BaseApiController
{
    public function getGroupReviews($groupId): \Illuminate\Http\JsonResponse
    {
        $groupReviews = GroupReview::where([
            'group_id' => $groupId, //'user_id' => $this->user->id
        ])
            ->with('user.profileWithAvatar.avatarImage', 'group')
            ->orderBy('created_at', 'desc')
            ->get();

        $groupReviews->transform(function ($review) {
            $avatarImage = optional($review->user->profileWithAvatar->avatarImage)->image;
            $review->user->avatar = $avatarImage;

            // Удалим ненужную вложенность, если хочешь:
            unset($review->user->profile_with_avatar);

            return $review;
        });

        return response()->json($groupReviews);
    }

    public function groupReviewStore(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'group_id' => 'required',
                'comment' => 'required',
                'rating' => 'required|numeric|min:0|max:5',
            ]);

            $data = $request->all();
            $data['user_id'] = $this->user->id;
            GroupReview::create($data);

            return response()->json('Отзыв успешно сохранен', 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422);
        }
    }

    public function groupReviewUpdate(Request $request, $groupReviewId): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'group_id' => 'required',
                'comment' => 'required',
                'rating' => 'required|numeric|min:0|max:5',
            ]);

            $groupReview = GroupReview::findOrFail($groupReviewId);

            if($groupReview->user_id !== $this->user->id){
                return response()->json('Отзыв не ваш', 409, [], JSON_UNESCAPED_UNICODE);
            }

            $data = $request->all();
            $groupReview->update($data);

            return response()->json('Отзыв успешно обновлен', 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422);
        }
    }

    public function groupReviewDelete($groupReviewId): \Illuminate\Http\JsonResponse
    {
        $groupReview = GroupReview::findOrFail($groupReviewId);
        if($groupReview->user_id !== $this->user->id){
            return response()->json('Отзыв не ваш', 409, [], JSON_UNESCAPED_UNICODE);
        }

        if(!$groupReview) {
            return response()->json('Отзыв уже удалено', 404, [], JSON_UNESCAPED_UNICODE);
        }

        $groupReview->delete();

        return response()->json('Отзыв успешно удален', 200, [], JSON_UNESCAPED_UNICODE);
    }
}
