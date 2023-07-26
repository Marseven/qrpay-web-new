<?php

namespace App\Http\Controllers\User;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;

class MoneyRequestController extends Controller
{
    public function moneyRequest(Request $request)
    {
        $page_title = "Request Moneny";
        $payment_gateways = PaymentGateway::has('currencies')->active()->moneyOut()->get();
        $money_transactions = Transaction::where('type', 'withdraw')->get();
        return view('frontend.pages.user.money_request', compact('page_title','payment_gateways','money_transactions'));
    }

    public function index(Request $request)
    {
        $page_title = "Withdraw Log";
        $money_withdraw_logs = Transaction::where('type', 'withdraw')->where('user_id', auth()->user()->id)->with('user:id,first_name')->paginate(20);
        return view('frontend.pages.user.money_withdraw_logs', compact('money_withdraw_logs','page_title'));
    }
}
