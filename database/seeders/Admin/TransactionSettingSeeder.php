<?php

namespace Database\Seeders\Admin;

use App\Constants\GlobalConst;
use App\Models\Admin\AgentProfitSetting;
use App\Models\Admin\TransactionSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transaction_settings = array(
            array('id' => '1','admin_id' => '1','slug' => 'transfer','title' => 'Transfer Money Charges','fixed_charge' => '2.00','percent_charge' => '1.00','min_limit' => '10.00','max_limit' => '1000.00','monthly_limit' => '50000.00','daily_limit' => '5000.00','status' => '1','created_at' => NULL,'updated_at' => '2023-03-23 09:30:52'),
            array('id' => '2','admin_id' => '1','slug' => 'bill_pay','title' => 'Bill Pay Charges','fixed_charge' => '2.00','percent_charge' => '1.00','min_limit' => '10.00','max_limit' => '1000.00','monthly_limit' => '20000.00','daily_limit' => '2000.00','status' => '1','created_at' => '2023-03-25 13:11:14','updated_at' => '2023-03-25 07:19:23'),
            array('id' => '3','admin_id' => '1','slug' => 'mobile_topup','title' => 'Mobile Topup Charges','fixed_charge' => '2.00','percent_charge' => '1.00','min_limit' => '15.00','max_limit' => '1000.00','monthly_limit' => '10000.00','daily_limit' => '100000.00','status' => '1','created_at' => '2023-03-27 13:44:29','updated_at' => '2023-03-27 07:46:28'),
            array('id' => '4','admin_id' => '1','slug' => 'virtual_card','title' => 'Virtual Card Charges','fixed_charge' => '2.00','percent_charge' => '1.00','min_limit' => '100.00','max_limit' => '10000.00','monthly_limit' => '0.00','daily_limit' => '0.00','status' => '1','created_at' => '2023-03-30 14:48:54','updated_at' => '2023-06-03 16:26:40'),
            array('id' => '5','admin_id' => '1','slug' => 'remittance','title' => 'Remittance Charge','fixed_charge' => '3.00','percent_charge' => '1.00','min_limit' => '15.00','max_limit' => '15000.00','monthly_limit' => '1000.00','daily_limit' => '1000.00','status' => '1','created_at' => '2023-04-05 16:19:37','updated_at' => '2023-04-11 10:33:24'),
            array('id' => '6','admin_id' => '1','slug' => 'make-payment','title' => 'Make Payment','fixed_charge' => '3.00','percent_charge' => '1.00','min_limit' => '5.00','max_limit' => '100.00','monthly_limit' => '0.00','daily_limit' => '0.00','status' => '1','created_at' => '2023-05-10 11:13:47','updated_at' => '2023-05-10 11:15:21')
          );

          TransactionSetting::insert($transaction_settings);
    }
}
