<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Constants\GlobalConst;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Admin\BasicSettings;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Api\Helpers;
use Exception;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function slugValue($slug) {
        $values =  [
            'add-money'         => PaymentGatewayConst::TYPEADDMONEY,
            'money-out'         => PaymentGatewayConst::TYPEMONEYOUT,
            'transfer-money'    => PaymentGatewayConst::TYPETRANSFERMONEY,
            'money-exchange'    => PaymentGatewayConst::TYPEMONEYEXCHANGE,
            'bill-pay'    => PaymentGatewayConst::BILLPAY,
            'mobile-topup'    => PaymentGatewayConst::MOBILETOPUP,
            'virtual-card'    => PaymentGatewayConst::VIRTUALCARD,
            'remittance'    => PaymentGatewayConst::SENDREMITTANCE,
        ];

        if(!array_key_exists($slug,$values)) return abort(404);
        return $values[$slug];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($slug = null) {
        if($slug != null){
            $page_title = ucwords(remove_speacial_char($slug," ")) . " Log";
            $transactions = Transaction::merchantAuth()->where("type",$this->slugValue($slug))->orderByDesc("id")->get()->map(function($item){
                $statusInfo = [
                    "success" =>      1,
                    "pending" =>      2,
                    "rejected" =>     3,
                ];
               if($item->type == payment_gateway_const()::TYPEMONEYOUT){
                    return[
                        'id' => $item->id,
                        'trx' => $item->trx_id,
                        'gateway_name' => $item->currency->gateway->name,
                        'gateway_currency_name' => $item->currency->name,
                        'transaction_type' => $item->type,
                        'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                        'payable' => getAmount($item->payable,2).' '.$item->merchant_wallet->currency->code,
                        'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->currency->rate,2).' '.$item->currency->currency_code,
                        'total_charge' => getAmount($item->charge->total_charge,2).' '.$item->merchant_wallet->currency->code,
                        'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                        'status' => $item->stringStatus->value ,
                        'date_time' => $item->created_at ,
                        'status_info' =>(object)$statusInfo ,
                        'rejection_reason' =>$item->reject_reason??"" ,

                    ];

                }elseif($item->type == payment_gateway_const()::TYPEMAKEPAYMENT){

                    if($item->attribute == payment_gateway_const()::RECEIVED){
                        return[
                            'id' => @$item->id,
                            'type' =>$item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Received Money from @" .@$item->details->sender->fullname." (".@$item->details->sender->full_mobile.")",
                            'recipient_received' => getAmount(@$item->request_amount,2).' '.get_default_currency_code(),
                            'current_balance' => getAmount(@$item->available_balance,2).' '.get_default_currency_code(),
                            'status' => @$item->stringStatus->value ,
                            'date_time' => @$item->created_at ,
                            'status_info' =>(object)@$statusInfo ,
                        ];

                    }

            }elseif($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE){
                return[
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                    'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                    'receive_amount' => getAmount($item->payable,2).' '.get_default_currency_code(),
                    'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->creator_wallet->currency->rate,2).' '.$item->creator_wallet->currency->code,
                    'total_charge' => getAmount($item->charge->total_charge,2).' '.get_default_currency_code(),
                    'remark' => $item->remark,
                    'status' => $item->stringStatus->value ,
                    'date_time' => $item->created_at ,
                    'status_info' =>(object)$statusInfo ,

                ];

            }
            });

        }else {
            $page_title = "Transaction Log";
            $transactions = Transaction::merchantAuth()->orderByDesc("id")->get()->map(function($item){
                $statusInfo = [
                    "success" =>      1,
                    "pending" =>      2,
                    "rejected" =>     3,
                ];
                if($item->type == payment_gateway_const()::TYPEMONEYOUT){
                    return[
                        'id' => $item->id,
                        'trx' => $item->trx_id,
                        'gateway_name' => $item->currency->gateway->name,
                        'gateway_currency_name' => $item->currency->name,
                        'transaction_type' => $item->type,
                        'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                        'payable' => getAmount($item->payable,2).' '.$item->merchant_wallet->currency->code,
                        'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->currency->rate,2).' '.$item->currency->currency_code,
                        'total_charge' => getAmount($item->charge->total_charge,2).' '.$item->merchant_wallet->currency->code,
                        'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                        'status' => $item->stringStatus->value ,
                        'date_time' => $item->created_at ,
                        'status_info' =>(object)$statusInfo ,
                        'rejection_reason' =>$item->reject_reason??"" ,

                    ];

                }elseif($item->type == payment_gateway_const()::TYPEMAKEPAYMENT){

                    if($item->attribute == payment_gateway_const()::RECEIVED){
                        return[
                            'id' => @$item->id,
                            'type' =>$item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Received Money from @" .@$item->details->sender->fullname." (".@$item->details->sender->full_mobile.")",
                            'recipient_received' => getAmount(@$item->request_amount,2).' '.get_default_currency_code(),
                            'current_balance' => getAmount(@$item->available_balance,2).' '.get_default_currency_code(),
                            'status' => @$item->stringStatus->value ,
                            'date_time' => @$item->created_at ,
                            'status_info' =>(object)@$statusInfo ,
                        ];

                    }

            }elseif($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE){
                return[
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                    'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                    'receive_amount' => getAmount($item->payable,2).' '.get_default_currency_code(),
                    'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->creator_wallet->currency->rate,2).' '.$item->creator_wallet->currency->code,
                    'total_charge' => getAmount($item->charge->total_charge,2).' '.get_default_currency_code(),
                    'remark' => $item->remark,
                    'status' => $item->stringStatus->value ,
                    'date_time' => $item->created_at ,
                    'status_info' =>(object)$statusInfo ,

                ];

            }

            });
        }

        $data =[
            'page_title' => $page_title,
            'transactions'=> $transactions,
            ];
            $message =  ['success'=>['All Transactions']];
            return Helpers::success($data,$message);
    }


    public function search(Request $request) {
        $validator = Validator::make(request()->all(), [
            'text'  => 'required|string',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validate();

        try{
            $transactions = Transaction::merchantAuth()->search($validated['text'])->take(10)->get()->map(function($item){
                $statusInfo = [
                    "success" =>      1,
                    "pending" =>      2,
                    "rejected" =>     3,
                ];
                if($item->type == payment_gateway_const()::TYPEMONEYOUT){
                    return[
                        'id' => $item->id,
                        'trx' => $item->trx_id,
                        'gateway_name' => $item->currency->gateway->name,
                        'gateway_currency_name' => $item->currency->name,
                        'transaction_type' => $item->type,
                        'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                        'payable' => getAmount($item->payable,2).' '.$item->merchant_wallet->currency->code,
                        'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->currency->rate,2).' '.$item->currency->currency_code,
                        'total_charge' => getAmount($item->charge->total_charge,2).' '.$item->merchant_wallet->currency->code,
                        'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                        'status' => $item->stringStatus->value ,
                        'date_time' => $item->created_at ,
                        'status_info' =>(object)$statusInfo ,
                        'rejection_reason' =>$item->reject_reason??"" ,

                    ];

                }elseif($item->type == payment_gateway_const()::TYPEMAKEPAYMENT){

                    if($item->attribute == payment_gateway_const()::SEND){
                        return[
                            'id' => @$item->id,
                            'type' =>$item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Make Payment to @" . @$item->details->receiver->fullname." (".@$item->details->receiver->full_mobile.")",
                            'request_amount' => getAmount(@$item->request_amount,2).' '.get_default_currency_code() ,
                            'total_charge' => getAmount(@$item->charge->total_charge,2).' '.get_default_currency_code(),
                            'payable' => getAmount(@$item->payable,2).' '.get_default_currency_code(),
                            'recipient_received' => getAmount(@$item->details->recipient_amount,2).' '.get_default_currency_code(),
                            'current_balance' => getAmount(@$item->available_balance,2).' '.get_default_currency_code(),
                            'status' => @$item->stringStatus->value ,
                            'date_time' => @$item->created_at ,
                            'status_info' =>(object)@$statusInfo ,
                        ];
                    }elseif($item->attribute == payment_gateway_const()::RECEIVED){
                        return[
                            'id' => @$item->id,
                            'type' =>$item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Received Money from @" .@$item->details->sender->fullname." (".@$item->details->sender->full_mobile.")",
                            'recipient_received' => getAmount(@$item->request_amount,2).' '.get_default_currency_code(),
                            'current_balance' => getAmount(@$item->available_balance,2).' '.get_default_currency_code(),
                            'status' => @$item->stringStatus->value ,
                            'date_time' => @$item->created_at ,
                            'status_info' =>(object)@$statusInfo ,
                        ];

                    }

            }elseif($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE){
                return[
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                    'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                    'receive_amount' => getAmount($item->payable,2).' '.get_default_currency_code(),
                    'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->creator_wallet->currency->rate,2).' '.$item->creator_wallet->currency->code,
                    'total_charge' => getAmount($item->charge->total_charge,2).' '.get_default_currency_code(),
                    'remark' => $item->remark,
                    'status' => $item->stringStatus->value ,
                    'date_time' => $item->created_at ,
                    'status_info' =>(object)$statusInfo ,

                ];

            }

            });
        }catch(Exception $e){
            $error = ['error' => ['Something went worng!. Please try again.']];
            return Helpers::error($error);
        }

        $data =[
            'transactions'=> $transactions,
            ];
        $message =  ['success'=>['Search Transactions']];
        return Helpers::success($data,$message);
    }
}
