<?php

namespace App\Http\Controllers\User;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $page_title = "Request Moneny";
        return view('frontend.pages.user.invoice', compact('page_title'));
    }

    public function create(Request $request)
    {
        $page_title = "Withdraw Log";
        return view('frontend.pages.user.invoice_create', compact('page_title'));
    }
}
