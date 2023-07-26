<?php

namespace Database\Seeders\Merchant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Constants\PaymentGatewayConst;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            // [
            //     'firstname'         => "Test",
            //     'lastname'          => "Merchant",
            //     'email'             => "merchant@appdevs.net",
            //     'username'          => "mdrokon",
            //     'status'            => true,
            //     'password'          => Hash::make("testmerchant"),
            //     'email_verified'    => true,
            //     'sms_verified'      => true,
            //     'created_at'        => now(),
            // ],
            [
                'firstname'         => "Demo",
                'lastname'          => "Merchant",
                'email'             => "mh.ayon222@gmail.com",
                'username'          => "testmerchant",
                'status'            => true,
                'password'          => Hash::make("demomerchant"),
                'email_verified'    => true,
                'sms_verified'      => true,
                'created_at'        => now(),
            ],
        ];

        Merchant::insert($data);

    }
}
