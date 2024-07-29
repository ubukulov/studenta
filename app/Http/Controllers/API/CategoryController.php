<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    public function addCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        Category::create($request->all());

        return response()->json('Категория успешно создано', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $category->update($request->all());

        return response()->json('Категория успешно обновлено', 200, [], JSON_UNESCAPED_UNICODE);
    }
}
