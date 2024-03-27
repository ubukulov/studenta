<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\Group;
use App\Models\GroupImage;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
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
                $group = Group::inRandomOrder()->first();
                $event = Event::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'name' => $faker->name,
                    'description' => $faker->realText,
                    'address' => $faker->address,
                    'two_gis' => $faker->url,
                    'date' => $faker->date,
                    'start' => $faker->time,
                    'end' => $faker->time,
                    'count_place' => $faker->randomNumber(2, true)
                ]);

                foreach(range(1,3) as $i) {
                    EventImage::create([
                        'event_id' => $event->id, 'path' => 'https://picsum.photos/800/600?random=12965'
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
