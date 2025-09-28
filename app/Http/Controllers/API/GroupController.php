<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class GroupController extends BaseApiController
{
    public function groups(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Group::with('user', 'image', 'events', 'categories')
            ->select([
                'groups.*',
                DB::raw('(COUNT(*)) as subscribes')
            ])
            ->leftJoin('group_participants', 'group_participants.group_id', '=', 'groups.id')
            ->groupBy('groups.id')
            ->orderBy('subscribes', 'DESC');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        $groups = $query->get();

        foreach($groups as $group) {
            if(GroupParticipant::userSubscribed($group->id, $this->user->id)) {
                $group['subscribe'] = true;
            } else {
                $group['subscribe'] = false;
            }
            if($group->type !== 'admin') {
                $user = $group->user;
                $user_profile = $user->profile;
                $image = ImageUpload::find($user_profile->avatar);
                $user->avatar = $image->image ?? null;
                $group->setRelation('user', $user);
                if($user_profile) {
                    $university = $user_profile->university;
                    $group['user']['university'] = $university->name ?? null;
                } else {
                    $group['user']['university'] = null;
                }

                unset($group['user']['profile']);
            }

        }

        return response()->json($groups);
    }

    public function getGroupById($id)
    {
        $group = Group::with('user', 'categories', 'image', 'events')
            ->findOrFail($id);
        $groupOwner = User::findOrFail($group->user_id);
        $user_profile = $groupOwner->profile;
        $university = $user_profile->university;
        $user_profile['university_name'] = $university->name;
        $image = ImageUpload::find($user_profile->avatar);
        $group['subscribe'] = (Group::isSubscribe($this->user->id, $id)) ? true : false;
        $group['subscribes'] = $group->subscribes()->count();
        $user_profile->avatar = $image->image ?? null;
        $group->setRelation('user', $groupOwner);
        return response()->json($group);
    }

    public function store(Request $request)
    {
        try {
            $groups = Group::where(['user_id' => $this->user->id])
                ->get();
            if(count($groups) >= 3) {
                return response()->json('Максимальное количество групп 3', 409);
            }

            $request->validate([
                'name' => 'required',
                'description' => 'required|min:100',
                'categories' => 'required|array|min:1',
                'categories.*' => 'exists:categories,id',
            ]);

            $data = $request->all();
            $data['user_id'] = $this->user->id;

            $group = Group::create($data);

            foreach($data['categories'] as $category) {
                $group->categories()->attach($category);
            }

            return response()->json('Группа успешно создан', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $group = Group::findOrFail($id);

            if($group->user_id != $this->user->id) {
                return response()->json('Это группа не ваша.', 409, [], JSON_UNESCAPED_UNICODE);
            }

            $request->validate([
                'name' => 'required',
                'description' => 'required|min:100',
                'categories' => 'required|array|min:1',
                'categories.*' => 'exists:categories,id',
            ]);

            $group->update($request->all());

            $group->categories()->detach();

            foreach($request['categories'] as $category) {
                $group->categories()->attach($category);
            }

            return response()->json('Группа успешно обновлен', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422);
        }
    }

    public function delete($id)
    {
        $group = Group::find($id);
        if(!$group) {
            return response()->json('Группа уже удалено', 409, [], JSON_UNESCAPED_UNICODE);
        }

        Group::destroy($id);

        return response()->json('Группа удалено успешно', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function subscribe(Request $request)
    {
        try {
            $request->validate([
                'group_id' => 'required',
            ]);

            $group_id = $request->input('group_id');

            if(Group::isSubscribe($this->user->id, $group_id)) {
                return response()->json('Вы уже подписаны на эту группу', 409, [], JSON_UNESCAPED_UNICODE);
            } else {
                Group::subscribe($this->user->id, $group_id);
                return response()->json('Вы успешно подписаны на группу', 200, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422);
        }
    }

    public function unsubscribe(Request $request)
    {
        try {
            $request->validate([
                'group_id' => 'required',
            ]);

            $group_id = $request->input('group_id');

            $group = Group::findOrFail($group_id);

            if($group->type == 'admin') response()->json('Вы не можете отписаться от этой группы', 403, [], JSON_UNESCAPED_UNICODE);

            if($group_participant = Group::isSubscribe($this->user->id, $group_id)) {
                Group::unSubscribe($group_participant);
                return response()->json('Вы успешно отписались от группы', 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json('Вы не подписаны на группу чтобы отписаться', 409, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422);
        }
    }
}
