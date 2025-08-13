<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'leonard.tomalon@metroretail.ph'],
            [
                'name' => 'Biboy',
                'password' => Hash::make('BIBOY'),
                'role' => 'super admin',
                'user_location' => 'f2', 
            ]
        );

        User::updateOrCreate(
            ['email' =>  'gene.catarina@metroretail.ph'],
            [
                'name' => 'Gene',
                'password' => Hash::make('GENE'),
                'role' => 'super admin',
                'user_location' => 'h8', 
            ]
        );
    }
}
