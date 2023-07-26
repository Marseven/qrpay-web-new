<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\SetupSeo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetupSeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $setup_seos = array(
            array('id' => '1','slug' => 'lorem_ipsum','title' => 'QRPAY is simply dummy text of the printing and typesetting industry.','desc' => 'QRPAY  is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.','tags' => '["wallet","Add Money","Transfer Money","Withdraw Money"]','image' => '94aa72f8-7de4-4968-947a-994eeffdd761.webp','last_edit_by' => '1','created_at' => '2023-02-20 05:21:32','updated_at' => '2023-06-11 13:27:53')
          );

        SetupSeo::insert($setup_seos);
    }
}
