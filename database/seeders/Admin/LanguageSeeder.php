<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name'        => "English",
                'code'       => "en",
                'status'       => 1,
                'last_edit_by'       => 1,

            ],
            [
                'name'        => "Spanish",
                'code'       => "es",
                'status'       => 0,
                'last_edit_by'       => 1,

            ],
        ];


        Language::insert($data);
    }
}
