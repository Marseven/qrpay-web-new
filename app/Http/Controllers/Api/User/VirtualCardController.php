<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Models\VirtualCard;
use App\Models\VirtualCardApi;
use App\Notifications\User\VirtualCard\CreateMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VirtualCardController extends Controller
{
    protected $api;
    public function __construct()
    {
        $cardApi = VirtualCardApi::first();
        $this->api =  $cardApi;
    }
    public function index()
    {
        $user = auth()->user();
        $myCards = VirtualCard::where('user_id',$user->id)->orderBy('id','DESC')->get()->map(function($data){
            $basic_settings = BasicSettings::first();
            $statusInfo = [
                "block" =>      0,
                "unblock" =>     1,
                ];
            return[
                'id' => $data->id,
                'name' => $data->name,
                'card_pan' => $data->card_pan,
                'card_id' => $data->card_id,
                'expiration' => $data->expiration,
                'cvv' => $data->cvv,
                'amount' => getAmount($data->amount,2),
                'card_back_details' => @$this->api->card_details,
                'site_title' =>@$basic_settings->site_name,
                'site_logo' =>get_logo(@$basic_settings,'dark'),
                'status' => $data->is_active,
                'status_info' =>(object)$statusInfo ,
            ];
        });
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->get()->map(function($data){

            return [
                'id' => $data->id,
                'slug' => $data->slug,
                'title' => $data->title,
                'fixed_charge' => getAmount($data->fixed_charge,2),
                'percent_charge' => getAmount($data->percent_charge,2),
                'min_limit' => getAmount($data->min_limit,2),
                'max_limit' => getAmount($data->max_limit,2),
            ];
        })->first();
        $transactions = Transaction::auth()->virtualCard()->latest()->take(10)->get()->map(function($item){
            $statusInfo = [
                "success" =>      1,
                "pending" =>      2,
                "rejected" =>     3,
                ];
            return[
                'id' => $item->id,
                'trx' => $item->trx_id,
                'transaction_type' => "Virtual Card".'('. @$item->remark.')',
                'request_amount' => getAmount($item->request_amount,2).' '.get_default_currency_code() ,
                'payable' => getAmount($item->payable,2).' '.get_default_currency_code(),
                'total_charge' => getAmount($item->charge->total_charge,2).' '.get_default_currency_code(),
                'card_amount' => getAmount(@$item->details->card_info->amount,2).' '.get_default_currency_code(),
                'card_number' => $item->details->card_info->card_pan,
                'current_balance' => getAmount($item->available_balance,2).' '.get_default_currency_code(),
                'status' => $item->stringStatus->value ,
                'date_time' => $item->created_at ,
                'status_info' =>(object)$statusInfo ,

            ];
        });
        $userWallet = UserWallet::where('user_id',$user->id)->get()->map(function($data){
            return[
                'balance' => getAmount($data->balance,2),
                'currency' => get_default_currency_code(),
            ];
        })->first();

        $data =[
            'base_curr' => get_default_currency_code(),
            'myCard'=> $myCards,
            'userWallet'=>  (object)$userWallet,
            'cardCharge'=>(object)$cardCharge,
            'transactions'   => $transactions,
            ];
            $message =  ['success'=>['Virtual Card']];
            return Helpers::success($data,$message);
    }
    public function charges(){
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->get()->map(function($data){

            return [
                'id' => $data->id,
                'slug' => $data->slug,
                'title' => $data->title,
                'fixed_charge' => getAmount($data->fixed_charge,2),
                'percent_charge' => getAmount($data->percent_charge,2),
                'min_limit' => getAmount($data->min_limit,2),
                'max_limit' => getAmount($data->max_limit,2),
            ];
        })->first();

        $data =[
            'base_curr' => get_default_currency_code(),
            'cardCharge'=>(object)$cardCharge,
            ];
            $message =  ['success'=>['Fess & Charges']];
            return Helpers::success($data,$message);

    }

    public function cardDetails(){
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = request()->card_id;
        $user = auth()->user();
        $myCard = VirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$myCard){
            $error = ['error'=>['Sorry, card not found!']];
            return Helpers::error($error);
        }
        $myCards = VirtualCard::where('card_id',$card_id)->where('user_id',$user->id)->get()->map(function($data){
            $basic_settings = BasicSettings::first();
            $statusInfo = [
                "block" =>      0,
                "unblock" =>     1,
                ];

            return[
                'id' => $data->id,
                'name' => $data->name,
                'account_id' => $data->account_id,
                'card_id' => $data->card_id,
                'card_hash' => $data->card_hash,
                'card_pan' => $data->card_pan,
                'masked_card' => $data->masked_card,
                'expiration' => $data->expiration,
                'cvv' => $data->cvv,
                'card_type' => ucwords($data->card_type),
                'city' => $data->city,
                'state' => $data->state,
                'zip_code' => $data->zip_code,
                'address' => $data->address,
                'amount' => getAmount($data->amount,2),
                'card_back_details' => @$this->api->card_details,
                'site_title' =>@$basic_settings->site_name,
                'site_logo' =>get_logo(@$basic_settings,'dark'),
                'status' => $data->is_active,
                'status_info' =>(object)$statusInfo ,
            ];
        })->first();
        $data =[
            'base_curr' => get_default_currency_code(),
            'myCards'=> $myCards,
            ];
            $message =  ['success'=>['Virtual Card Details']];
            return Helpers::success($data,$message);
    }
    public function cardTransaction() {
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = request()->card_id;
        $user = auth()->user();
        $card = VirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$card){
            $error = ['error'=>['Sorry, card not found!']];
            return Helpers::error($error);
        }
        $id = $card->card_id;
        $emptyMessage  = 'No Transaction Found!';
        $start_date = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-12 month" ) );
        $end_date = date('Y-m-d');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL =>  $this->api->url."/"."virtual-cards/".$id."/transactions?from=".date('Y-m-d',strtotime($start_date))."&to=".$end_date."&index=0&size=2147483647",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->api->secret_key
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $card_trans = json_decode($response, true);
        $vals = [];
        if (isset($card_trans['data']) && $card_trans['data'] != null) {
            $vals = collect($card_trans['data'])->map(function ($item) {
                return [
                    'trx' => $item['id'],
                    'amount' => $item['amount'].' '.get_default_currency_code(),
                    'payment_details' => $item['product'],
                    'reference' => $item['reference'],
                    'gateway_reference' => $item['gateway_reference'],
                    'response_message' => $item['response_message'],
                    'status' => $item['status'],
                    'date' =>  $item['created_at']
                ];
            });
        }

        $data = [
            'cardTransactions' => $vals ? $vals->all() : []
        ];

        $message = ['success' => ['Virtual Card Transactions']];
        return Helpers::success($data, $message);


    }

    public function cardBlock(Request $request){
        $validator = Validator::make($request->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = $request->card_id;
        $user = auth()->user();
        $status = 'block';
        $card = VirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$card){
            $error = ['error'=>['Sorry, invalid request!']];
            return Helpers::error($error);
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->url.'/'."virtual-cards/".$card->card_id."/status/".$status,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " .$this->api->secret_key
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response, true);
        if (isset($result)) {
            if ($result['status'] === 'success' && array_key_exists('data', $result)) {
                $card->is_active = 0;
                $card->save();
                $message =  ['success'=>['Card block successfully!']];
                return Helpers::onlysuccess($message);
            } elseif ($result['status'] === 'error' && $result['message'] === 'Card has been blocked previously') {
                $card->is_active = 0;
                $card->save();
                $error = ['error'=>['Card has been blocked previously']];
                return Helpers::error($error);
            } elseif ($result['status'] === 'error' && $result['message'] === 'Card not found. Please check and try again') {
                $card->terminate = 1;
                $card->save();
                $error = ['error'=>['This Card has been terminated previously.']];
                return Helpers::error($error);
            } else {
                $error = ['error'=>[$result['message']]];
                return Helpers::error($error);
            }
        }

    }
    public function cardUnBlock(Request $request){
        $validator = Validator::make($request->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = $request->card_id;
        $user = auth()->user();
        $status = 'unblock';
        $card = VirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$card){
            $error = ['error'=>['Sorry, invalid request!']];
            return Helpers::error($error);
        }
        $curl = curl_init();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL =>  $this->api->url.'/'."virtual-cards/".$card->card_id."/status/".$status,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->api->secret_key
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response, true);
        if (isset($result)) {
            if ( $result['status'] === 'success' && array_key_exists('data', $result)) {
                $card->is_active = 1;
                $card->save();
                $message =  ['success'=>['Card unblock successfully!']];
                return Helpers::onlysuccess($message);
            } elseif ( $result['status'] === 'error' && $result['message'] === 'card is not blocked' ) {
                $card->is_active = 1;
                $card->save();
                $error = ['error'=>['Card has been unblocked previously']];
                return Helpers::error($error);
            }elseif ( $result['status'] === 'error' && $result['message'] === 'Card not found. Please check and try again' ) {
                $card->terminate = 1;
                $card->save();
                $error = ['error'=>['This Card has been terminated previously.']];
                return Helpers::error($error);
            } else {
                $error = ['error'=>[$result['message']]];
                return Helpers::error($error);
            }
        }

    }
    public function cardBuy(Request $request){
        $validator = Validator::make($request->all(), [
            'card_amount' => 'required|numeric|gt:0',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $user = auth()->user();
        $card = VirtualCard::where('user_id',$user->id)->first();
        if($card){
            $error = ['error'=>['Already have a card!']];
            return Helpers::error($error);
        }


        $amount = $request->card_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            $error = ['error'=>['Wallet not found']];
            return Helpers::error($error);
        }
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            $error = ['error'=>['Default currency not setup yet']];
            return Helpers::error($error);
        }
        $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            $error = ['error'=>['Please follow the transaction limit']];
            return Helpers::error($error);
        }
        //charge calculations
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if($payable > $wallet->balance ){
            $error = ['error'=>['Sorry, insuficiant balance']];
            return Helpers::error($error);
        }
        $currency =$baseCurrency->code;
        $tempId = 'tempId-'. $user->id . time() . rand(6, 100);
        $trx = 'VC-' . time() . rand(6, 100);

        $callBack = route('user.virtual.card.flutterWave.callBack').'?c_user_id='.$user->id.'&c_amount='.  $amount.'&c_temp_id='.$tempId.'&c_trx='.$trx;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->url.'/virtual-cards',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "{\n    \"currency\": \"$currency\",\n    \"amount\":  $amount,\n    \"billing_name\": \"$user->name\",\n   \"callback_url\": \"$callBack/\"\n}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " .$this->api->secret_key
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response, true);
        if (isset($result)){
            if ( $result['status'] === 'success' && array_key_exists('data', $result) ) {
                $values = $result['data'];
                $filteredCollection = array_filter($values, function ($item) use ($currency) {
                    return $item['currency'] === $currency;
                });
                $values =  $filteredCollection;
                $k = array_rand($values);
                $result = (object) $values[$k];
                //Save Card
                $v_card = new VirtualCard();
                $v_card->user_id = $user->id;
                $v_card->card_id = $result->id;
                $v_card->name = $user->fullname;
                $v_card->account_id = $result->account_id;
                $v_card->card_hash = $result->card_hash;
                $v_card->card_pan = $result->card_pan;
                $v_card->masked_card = $result->masked_pan;
                $v_card->cvv = $result->cvv;
                $v_card->expiration = $result->expiration;
                $v_card->card_type = $result->card_type;
                $v_card->name_on_card = $result->name_on_card;
                $v_card->callback = $result->callback_url;
                $v_card->ref_id = $trx;
                $v_card->secret = $trx;
                $v_card->bg = "DeepBlue";
                $v_card->city = $result->city;
                $v_card->state = $result->state;
                $v_card->zip_code = $result->zip_code;
                $v_card->address = $result->address_1;
                $v_card->amount =  $amount;
                $v_card->currency = $currency;
                $v_card->charge =  $total_charge;
                if ($result->is_active) {
                    $v_card->is_active = 1;
                } else {
                    $v_card->is_active = 0;
                }
                $v_card->funding = 1;
                $v_card->terminate = 0;
                $v_card->save();

                $trx_id =  'CB'.getTrxNum();
                $notifyDataSender = [
                    'trx_id'  => $trx_id,
                    'title'  => "Virtual Card (Buy Card)",
                    'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'charges'   => getAmount( $total_charge, 2).' ' .get_default_currency_code(),
                    'card_amount'  => getAmount( $v_card->amount, 2).' ' .get_default_currency_code(),
                    'card_pan'  => $v_card->card_pan,
                    'status'  => "Success",
                  ];
                //sender notifications
                $user->notify(new CreateMail($user,(object)$notifyDataSender));
                $sender = $this->insertCadrBuy( $trx_id,$user,$wallet,$amount, $v_card ,$payable);
                $this->insertBuyCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$v_card->masked_card);
                $message =  ['success'=>['Buy Card Successfully']];
                return Helpers::onlysuccess($message);
            }else {
                $error = ['error'=>[@$result['message']??'Please wait a moment & try again later.']];
                return Helpers::error($error);
            }
        }

    }
    public function cardFundConfirm(Request $request){

        $validator = Validator::make($request->all(), [
            'card_id' => 'required',
            'fund_amount' => 'required|numeric|gt:0',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $user = auth()->user();
        $myCard =  VirtualCard::where('user_id',$user->id)->where('card_id',$request->card_id)->first();

        if(!$myCard){
            $error = ['error'=>['Your Card not found']];
            return Helpers::error($error);
        }

        $amount = $request->fund_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            $error = ['error'=>['Wallet not found']];
            return Helpers::error($error);
        }
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            $error = ['error'=>['Default currency not setup yet']];
            return Helpers::error($error);
        }
        $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            $error = ['error'=>['Please follow the transaction limit']];
            return Helpers::error($error);
        }
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if($payable > $wallet->balance ){
            $error = ['error'=>['Sorry, insuficiant balance']];
            return Helpers::error($error);

        }
        $currency =$baseCurrency->code;
        $tempId = 'tempId-'. $user->id . time() . rand(6, 100);
        $trx = 'VC-' . time() . rand(6, 100);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL =>  $this->api->url."/"."virtual-cards/".$myCard->card_id."/fund",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>"{\n    \"debit_currency\": \"$currency\",\n    \"amount\": $amount\n}",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Authorization: Bearer " .$this->api->secret_key
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        if(!empty($result->status)  && $result->status == "success"){
            //added fund amount to card
            $myCard->amount += $amount;
            $myCard->save();
            $trx_id = 'CF'.getTrxNum();
            $sender = $this->insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
            $this->insertFundCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);
            $message =  ['success'=>['Card Funded Successfully']];
            return Helpers::onlysuccess($message);

        }else{
            $error = ['error'=>[@$result->message??'Please wait a moment & try again later.']];
            return Helpers::error($error);
        }

    }
    // public function cardWithdraw(Request $request){

    //     $validator = Validator::make($request->all(), [
    //         'card_id' => 'required',
    //         'withdraw_amount' => 'required|numeric|gt:0',
    //     ]);
    //     if($validator->fails()){
    //         $error =  ['error'=>$validator->errors()->all()];
    //         return Helpers::validation($error);
    //     }
    //     $user = auth()->user();
    //     $myCard =  VirtualCard::where('user_id',$user->id)->where('card_id',$request->card_id)->first();
    //     if(!$myCard){
    //         $error = ['error'=>['Your Card not found']];
    //         return Helpers::error($error);
    //     }
    //     $amount = $request->withdraw_amount;
    //     $wallet = UserWallet::where('user_id',$user->id)->first();
    //     if(!$wallet){
    //         $error = ['error'=>['Wallet not found']];
    //         return Helpers::error($error);
    //     }
    //     $withdrawCharge = TransactionSetting::where('slug','virtual_card_withdraw')->where('status',1)->first();
    //     $baseCurrency = Currency::default();
    //     $rate = $baseCurrency->rate;
    //     if(!$baseCurrency){
    //         $error = ['error'=>['Default currency not setup yet']];
    //         return Helpers::error($error);
    //     }
    //     $minLimit =  $withdrawCharge->min_limit *  $rate;
    //     $maxLimit =  $withdrawCharge->max_limit *  $rate;
    //     if($amount < $minLimit || $amount > $maxLimit) {
    //         $error = ['error'=>['Please follow the transaction limit']];
    //         return Helpers::error($error);
    //     }

    //     $fixedCharge = $withdrawCharge->fixed_charge *  $rate;
    //     $percent_charge = ($amount / 100) * $withdrawCharge->percent_charge;
    //     $total_charge = $fixedCharge + $percent_charge;
    //     $payable = $total_charge + $amount;
    //     $will_get = $amount * $rate;
    //     if($payable >  $myCard->amount ){
    //         $error = ['error'=>['Sorry, insuficiant balance in this card']];
    //         return Helpers::error($error);
    //     }
    //     $currency =$baseCurrency->code;
    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //     CURLOPT_URL =>  $this->api->url."/"."virtual-cards/".$myCard->card_id."/withdraw",
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_ENCODING => "",
    //     CURLOPT_MAXREDIRS => 10,
    //     CURLOPT_TIMEOUT => 0,
    //     CURLOPT_FOLLOWLOCATION => true,
    //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //     CURLOPT_CUSTOMREQUEST => "POST",
    //     CURLOPT_POSTFIELDS =>"{\n     \"amount\": $amount\n}",
    //     CURLOPT_HTTPHEADER => array(
    //         "Content-Type: application/json",
    //         "Authorization: Bearer " .$this->api->secret_key
    //     ),
    //     ));
    //     $response = curl_exec($curl);
    //     curl_close($curl);
    //     $result = json_decode($response);
    //     if(!empty($result->status)  && $result->status == "success"){
    //         $myCard->amount -= $payable;
    //         $myCard->save();
    //         $trx_id = 'CW'.getTrxNum();
    //         $sender = $this->insertCardWithdraw( $trx_id,$user,$wallet,$amount, $myCard, $payable,$will_get);
    //         $this->insertWithdrawCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);
    //         $message =  ['success'=>['Card Withdraw Successfully']];
    //         return Helpers::onlysuccess($message);

    //     }else{
    //         $error = ['error'=>[@$result->message??'Please wait a moment & try again later.']];
    //         return Helpers::error($error);
    //     }

    // }
    //card buy helper
    public function insertCadrBuy( $trx_id,$user,$wallet,$amount, $v_card ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details =[
            'card_info' =>   $v_card??''
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::CARDBUY," ")),
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($error);
        }
        return $id;
    }

    public function insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$masked_card) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      =>$fixedCharge,
                'total_charge'      =>$total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>"Buy Card ",
                'message'       => "Buy card successful ".$masked_card,
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_BUY,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($error);
        }
    }
    //card fund helper
    public function insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details =[
            'card_info' =>   $myCard??''
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::CARDFUND," ")),
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($error);
        }
        return $id;
    }
    public function insertFundCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$masked_card,$amount) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      =>$fixedCharge,
                'total_charge'      =>$total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>"Card Fund ",
                'message'       => "Card fund successful card: ".$masked_card.' '.getAmount($amount,2).' '.get_default_currency_code(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_FUND,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($error);
        }
    }

        //update user balance
    public function updateSenderWalletBalance($authWalle,$afterCharge) {
        $authWalle->update([
            'balance'   => $afterCharge,
        ]);
    }
}
