<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\TemporaryData;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\User\Withdraw\WithdrawMail;
use Exception;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MoneyOutController extends Controller
{
    use ControlDynamicInputFields;
    public function moneyOutInfo(){
        $user = auth()->user();
        $userWallet = UserWallet::where('user_id',$user->id)->get()->map(function($data){
                return[
                    'balance' => getAmount($data->balance,2),
                    'currency' => get_default_currency_code(),
                ];
            })->first();

            $transactions = Transaction::auth()->moneyOut()->latest()->take(5)->get()->map(function($item){
                    $statusInfo = [
                        "success" =>      1,
                        "pending" =>      2,
                        "rejected" =>     3,
                        ];
                    return[
                        'id' => $item->id,
                        'trx' => $item->trx_id,
                        'gateway_name' => $item->currency->gateway->name,
                        'gateway_currency_name' => $item->currency->name,
                        'transaction_type' => $item->type,
                        'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                        'payable' => getAmount($item->payable,2).' '.$item->user_wallet->currency->code,
                        'exchange_rate' => '1 ' .get_default_currency_code().' = '.getAmount($item->currency->rate,2).' '.$item->currency->currency_code,
                        'total_charge' => getAmount($item->charge->total_charge,2).' '.$item->user_wallet->currency->code,
                        'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                        'status' => $item->stringStatus->value ,
                        'date_time' => $item->created_at ,
                        'status_info' =>(object)$statusInfo ,
                        'rejection_reason' =>$item->reject_reason??"" ,

                    ];
            });
            $gateways = PaymentGateway::where('status', 1)->where('slug', PaymentGatewayConst::money_out_slug())->get()->map(function($gateway){
                    $currencies = PaymentGatewayCurrency::where('payment_gateway_id',$gateway->id)->get()->map(function($data){
                    return[
                        'id' => $data->id,
                        'payment_gateway_id' => $data->payment_gateway_id,
                        'type' => $data->gateway->type,
                        'name' => $data->name,
                        'alias' => $data->alias,
                        'currency_code' => $data->currency_code,
                        'currency_symbol' => $data->currency_symbol,
                        'image' => $data->image,
                        'min_limit' => getAmount($data->min_limit,2),
                        'max_limit' => getAmount($data->max_limit,2),
                        'percent_charge' => getAmount($data->percent_charge,2),
                        'fixed_charge' => getAmount($data->fixed_charge,2),
                        'rate' => getAmount($data->rate,2),
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                    ];

                    });
                    return[
                        'id' => $gateway->id,
                        'name' => $gateway->name,
                        'image' => $gateway->image,
                        'slug' => $gateway->slug,
                        'code' => $gateway->code,
                        'type' => $gateway->type,
                        'alias' => $gateway->alias,
                        'supported_currencies' => $gateway->supported_currencies,
                        'input_fields' => $gateway->input_fields??null,
                        'status' => $gateway->status,
                        'currencies' => $currencies

                    ];
            });
            $flutterwave_supported_bank = getFlutterwaveBanks();
            $data =[
                'base_curr'    => get_default_currency_code(),
                'base_curr_rate'    => getAmount(1,2),
                'default_image'    => "public/backend/images/default/default.webp",
                "image_path"  =>  "public/backend/images/payment-gateways",
                'userWallet'   =>   (object)$userWallet,
                'gateways'   => $gateways,
                'flutterwave_supported_bank'   => $flutterwave_supported_bank,
                'transactionss'   =>   $transactions,
            ];
            $message =  ['success'=>['Withdraw Information!']];
            return Helpers::success($data,$message);

    }

    public function moneyOutInsert(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required'
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        if($basic_setting->kyc_verification){
            if( $user->kyc_verified == 0){
                $error = ['error'=>['Please submit kyc information!']];
                return Helpers::error($error);
            }elseif($user->kyc_verified == 2){
                $error = ['error'=>['Please wait before admin approved your kyc information']];
                return Helpers::error($error);
            }elseif($user->kyc_verified == 3){
                $error = ['error'=>['Admin rejected your kyc information, Please re-submit again']];
                return Helpers::error($error);
            }
        }

        $userWallet = UserWallet::where('user_id',$user->id)->where('status',1)->first();
        $gate =PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::money_out_slug());
            $gateway->where('status', 1);
        })->where('alias',$request->gateway)->first();
        if (!$gate) {
            $error = ['error'=>['Invalid Gateway!']];
            return Helpers::error($error);
        }
        $baseCurrency = Currency::default();
        if (!$baseCurrency) {
            $error = ['error'=>['Default Currency Not Setup Yet!']];
            return Helpers::error($error);
        }
        $amount = $request->amount;

        $min_limit =  $gate->min_limit / $gate->rate;
        $max_limit =  $gate->max_limit / $gate->rate;
        if($amount < $min_limit || $amount > $max_limit) {
            $error = ['error'=>['Please follow the transaction limit!']];
            return Helpers::error($error);
        }
        //gateway charge
        $fixedCharge = $gate->fixed_charge;
        $percent_charge =  (((($request->amount * $gate->rate)/ 100) * $gate->percent_charge));
        $charge = $fixedCharge + $percent_charge;
        $conversion_amount = $request->amount * $gate->rate;
        $will_get = $conversion_amount -  $charge;
        //base_cur_charge
        $baseFixedCharge = $gate->fixed_charge *  $baseCurrency->rate;
        $basePercent_charge = ($request->amount / 100) * $gate->percent_charge;
        $base_total_charge = $baseFixedCharge + $basePercent_charge;
        $reduceAbleTotal = $amount;
        if( $reduceAbleTotal > $userWallet->balance){
            $error = ['error'=>['Insuficiant Balance!']];
            return Helpers::error($error);
        }

        $insertData = [
            'user_id'=> $user->id,
            'gateway_name'=> strtolower($gate->gateway->name),
            'gateway_type'=> $gate->gateway->type,
            'wallet_id'=> $userWallet->id,
            'trx_id'=> 'MO'.getTrxNum(),
            'amount' =>  $amount,
            'base_cur_charge' => $base_total_charge,
            'base_cur_rate' => $baseCurrency->rate,
            'gateway_id' => $gate->gateway->id,
            'gateway_currency_id' => $gate->id,
            'gateway_currency' => strtoupper($gate->currency_code),
            'gateway_percent_charge' => $percent_charge,
            'gateway_fixed_charge' => $fixedCharge,
            'gateway_charge' => $charge,
            'gateway_rate' => $gate->rate,
            'conversion_amount' => $conversion_amount,
            'will_get' => $will_get,
            'payable' => $reduceAbleTotal,
        ];
        $identifier = generate_unique_string("transactions","trx_id",16);
        $inserted = TemporaryData::create([
            'type'          => PaymentGatewayConst::TYPEMONEYOUT,
            'identifier'    => $identifier,
            'data'          => $insertData,
        ]);
        if( $inserted){
            $payment_gateway = PaymentGateway::where('id',$gate->payment_gateway_id)->first();
            $payment_informations =[
                'trx' =>  $identifier,
                'gateway_currency_name' =>  $gate->name,
                'request_amount' => getAmount($request->amount,2).' '.get_default_currency_code(),
                'exchange_rate' => "1".' '.get_default_currency_code().' = '.getAmount($gate->rate).' '.$gate->currency_code,
                'conversion_amount' =>  getAmount($conversion_amount,2).' '.$gate->currency_code,
                'total_charge' => getAmount($charge,2).' '.$gate->currency_code,
                'will_get' => getAmount($will_get,2).' '.$gate->currency_code,
                'payable' => getAmount($reduceAbleTotal,2).' '.$gate->currency_code,

            ];
            if($gate->gateway->type == "AUTOMATIC"){
                $url = route('api.withdraw.automatic.confirmed');
                $data =[
                    'payment_informations' => $payment_informations,
                    'gateway_type' => $payment_gateway->type,
                    'gateway_currency_name' => $gate->name,
                    'alias' => $gate->alias,
                    'url' => $url??'',
                    'method' => "post",
                    ];
                    $message =  ['success'=>['Withdraw Money Inserted Successfully']];
                    return Helpers::success($data, $message);
            }else{
                $url = route('api.withdraw.manual.confirmed');
                $data =[
                    'payment_informations' => $payment_informations,
                    'gateway_type' => $payment_gateway->type,
                    'gateway_currency_name' => $gate->name,
                    'alias' => $gate->alias,
                    'details' => $payment_gateway->desc??null,
                    'input_fields' => $payment_gateway->input_fields??null,
                    'url' => $url??'',
                    'method' => "post",
                    ];
                    $message =  ['success'=>['Withdraw Money Inserted Successfully']];
                    return Helpers::success($data, $message);
            }


        }else{
            $error = ['error'=>['Something is wrong!']];
            return Helpers::error($error);
        }
    }
    //manual confirmed
    public function moneyOutConfirmed(Request $request){
        $validator = Validator::make($request->all(), [
            'trx'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $track = TemporaryData::where('identifier',$request->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>["Sorry, your payment information is invalid"]];
            return Helpers::error($error);

        }
        $moneyOutData =  $track->data;
        $gateway = PaymentGateway::where('id', $moneyOutData->gateway_id)->first();
        if($gateway->type != "MANUAL"){
            $error = ['error'=>["Invalid request, it is not manual gateway request"]];
            return Helpers::error($error);
        }
        $payment_fields = $gateway->input_fields ?? [];
        $validation_rules = $this->generateValidationRules($payment_fields);
        $validator2 = Validator::make($request->all(), $validation_rules);
        if ($validator2->fails()) {
            $message =  ['error' => $validator2->errors()->all()];
            return Helpers::error($message);
        }
        $validated = $validator2->validate();
        $get_values = $this->placeValueWithFields($payment_fields, $validated);
            try{
                //send notifications
                $user = auth()->user();
                $user->notify(new WithdrawMail($user,$moneyOutData));
                $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values);
                $this->insertChargesManual($moneyOutData,$inserted_id);
                $this->insertDeviceManual($moneyOutData,$inserted_id);
                $track->delete();
                $message =  ['success'=>['Withdraw money request send to admin successfully']];
                return Helpers::onlysuccess($message);
            }catch(Exception $e) {
                $error = ['error'=>["Sorry,something is wrong"]];
                return Helpers::error($error);
            }

    }
    //automatic confirmed
    public function confirmMoneyOutAutomatic(Request $request){
        $validator = Validator::make($request->all(), [
            'trx'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $track = TemporaryData::where('identifier',$request->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>["Sorry, your payment information is invalid"]];
            return Helpers::error($error);
        }
        $gateway = PaymentGateway::where('id', $track->data->gateway_id)->first();
        if($gateway->type != "AUTOMATIC"){
            $error = ['error'=>["Invalid request, it is not automatic gateway request"]];
            return Helpers::error($error);
        }
        //flutterwave automatic
         if($track->data->gateway_name == "flutterwave"){
            $validator = Validator::make($request->all(), [
                'bank_name' => 'required|numeric|gt:0',
                'account_number' => 'required'
            ]);
            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }

            return $this->flutterwavePay($gateway,$request,$track);


         }else{
            $error = ['error'=>["Sorry,something is wrong try again later"]];
            return Helpers::error($error);
         }

    }

    public function insertRecordManual($moneyOutData,$gateway,$get_values) {
        if($moneyOutData->gateway_type == "AUTOMATIC"){
            $status = 1;
        }else{
            $status = 2;
        }
        $trx_id = $moneyOutData->trx_id ??'MO'.getTrxNum();
        $authWallet = UserWallet::where('id',$moneyOutData->wallet_id)->where('user_id',$moneyOutData->user_id)->first();
        $afterCharge = ($authWallet->balance - ($moneyOutData->amount));
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => auth()->user()->id,
                'user_wallet_id'                => $moneyOutData->wallet_id,
                'payment_gateway_currency_id'   => $moneyOutData->gateway_currency_id,
                'type'                          => PaymentGatewayConst::TYPEMONEYOUT,
                'trx_id'                        => $trx_id,
                'request_amount'                => $moneyOutData->amount,
                'payable'                       => $moneyOutData->will_get,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEMONEYOUT," ")) . " by " .$gateway->name,
                'details'                       => json_encode($get_values),
                'status'                        => $status,
                'created_at'                    => now(),
            ]);
            $this->updateWalletBalanceManual($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>["Sorry,something is wrong"]];
            return Helpers::error($error);
        }
        return $id;
    }

    public function updateWalletBalanceManual($authWalle,$afterCharge) {
        $authWalle->update([
            'balance'   => $afterCharge,
        ]);
    }
    public function insertChargesManual($moneyOutData,$id) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $moneyOutData->gateway_percent_charge,
                'fixed_charge'      => $moneyOutData->gateway_fixed_charge,
                'total_charge'      => $moneyOutData->gateway_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => "Withdraw",
                'message'       => "Your Withdraw request send to admin " .$moneyOutData->amount.' '.get_default_currency_code()." successful",
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>["Sorry,something is wrong"]];
            return Helpers::error($error);
        }
    }

    public function insertDeviceManual($output,$id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        DB::beginTransaction();
        try{
            DB::table("transaction_devices")->insert([
                'transaction_id'=> $id,
                'ip'            => $client_ip,
                'mac'           => $mac,
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>["Sorry,something is wrong"]];
            return Helpers::error($error);
        }
    }

    //fluttrwave
    public function flutterwavePay($gateway,$request, $track){
        $moneyOutData =  $track->data;
        $credentials = $gateway->credentials;
        $data = null;
        $secret_key = getPaymentCredentials($credentials,'Secret key');
        $base_url = getPaymentCredentials($credentials,'Base Url');
        $callback_url = getPaymentCredentials($credentials,'Callback Url');

        $ch = curl_init();
        $url =  $base_url.'/transfers';
        $data = [
            "account_bank" => $request->bank_name,
            "account_number" => $request->account_number,
            "amount" => $moneyOutData->amount,
            "narration" => "Withdraw from wallet",
            "currency" => "NGN",
            "reference" => generateTransactionReference(),
            "callback_url" => $callback_url,
            "debit_currency" => "NGN"
        ];
        $headers = [
            'Authorization: Bearer '.$secret_key,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return back()->with(['error' => [curl_error($ch)]]);
        } else {
            $result = json_decode($response,true);
            if($result['status'] && $result['status'] == 'success'){
                try{

                    //send notifications
                    $user = auth()->user();
                    $user->notify(new WithdrawMail($user,$moneyOutData));
                    $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values = null);
                    $this->insertChargesManual($moneyOutData,$inserted_id);
                    $this->insertDeviceManual($moneyOutData,$inserted_id);
                    $track->delete();
                    $message =  ['success'=>['Withdraw money request send successfully']];
                    return Helpers::onlysuccess($message);
                }catch(Exception $e) {
                    $error = ['error'=>["Sorry,something is wrong"]];
                    return Helpers::error($error);
                }

            }else{
                $error = ['error'=>[$result['message']]];
                return Helpers::error($error);
            }
        }

        curl_close($ch);

    }
    //check flutterwave banks
   public function checkBanks(Request $request){
    $validator = Validator::make($request->all(), [
        'bank_name' => 'required|numeric|gt:0',
        'account_number' => 'required'
    ]);
    if($validator->fails()){
        $error =  ['error'=>$validator->errors()->all()];
        return Helpers::validation($error);
    }
    $bank_account = $request->account_number;
    $bank_code = $request->bank_name;
    $exist = (checkBankAccount($secret_key = null,$bank_account,$bank_code));
    if( $exist['status'] == "success"){
        $data =[
            'bank_info' => $exist['data']
            ];
            $message =  ['success'=>[$exist['message']]];
            return Helpers::success($data, $message);

    }else{
        $message =  ['error'=>[$exist['message']]];
        return Helpers::error($message);
    }

   }
}
