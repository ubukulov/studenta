<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupImage;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        foreach(range(1,10) as $index) {
            DB::beginTransaction();
            try {
                $user = User::inRandomOrder()->first();
                $category = Category::inRandomOrder()->first();
                $group = Group::create([
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                    'name' => $faker->name,
                    'description' => $faker->realText,
                    'instagram' => $faker->url,
                    'whatsapp' => $faker->url,
                    'telegram' => $faker->url,
                ]);

                foreach(range(1,3) as $i) {
                    GroupImage::create([
                        'group_id' => $group->id, 'path' => 'https://picsum.photos/800/600?random=12965'
                    ]);
                }
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
                echo $exception->getMessage();
            }
        }
    }
}
