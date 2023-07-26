<?php

namespace Database\Seeders\Admin;

use App\Models\VirtualCardApi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VirtualApiSeeder extends Seeder
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
                'admin_id'          => 1,
                'secret_key'        => "FLWSECK_TEST-SANDBOXDEMOKEY-X",
                'secret_hash'       => "AYxcfvgbhnj@34",
                'url'               => "https://api.flutterwave.com/v3",
                'card_details'         => "This card is property of Monzo Bank, Wonderland. Misuse is criminal offence. If found, please return to Monzo Bank or to the nearest bank with MasterCard logo.",
                
            ],
           
        ];
        VirtualCardApi::insert($data);
    }
}
