<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends BaseApiController
{
    public function groups()
    {
        $groups = Group::with('user', 'category', 'images', 'events')
            ->get();
        return response()->json($groups);
    }

    public function getGroupById($id)
    {
        $group = Group::with('user', 'category', 'images', 'events')->findOrFail($id);
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
           'category_id' => 'required'
        ]);

        $data = $request->all();
        $data['user_id'] = $this->user->id;

        Group::create($data);

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
}
