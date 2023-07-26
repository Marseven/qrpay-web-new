<?php

namespace Database\Seeders;


use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\Admin\AdminHasRoleSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\Admin\AdminSeeder;
use Database\Seeders\Admin\CurrencySeeder;
use Database\Seeders\Admin\SetupKycSeeder;
use Database\Seeders\Admin\SetupSeoSeeder;
use Database\Seeders\Admin\ExtensionSeeder;
use Database\Seeders\Admin\AppSettingsSeeder;
use Database\Seeders\Admin\BankTransfer;
use Database\Seeders\Admin\SiteSectionsSeeder;
use Database\Seeders\Admin\BasicSettingsSeeder;
use Database\Seeders\Admin\BillPayCategorySeeder;
use Database\Seeders\Admin\BlogSedder;
use Database\Seeders\Admin\CashPickup;
use Database\Seeders\Admin\LanguageSeeder;
use Database\Seeders\Admin\OnboardScreenSeeder;
use Database\Seeders\Admin\PaymentGatewaySeeder;
use Database\Seeders\Admin\ReceiverCountry;
use Database\Seeders\Admin\RoleSeeder;
use Database\Seeders\Admin\SetupPageSeeder;
use Database\Seeders\Admin\TopupSeeder;
use Database\Seeders\Admin\TransactionSettingSeeder;
use Database\Seeders\Admin\VirtualApiSeeder;
use Database\Seeders\Fresh\BasicSettingsSeeder as FreshBasicSettingsSeeder;
use Database\Seeders\Merchant\MerchantSeeder;
use Database\Seeders\Merchant\MerchantWalletSeeder;
use Database\Seeders\User\UserSeeder;
use Database\Seeders\User\UserWalletSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // User::factory(50)->create();
        // UserProfile::factory(40)->create();
        //demo
        // $this->call([
        //     AdminSeeder::class,
        //     RoleSeeder::class,
        //     TransactionSettingSeeder::class,
        //     CurrencySeeder::class,
        //     BasicSettingsSeeder::class,
        //     BillPayCategorySeeder::class,
        //     TopupSeeder::class,
        //     LanguageSeeder::class,
        //     PaymentGatewaySeeder::class,
        //     SetupSeoSeeder::class,
        //     AppSettingsSeeder::class,
        //     OnboardScreenSeeder::class,
        //     SiteSectionsSeeder::class,
        //     SetupKycSeeder::class,
        //     ExtensionSeeder::class,
        //     BlogSedder::class,
        //     BankTransfer::class,
        //     CashPickup::class,
        //     ReceiverCountry::class,
        //     AdminHasRoleSeeder::class,
        //     SetupPageSeeder::class,
        //     VirtualApiSeeder::class,
        //     //user
        //     UserSeeder::class,
        //     UserWalletSeeder::class,
        //     //merchant
        //     MerchantSeeder::class,
        //     MerchantWalletSeeder::class,
        // ]);


        $this->call([
            AdminSeeder::class,
            RoleSeeder::class,
            TransactionSettingSeeder::class,
            CurrencySeeder::class,
            FreshBasicSettingsSeeder::class,
            BillPayCategorySeeder::class,
            TopupSeeder::class,
            LanguageSeeder::class,
            PaymentGatewaySeeder::class,
            SetupSeoSeeder::class,
            AppSettingsSeeder::class,
            OnboardScreenSeeder::class,
            SiteSectionsSeeder::class,
            SetupKycSeeder::class,
            ExtensionSeeder::class,
            BlogSedder::class,
            BankTransfer::class,
            CashPickup::class,
            ReceiverCountry::class,
            AdminHasRoleSeeder::class,
            SetupPageSeeder::class,
            VirtualApiSeeder::class,

        ]);
    }
}
