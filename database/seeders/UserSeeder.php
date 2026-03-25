<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\BusinessProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'John',
                'email' => 'jobseeker@jobseeker.com',
                'role' => 'jobseeker',
                'password' => Hash::make('12345678'),
               
            ],
            [
                'name' => 'Jane',
                'email' => 'admin@admin.com',
                'role' => 'admin',
                'password' => Hash::make('12345678'),
                
            ],
            [
                'name' => 'Alice',
                'email' => 'company@company.com',
                'role' => 'company',
                'password' => Hash::make('12345678'),
               
            ],
            
        ]);
    
       
    }
    
}
