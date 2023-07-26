<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\AppOnboardScreens;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OnboardScreenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $app_onboard_screens = array(
            array('id' => '1','title' => 'Easy, Quick & Secure System for Create Virtual Card','sub_title' => 'QRPay has the most secure system which is very useful for money transactions. Get ready to use unlimited virtual credit card system.','image' => 'e58bde4e-b032-4319-8e0d-f7fe5c85be67.webp','status' => '1','last_edit_by' => '1','created_at' => '2023-05-01 16:33:41','updated_at' => '2023-06-11 12:36:42'),
            array('id' => '2','title' => 'Create Unlimited Virtual Cards for Unlimited Usage','sub_title' => 'Users can easily create virtual credit cards from here. Use anytime anywhere and unlimited. Thanks for start a new journey with b21.','image' => '00c8059a-1b41-4c46-b98b-504df8e8e48a.webp','status' => '1','last_edit_by' => '1','created_at' => '2023-05-01 16:34:33','updated_at' => '2023-06-11 12:36:58'),
            array('id' => '3','title' => 'Create Unlimited Virtual Cards for Unlimited Usage','sub_title' => 'Users can easily create virtual credit cards from here. Use anytime anywhere and unlimited. Thanks for start a new journey with b21.','image' => '46c19bcc-5986-4cb5-ab03-3fe4ffb07a05.webp','status' => '1','last_edit_by' => '1','created_at' => '2023-06-11 12:37:09','updated_at' => '2023-06-11 12:37:18')
          );
        AppOnboardScreens::insert($app_onboard_screens);
    }
}
