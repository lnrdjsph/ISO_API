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
                'user_location' => '4002', 
            ]
        );

        User::updateOrCreate(
            ['email' =>  'gene.catarina@metroretail.ph'],
            [
                'name' => 'Gene',
                'password' => Hash::make('GENE'),
                'role' => 'super admin',
                'user_location' => '6012', 
            ]
        );
        User::updateOrCreate(
            ['email' =>  'akehide.tecson@metroretail.ph'],
            [
                'name' => 'Akehide',
                'password' => Hash::make('AKEHIDE'),
                'role' => 'super admin',
                'user_location' => '4002', 
            ]
        );
        User::updateOrCreate(
            ['email' =>  'test.storepersonnel@metroretail.ph'],
            [
                'name' => 'Store Personnel',
                'password' => Hash::make('test1'),
                'role' => 'store personnel',
                'user_location' => '4002', 
            ]
        );
        User::updateOrCreate(
            ['email' =>  'test.warehouseadmin@metroretail.ph'],
            [
                'name' => 'Warehouse Admin',
                'password' => Hash::make('test2'),
                'role' => 'warehouse admin',
                'user_location' => 'h8', 
            ]
        );
        User::updateOrCreate(
            ['email' =>  'test.warehousepersonnel@metroretail.ph'],
            [
                'name' => 'Warehouse Personnel',
                'password' => Hash::make('test2'),
                'role' => 'warehouse personnel',
                'user_location' => 'h8', 
            ]
        );
        User::updateOrCreate(
            ['email' =>  'test.manager@metroretail.ph'],
            [
                'name' => 'Manager',
                'password' => Hash::make('test2'),
                'role' => 'manager',
                'user_location' => 'h8', 
            ]
        );
    }
}
