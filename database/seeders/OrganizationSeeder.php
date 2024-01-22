<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('organizations')->insert(['name' => 'BAHANDI']);
        DB::table('organizations')->insert(['name' => 'SALAM BRO']);
        DB::table('organizations')->insert(['name' => 'ДОДО ПИЦЦА']);
        DB::table('organizations')->insert(['name' => 'GIPPO']);
        DB::table('organizations')->insert(['name' => 'KFC']);
        DB::table('organizations')->insert(['name' => 'Zheka`s Doner']);
    }
}
