<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories')->insert(['name' => 'Еда']);
        DB::table('categories')->insert(['name' => 'Одежда']);
        DB::table('categories')->insert(['name' => 'Развлечения']);
        DB::table('categories')->insert(['name' => 'Спорт']);
        DB::table('categories')->insert(['name' => 'Кино']);
        DB::table('categories')->insert(['name' => 'Обувь']);
        DB::table('categories')->insert(['name' => 'Книги']);
    }
}
