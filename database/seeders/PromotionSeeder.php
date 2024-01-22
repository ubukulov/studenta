<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Promotion;
use App\Models\PromotionImage;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        foreach(range(1,20) as $index) {
            DB::beginTransaction();
            try {
                $category = Category::inRandomOrder()->first();
                $organization = Category::inRandomOrder()->first();
                $promotion = Promotion::create([
                    'category_id' => $category->id,
                    'organization_id' => $organization->id,
                    'establishments_name' => $faker->company(),
                    'size' => $faker->numberBetween(10, 80),
                    'address' => $faker->address,
                    'description' => $faker->sentence,
                    'seats' => $faker->numberBetween(10,100),
                    'location'=> $faker->userName,
                    'start_date' => $faker->dateTime(),
                    'end_date' => $faker->dateTime(),
                ]);

                foreach(range(1,3) as $i) {
                    PromotionImage::create([
                        'promotion_id' => $promotion->id, 'image' => 'https://picsum.photos/800/600?random=12965'
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
