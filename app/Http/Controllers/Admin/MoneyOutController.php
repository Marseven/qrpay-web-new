<?php

namespace App\Http\Controllers\Admin;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\AgentNotification;
use App\Models\AgentWallet;
use App\Models\Merchants\MerchantNotification;
use App\Models\Merchants\MerchantWallet;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\User\Withdraw\ApprovedByAdminMail;
use App\Notifications\User\Withdraw\RejectedByAdminMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MoneyOutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = "All Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
        )->where('type', PaymentGatewayConst::TYPEMONEYOUT)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title','transactions'
        ));
    }

    /**
     * Display All Pending Logs
     * @return view
     */
    public function pending() {
        $page_title = "Pending Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
         )->where('type', PaymentGatewayConst::TYPEMONEYOUT)->where('status', 2)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title','transactions'
        ));
    }


    /**
     * Display All Complete Logs
     * @return view
     */
    public function complete() {
        $page_title = "Complete Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
         )->where('type', PaymentGatewayConst::TYPEMONEYOUT)->where('status', 1)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title','transactions'
        ));
    }


    /**
     * Display All Canceled Logs
     * @return view
     */
    public function canceled() {
        $page_title = "Canceled Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
         )->where('type', PaymentGatewayConst::TYPEMONEYOUT)->where('status',4)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title','transactions'
        ));
    }
    public function moneyOutDetails($id){

        $data = Transaction::where('id',$id)->with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
        )->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        $page_title = "Money out details for".'  '.$data->trx_id;
        return view('admin.sections.money-out.details', compact(
            'page_title',
            'data'
        ));
    }
    public function approved(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id',$request->id)->where('status',2)->where('type', PaymentGatewayConst::TYPEMONEYOUT)->first();
        $up['status'] = 1;
        try{
           $approved = $data->fill($up)->save();
           if( $approved){

            $moneyOutData= [
                'trx_id' => $data->trx_id??'',
                'gateway_name' => $data->currency->gateway->name??'',
                'gateway_type' => $data->currency->gateway->type??'',
                'amount' => $data->request_amount??0,
                'gateway_rate' => $data->currency->rate??'',
                'gateway_currency' => $data->currency->currency_code??'',
                'gateway_charge' => $data->charge->total_charge??0,
                'will_get' =>$data->payable??0,
                'payable' =>$data->request_amount??0,
            ];

            $notification_content = [
                'title'         => "Money Out",
                'message'       => "Your Money Out request approved by admin " .getAmount($data->request_amount,2).' '.get_default_currency_code()." successful.",
                'image'         => files_asset_path('profile-default'),
            ];
            if($data->user_id != null) {
                $user =$data->user;
                $user->notify(new ApprovedByAdminMail($user,(object)$moneyOutData));
                UserNotification::create([
                    'type'      => NotificationConst::MONEY_OUT,
                    'user_id'  =>  $data->user_id,
                    'message'   => $notification_content,
                ]);
                DB::commit();
            }else if($data->merchant_id != null) {
                $user =$data->merchant;
                $user->notify(new ApprovedByAdminMail($user,(object)$moneyOutData));
                MerchantNotification::create([
                    'type'      => NotificationConst::MONEY_OUT,
                    'merchant_id'  =>  $data->merchant_id,
                    'message'   => $notification_content,
                ]);
                DB::commit();
            }


           }

            return redirect()->back()->with(['success' => ['Mouney Out request approved successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    public function rejected(Request $request){

        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'reject_reason' => 'required|string|max:200',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id',$request->id)->where('status',2)->where('type', PaymentGatewayConst::TYPEMONEYOUT)->first();
        $up['status'] = 4;
        $up['reject_reason'] = $request->reject_reason;
        try{
            $rejected =  $data->fill($up)->save();
            if( $rejected){
                $moneyOutData= [
                    'trx_id' => $data->trx_id??'',
                    'gateway_name' => $data->currency->gateway->name??'',
                    'gateway_type' => $data->currency->gateway->type??'',
                    'amount' => $data->request_amount??0,
                    'gateway_rate' => $data->currency->rate??'',
                    'gateway_currency' => $data->currency->currency_code??'',
                    'gateway_charge' => $data->charge->total_charge??0,
                    'will_get' =>$data->payable??0,
                    'payable' =>$data->request_amount??0,
                    'reason' =>$request->reject_reason??'',
                ];
                //base_cur_charge
                $baseCurrency = Currency::default();
                $gate =PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
                    $gateway->where('slug', PaymentGatewayConst::money_out_slug());
                    $gateway->where('status', 1);
                })->where('id',$data->payment_gateway_currency_id)->first();

                $baseFixedCharge = $gate->fixed_charge *  $baseCurrency->rate;
                $basePercent_charge = ($data->request_amount / 100) * $gate->percent_charge;
                $base_total_charge = $baseFixedCharge + $basePercent_charge;
                $returnAmount = $data->request_amount + $base_total_charge;

                if($data->user_id != null) {
                    $userWallet = UserWallet::where('user_id',$data->user_id)->first();
                    $userWallet->balance +=  $returnAmount;
                    $userWallet->save();
                }else if($data->merchant_id != null) {
                    $userWallet = MerchantWallet::where('merchant_id',$data->merchant_id)->first();
                    $userWallet->balance +=  $returnAmount;
                    $userWallet->save();
                }

            //notification
            $notification_content = [
                'title'         => "Money Out",
                'message'       => "Your Money Out request rejected by admin " .getAmount($data->request_amount,2).' '.get_default_currency_code(),
                'image'         => files_asset_path('profile-default'),
            ];

            if($data->user_id != null) {
                $user =$data->user;
                $user->notify(new RejectedByAdminMail($user,(object)$moneyOutData));
                UserNotification::create([
                    'type'      => NotificationConst::MONEY_OUT,
                    'user_id'  =>  $data->user_id,
                    'message'   => $notification_content,
                ]);
                DB::commit();
            }else if($data->merchant_id != null) {
                $user =$data->merchant;
                $user->notify(new RejectedByAdminMail($user,(object)$moneyOutData));
                MerchantNotification::create([
                    'type'      => NotificationConst::MONEY_OUT,
                    'merchant_id'  =>  $data->merchant_id,
                    'message'   => $notification_content,
                ]);
                DB::commit();
            }
            }
            return redirect()->back()->with(['success' => ['Money Out request rejected successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
}
