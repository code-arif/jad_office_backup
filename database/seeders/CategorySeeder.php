<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('job_categories')->insert([
            ['title' => 'Long-Term Job'],
            ['title' => 'Short-Term Job'],
            ['title' => 'Apprenticeship'],
            ['title' => 'Full-Time Job'],
            ['title' => 'Part-Time Job'],
            ['title' => 'Seasonal Job'],
            ['title' => 'Short-Term Gigs'],
            ['title' => 'Other'],
        ]);
    }
}
