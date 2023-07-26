<?php

namespace App\Http\Controllers\User;

use App\Models\Receipient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\Currency;
use App\Models\Admin\ReceiverCounty;
use App\Models\Admin\TransactionSetting;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\User\Remittance\BankTransferMail;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class RemitanceController extends Controller
{
    protected  $trx_id;
    public function __construct()
    {
        $this->trx_id = 'RT'.getTrxNum();
    }
    public function index() {
        $page_title = "Remittance";
        $exchangeCharge = TransactionSetting::where('slug','remittance')->where('status',1)->first();
        $receiverCountries = ReceiverCounty::active()->get();
        $receipients = Receipient::auth()->orderByDesc("id")->paginate(12);
        $transactions = Transaction::auth()->remitance()->latest()->take(5)->get();
        return view('user.sections.remittance.index',compact(
            "page_title",
            'exchangeCharge',
            'receiverCountries',
            'receipients',
            'transactions'
        ));
    }
    public function confirmed(Request $request){
        $request->validate([
            'form_country'               =>'required',
            'to_country'                 =>'required',
            'transaction_type'           =>'required|string',
            'recipient'                  =>'required',
            'send_amount'                =>"required|numeric",
            'receive_amount'             =>'required|numeric',

        ]);
        $exchangeCharge = TransactionSetting::where('slug','remittance')->where('status',1)->first();
        $user = auth()->user();

        $userWallet = UserWallet::where('user_id',$user->id)->first();
        if(!$userWallet){
            return back()->with(['error' => ['Sender wallet not found']]);
        }
        $baseCurrency = Currency::default();
        if(!$baseCurrency){
            return back()->with(['error' => ['Default currency not found']]);
        }
        $to_country = ReceiverCounty::where('id',$request->to_country)->first();
        if(!$to_country){
            return back()->with(['error' => ['Receiver country not found']]);
        }
        $receipient = Receipient::auth()->where("id",$request->recipient)->first();
        if(!$receipient){
            return back()->with(['error' => ['Receipient is invalid']]);
        }

        $base_rate = $baseCurrency->rate;
        $receiver_rate =$to_country->rate;
        $form_country =  $baseCurrency->country;
        $send_amount = $request->send_amount;
        $receive_amount = $request->receive_amount;
        $transaction_type = $request->transaction_type;
        $minLimit =  $exchangeCharge->min_limit *  $base_rate;
        $maxLimit =  $exchangeCharge->max_limit *  $base_rate;
        if($send_amount < $minLimit || $send_amount > $maxLimit) {
            return back()->with(['error' => ['Please follow the transaction limit']]);
        }

        //charge calculations
        $fixedCharge = $exchangeCharge->fixed_charge *  $base_rate;
        $percent_charge = ($send_amount / 100) * $exchangeCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $send_amount;
        //receiver amount
        $receiver_rate = (float) $receiver_rate / (float)$base_rate;
        $receiver_amount = $receiver_rate * $send_amount;
        $receiver_will_get = $receiver_amount;
        if($payable > $userWallet->balance ){
            return back()->with(['error' => ['Sorry, insufficient balance']]);
        }
       
        try{
            if($transaction_type === Str::slug(GlobalConst::TRX_WALLET_TO_WALLET_TRANSFER)){
                $receiver_user =  json_decode($receipient->details);
                $receiver_user =  $receiver_user->id;
                $receiver_wallet = UserWallet::where('user_id',$receiver_user)->first();
                if(!$receiver_wallet){
                    return back()->with(['error' => ['Receiver wallet not found']]);
                }
                $trx_id = $this->trx_id;
                $sender = $this->insertSender( $trx_id,$user,$userWallet,$send_amount,$receiver_will_get,$payable,$receipient,$form_country,$to_country,$transaction_type);
                if($sender){
                     $this->insertSenderCharges( $fixedCharge,$percent_charge, $total_charge, $send_amount,$user,$sender,$receipient,$receiver_user);
                }
                $receiverTrans = $this->insertReceiver( $trx_id,$user,$userWallet,$send_amount,$receiver_will_get,$payable,$receipient,$form_country,$to_country,$transaction_type,$receiver_user,$receiver_wallet);
                if($receiverTrans){
                     $this->insertReceiverCharges(  $fixedCharge,$percent_charge, $total_charge, $send_amount,$user,$receiverTrans,$receipient,$receiver_user);
                }
                session()->forget('remittance_token');

            }else{
                $trx_id = $this->trx_id;
                $notifyData = [
                    'trx_id'  => $trx_id,
                    'title'  => "Send Remittance to @" . $receipient->firstname.' '.@$receipient->lastname." (".@$receipient->mobile_code.@$receipient->mobile.")",
                    'request_amount'  => getAmount($send_amount,4).' '.get_default_currency_code(),
                    'exchange_rate'  => "1 " .get_default_currency_code().' = '.get_amount($to_country->rate,$to_country->code),
                    'charges'   => getAmount( $total_charge, 2).' ' .get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'sending_country'   => @$form_country,
                    'receiving_country'   => @$to_country->country,
                    'receiver_name'  =>  @$receipient->firstname.' '.@$receipient->lastname,
                    'alias'  =>  ucwords(str_replace('-', ' ', @$receipient->alias)),
                    'transaction_type'  =>  @$transaction_type,
                    'receiver_get'   =>  getAmount($receiver_will_get,4).' ' .$to_country->code,
                    'status'  => "Pending",
                  ];
                //sender notifications
                $user->notify(new BankTransferMail($user,(object)$notifyData));
                $sender = $this->insertSender( $trx_id,$user,$userWallet,$send_amount,$receiver_will_get,$payable,$receipient,$form_country,$to_country,$transaction_type);
                if($sender){
                     $this->insertSenderCharges( $fixedCharge,$percent_charge, $total_charge, $send_amount,$user,$sender,$receipient);
                     session()->forget('remittance_token');
                }
            }

            return redirect()->route("user.remittance.index")->with(['success' => ['Remittance Money send successfully']]);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }

    }
      //start transaction helpers
        //serder transaction
    public function insertSender($trx_id,$user,$userWallet,$send_amount,$receiver_will_get,$payable,$receipient,$form_country,$to_country,$transaction_type) {
            $trx_id = $trx_id;
            $authWallet = $userWallet;
            $afterCharge = ($authWallet->balance - $payable);
            $details =[
                'recipient_amount' => $receiver_will_get,
                'receiver' => $receipient,
                'form_country' => $form_country,
                'to_country' => $to_country,
                'remitance_type' => $transaction_type,
                 'sender' => $user,
            ];
            if($transaction_type == Str::slug(GlobalConst::TRX_WALLET_TO_WALLET_TRANSFER)){
                $status = 1;

            }else{
                $status = 2;
            }
            DB::beginTransaction();
            try{
                $id = DB::table("transactions")->insertGetId([
                    'user_id'                       => $user->id,
                    'user_wallet_id'                => $authWallet->id,
                    'payment_gateway_currency_id'   => null,
                    'type'                          => PaymentGatewayConst::SENDREMITTANCE,
                    'trx_id'                        => $trx_id,
                    'request_amount'                => $send_amount,
                    'payable'                       => $payable,
                    'available_balance'             => $afterCharge,
                    'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::SENDREMITTANCE," ")) . " To " .$receipient->fullname,
                    'details'                       => json_encode($details),
                    'attribute'                      =>PaymentGatewayConst::SEND,
                    'status'                        => $status,
                    'created_at'                    => now(),
                ]);
                $this->updateSenderWalletBalance($authWallet,$afterCharge);

                DB::commit();
            }catch(Exception $e) {
                DB::rollBack();
                throw new Exception($e->getMessage());
            }
            return $id;
        }
        public function updateSenderWalletBalance($authWalle,$afterCharge) {
            $authWalle->update([
                'balance'   => $afterCharge,
            ]);
        }
        public function insertSenderCharges($fixedCharge,$percent_charge, $total_charge, $send_amount,$user,$sender,$receipient) {
            DB::beginTransaction();
            try{
                DB::table('transaction_charges')->insert([
                    'transaction_id'    => $sender,
                    'percent_charge'    => $percent_charge,
                    'fixed_charge'      =>$fixedCharge,
                    'total_charge'      =>$total_charge,
                    'created_at'        => now(),
                ]);
                DB::commit();

                //notification
                $notification_content = [
                    'title'         =>"Send Remitance",
                    'message'       => "Send Remitance Request to ".$receipient->fullname.' ' .$send_amount.' '.get_default_currency_code()." successful",
                    'image'         => files_asset_path('profile-default'),
                ];

                UserNotification::create([
                    'type'      => NotificationConst::SEND_REMITTANCE,
                    'user_id'  => $user->id,
                    'message'   => $notification_content,
                ]);
                DB::commit();
            }catch(Exception $e) {
                DB::rollBack();
                throw new Exception($e->getMessage());
            }
        }
        //Receiver Transaction
        public function insertReceiver($trx_id,$user,$userWallet,$send_amount,$receiver_will_get,$payable,$receipient,$form_country,$to_country,$transaction_type,$receiver_user,$receiver_wallet) {

            $trx_id = $trx_id;
            $receiverWallet = $receiver_wallet;
            $recipient_amount = ($receiverWallet->balance + $receiver_will_get);
            $details =[
                'recipient_amount' => $receiver_will_get,
                'receiver' => $receipient,
                'form_country' => $form_country,
                'to_country' => $to_country,
                'remitance_type' => $transaction_type,
                'sender' => $user,
            ];
            DB::beginTransaction();
            try{
                $id = DB::table("transactions")->insertGetId([
                    'user_id'                       => $receiver_user,
                    'user_wallet_id'                => $receiverWallet->id,
                    'payment_gateway_currency_id'   => null,
                    'type'                          => PaymentGatewayConst::SENDREMITTANCE,
                    'trx_id'                        => $trx_id,
                    'request_amount'                => $send_amount,
                    'payable'                       => $payable,
                    'available_balance'             => $recipient_amount,
                    'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::RECEIVEREMITTANCE," ")) . " From " .$user->fullname,
                    'details'                       => json_encode($details),
                    'attribute'                      =>PaymentGatewayConst::RECEIVED,
                    'status'                        => true,
                    'created_at'                    => now(),
                ]);
                $this->updateReceiverWalletBalance($receiverWallet,$recipient_amount);

                DB::commit();
            }catch(Exception $e) {
                DB::rollBack();
                throw new Exception($e->getMessage());
            }
            return $id;
        }
        public function updateReceiverWalletBalance($receiverWallet,$recipient_amount) {
            $receiverWallet->update([
                'balance'   => $recipient_amount,
            ]);
        }
        public function insertReceiverCharges( $fixedCharge,$percent_charge, $total_charge, $send_amount,$user,$receiverTrans,$receipient,$receiver_user) {
            DB::beginTransaction();
            try{
                DB::table('transaction_charges')->insert([
                    'transaction_id'    => $receiverTrans,
                    'percent_charge'    => $percent_charge,
                    'fixed_charge'      =>$fixedCharge,
                    'total_charge'      =>$total_charge,
                    'created_at'        => now(),
                ]);
                DB::commit();

                //notification
                $notification_content = [
                    'title'         =>"Send Remitance",
                    'message'       => "Send Remitance  from ".$user->fullname.' ' .$send_amount.' '.get_default_currency_code()." successful",
                    'image'         => files_asset_path('profile-default'),
                ];

                UserNotification::create([
                    'type'      => NotificationConst::SEND_REMITTANCE,
                    'user_id'  => $receiver_user,
                    'message'   => $notification_content,
                ]);
                DB::commit();
            }catch(Exception $e) {
                DB::rollBack();
                throw new Exception($e->getMessage());
            }
        }
    //end transaction helpers
    public function getToken() {
        $data = request()->all();
        $in['receiver_country'] = $data['receiver_country'];
        $in['transacion_type'] = $data['transacion_type'];
        $in['recipient'] = $data['recipient'];
        $in['sender_amount'] = $data['sender_amount'];
        $in['receive_amount'] = $data['receive_amount'];
        Session::put('remittance_token',$in);
        return response()->json($data);

    }
    public function getRecipientByCountry(Request $request){
        $receiver_country = $request->receiver_country;
        $transacion_type = $request->transacion_type;
        if( $transacion_type != null || $transacion_type != ''){
            $data['recipient'] = Receipient::auth()->where('country', $receiver_country)->where('type',$transacion_type)->get();

        }else{
            $data['recipient'] = Receipient::auth()->where('country', $receiver_country)->get();
        }
        return response()->json($data);
    }
    public function getRecipientByTransType(Request $request){
        $receiver_country = $request->receiver_country;
        $transacion_type = $request->transacion_type;
          $data['recipient'] = Receipient::auth()->where('country', $receiver_country)->where('type',$transacion_type)->get();
        return response()->json($data);
    }
}

