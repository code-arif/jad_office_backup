<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecializeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('specializes')->insert([
            ['type' => 'company', 'name' => 'Demolition and/or Excavation', 'image_url' => 'image-path1.png'],
            ['type' => 'company', 'name' => 'Concrete Contractor', 'image_url' => 'image-path2.png'],
            ['type' => 'company', 'name' => 'Electrical Contractor', 'image_url' => 'image-path3.png'],
            ['type' => 'company', 'name' => 'Plumbing Contractor', 'image_url' => 'image-path4.png'],
            ['type' => 'company', 'name' => 'Roofing Contractor', 'image_url' => 'image-path5.png'],
            ['type' => 'company', 'name' => 'HVAC Contractor', 'image_url' => 'image-path6.png'],
            ['type' => 'employee', 'name' => 'General Laborer', 'image_url' => 'image-path7.png'],
            ['type' => 'employee', 'name' => 'Concrete Finisher', 'image_url' => 'image-path8.png'],
            ['type' => 'employee', 'name' => 'Machine Operator', 'image_url' => 'image-path9.png'],
            ['type' => 'employee', 'name' => 'Truck Driver', 'image_url' => 'image-path10.png'],
            ['type' => 'employee', 'name' => 'Masonry', 'image_url' => 'image-path11.png'],
            ['type' => 'employee', 'name' => 'Plumber', 'image_url' => 'image-path12.png'],
        ]);
    }
}
