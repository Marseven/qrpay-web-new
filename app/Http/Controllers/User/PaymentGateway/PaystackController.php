<?php

namespace App\Http\Controllers\User\PaymentGateway;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class PaystackController extends Controller
{
    public function paystack(Request $request)
    {
        dd('paystack form');
    }

    public function paystackVerify(Request $request)
    {
       dump($request->all);
    }

}
