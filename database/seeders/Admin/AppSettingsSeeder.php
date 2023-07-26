<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\AppSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $app_settings = array(
            array('id' => '1','version' => '1.0.0','splash_screen_image' => 'seeder/splash_screen.png','url_title' => 'Our App Url','android_url' => 'https://play.google.com','iso_url' => 'https://www.apple.com/store','created_at' => '2023-02-20 05:21:32','updated_at' => '2023-06-11 12:36:09')
          );


        AppSettings::insert($app_settings);
    }
}
