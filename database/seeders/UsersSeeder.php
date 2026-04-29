<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('@coordBE123..');

        $users = [
            ['name' => 'Admin', 'lastname' => 'Principal', 'email' => 'adminBE@unet.edu.ve', 'password' => $password, 'card_code' => '67845', 'career' => null, 'is_admin' => 1],
           ];

        User::upsert($users, ['card_code', 'email'], ['name', 'lastname', 'password', 'career', 'is_admin']);
    }
}