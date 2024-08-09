<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends BaseApiController
{
    public function groups(): \Illuminate\Http\JsonResponse
    {
        $groups = Group::with('user', 'images', 'events')
            ->select([
                'groups.*',
                DB::raw('(COUNT(*)) as subscribes')
            ])
            ->leftJoin('group_participants', 'group_participants.group_id', '=', 'groups.id')
            ->groupBy('groups.id')
            ->orderBy('subscribes', 'DESC')
            ->get();

        foreach($groups as $group) {
            if(GroupParticipant::userSubscribed($group->id, $this->user->id)) {
                $group['subscribe'] = true;
            } else {
                $group['subscribe'] = false;
            }
            $user = $group->user;
            $user_profile = $user->profile;
            if($user_profile) {
                $university = $user_profile->university;
                if($university) $group['user']['university'] = $university->name ?? null;
                $image_upload = ImageUpload::find($user_profile->avatar);
                if($image_upload) $group['user']['avatar'] = "http://194.4.56.241:8877" . $image_upload->image ?? null;
            } else {
                $group['user']['university'] = null;
                $group['user']['avatar'] = null;
            }
            unset($group['user']['profile']);
        }

        return response()->json($groups);
    }

    public function getGroupById($id)
    {
        $group = Group::with('user', 'category', 'images', 'events')->findOrFail($id);
        $group['subscribe'] = (Group::isSubscribe($this->user->id, $id)) ? true : false;
        return response()->json($group);
    }

    public function store(Request $request)
    {
        $groups = Group::where(['user_id' => $this->user->id])
            ->get();
        if(count($groups) >= 3) {
            return response()->json('Максимальное количество групп 3', 400);
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
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        if($group->user_id != $this->user->id) {
            return response()->json('Это группа не ваша.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        $request->validate([
            'name' => 'required',
            'description' => 'required|min:100',
            'category_id' => 'required'
        ]);

        $group->update($request->all());

        return response()->json('Группа успешно обновлен', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function delete($id)
    {
        $group = Group::findOrFail($id);
        if(!$group) {
            return response()->json('Группа уже удалено', 400, [], JSON_UNESCAPED_UNICODE);
        }

        Group::destroy($id);

        return response()->json('Группа удалено успешно', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'group_id' => 'required',
        ]);

        $group_id = $request->input('group_id');

        if(Group::isSubscribe($this->user->id, $group_id)) {
            return response()->json('Вы уже подписаны на эту группу', 400, [], JSON_UNESCAPED_UNICODE);
        } else {
            Group::subscribe($this->user->id, $group_id);
            return response()->json('Вы успешно подписаны на группу', 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function unsubscribe(Request $request)
    {
        $request->validate([
            'group_id' => 'required',
        ]);

        $group_id = $request->input('group_id');

        if($group_participant = Group::isSubscribe($this->user->id, $group_id)) {
            Group::unSubscribe($group_participant);
            return response()->json('Вы успешно отписались от группы', 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json('Вы не подписаны на группу чтобы отписаться', 400, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
