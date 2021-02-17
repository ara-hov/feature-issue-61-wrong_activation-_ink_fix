<?php

use Illuminate\Database\Seeder;
use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class, 10)->create();

        User::create([
            'name' => 'Sunny',
            'last_name' => 'Sameer',
            "mobile_number" => "123456789",
            'email' => 'sunny@trisec.io',
            'password' => bcrypt('welcome'),
            "confirmPassword" => "welcome",
            'role_id' => 1
        ],[
            'name' => 'Admin',
            'last_name' => 'User',
            "mobile_number" => "123456789",
            'email' => 'admin@trisec.io',
            'password' => bcrypt('123456'),
            "confirmPassword" => "123456",
            'role_id' => 10
        ]);
    }
}
