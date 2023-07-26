<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::create([
            'firstname'     => "Super",
            'lastname'      => "Admin",
            'username'      => "appdevs",
            'email'         => "admin@appdevs.net",
            'password'      => Hash::make("appdevs"),
            'created_at'    => now(),
            'status'        => true,
        ]);

        Admin::create([
            'firstname'     => "Mehedi ",
            'lastname'      => "Hasan",
            'username'      => "mehedi",
            'email'         => "md.mehedihasaniubat@gmail.com",
            'password'      => Hash::make("mehedi"),
            'created_at'    => now(),
            'status'        => true,
        ]);


        // Admin::factory()->times(200)->create();

    }
}
