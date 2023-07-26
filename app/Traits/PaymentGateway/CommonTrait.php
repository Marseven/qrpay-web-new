<?php

namespace App\Traits\PaymentGateway;

use Illuminate\Support\Facades\DB;
use App\Models\Admin\PaymentGateway;
use Illuminate\Http\Request;


trait CommonTrait
{
    public static function basicData(Request $request)
    {
        $data = $request->all();
        $data['pmntGateway'] = DB::table('payment_gateways')->where('code', $data['payment_gateway'])->select('code', 'name', 'id','alias', 'supported_currencies')->first();
        $add_money_charges = DB::table('payment_gateway_currencies')->where('payment_gateway_id', '=', $data['pmntGateway']->id)->where('currency_code', '=', $data['currency_code'])->first();
        $data['calcCharge'] = addMoneyChargeCalc($data['amount'], $add_money_charges);
        return  $data;
    }
}
