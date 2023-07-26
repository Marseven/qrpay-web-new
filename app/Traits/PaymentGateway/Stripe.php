<?php

namespace App\Traits\PaymentGateway;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\User\AddMoneyController;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\PaymentGatewayApi;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\TemporaryData;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Notifications\User\AddMoney\ApprovedMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Stripe\Charge;
use Stripe\Stripe as StripePackage;
use Stripe\Token;

trait Stripe
{


    public function stripeInit($output = null) {
        if(!$output) $output = $this->output;
        $gatewayAlias = $output['gateway']['alias'];

        $identifier = generate_unique_string("transactions","trx_id",16);
        $this->stripeJunkInsert($identifier);
        Session::put('identifier',$identifier);
        Session::put('output',$output);
        if(userGuard()['type'] == "AGENT"){
            return redirect()->route('agent.add.money.payment', $gatewayAlias);
        }elseif(userGuard()['type'] == "USER"){
            return redirect()->route('user.add.money.payment', $gatewayAlias);
        }

    }

    public function getStripeCredetials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");
        $client_id_sample = ['publishable_key','publishable key','publishable-key'];
        $client_secret_sample = ['secret id','secret-id','secret_id'];

        $client_id = '';
        $outer_break = false;
        foreach($client_id_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $client_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $secret_id = '';
        $outer_break = false;
        foreach($client_secret_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $secret_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        return (object) [
            'publish_key'     => $client_id,
            'secret_key' => $secret_id,

        ];

    }

    public function stripePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function stripeJunkInsert($response) {
        $output = $this->output;
        $data = [
            'gateway'   => $output['gateway']->id,
            'currency'  => $output['currency']->id,
            'amount'    => json_decode(json_encode($output['amount']),true),
            'response'  => $response,
        ];

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::STRIPE,
            'identifier'    => $response,
            'data'          => $data,

        ]);
    }
    public function paymentConfirmed(Request $request){
       $output = session()->get('output');

       $credentials = $this->getStripeCredetials($output);

       $token = session()->get('identifier');
       $data = TemporaryData::where("identifier",$token)->first();
       if(!$data || $data == null){
        return back()->with(['error' => ["Invalid Request!"]]);
       }
        $this->validate($request, [
        'name' => 'required',
        'cardNumber' => 'required',
        'cardExpiry' => 'required',
        'cardCVC' => 'required',
        ]);

        $cc = $request->cardNumber;
        $exp = $request->cardExpiry;
        $cvc = $request->cardCVC;

        $exp = explode("/", $_POST['cardExpiry']);
        $emo = trim($exp[0]);
        $eyr = trim($exp[1]);
        $cnts = round($data->data->amount->total_amount, 2) * 100;

        StripePackage::setApiKey(@$credentials->secret_key);
        StripePackage::setApiVersion("2020-03-02");

        try {
            $token = Token::create(array(
                    "card" => array(
                    "number" => "$cc",
                    "exp_month" => $emo,
                    "exp_year" => $eyr,
                    "cvc" => "$cvc"
                )
            ));
            try {
                $charge = Charge::create(array(
                    'card' => $token['id'],
                    'currency' => $data->data->amount->sender_cur_code,
                    'amount' => $cnts,
                    'description' => 'item',
                ));

                if ($charge['status'] == 'succeeded') {
                    $trx_id = 'AM'.getTrxNum();
                    $this->createTransactionStripe($output,$trx_id);
                    $user = auth()->user();
                    $user->notify(new ApprovedMail($user,$output,$trx_id));

                    session()->forget('identifier');
                    session()->forget('output');
                    return redirect()->route("user.add.money.index")->with(['success' => ['Successfully added money']]);
                }
            } catch (\Exception $e) {

                return back()->with(['error' => [$e->getMessage()]]);
            }
        } catch (\Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }


    }

    public function createTransactionStripe($output, $trx_id) {
        $trx_id =  $trx_id;
        $inserted_id = $this->insertRecordStripe($output,$trx_id);
        $this->insertChargesStripe($output,$inserted_id);
        $this->insertDeviceStripe($output,$inserted_id);
        $this->removeTempDataStripe($output);
    }

    public function insertRecordStripe($output, $trx_id) {

        $trx_id = $trx_id;
        $token = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => auth()->user()->id,
                'user_wallet_id'                => $output['wallet']->id,
                'payment_gateway_currency_id'   => $output['currency']->id,
                'type'                          =>  "ADD-MONEY",
                'trx_id'                        => $trx_id,
                'request_amount'                => $output['amount']->requested_amount,
                'payable'                       => $output['amount']->total_amount,
                'available_balance'             => $output['wallet']->balance + $output['amount']->requested_amount,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEADDMONEY," ")) . " With " . $output['gateway']->name,
                'details'                       => "strip payment successfull",
                'status'                        => true,
                'attribute'                      =>PaymentGatewayConst::SEND,
                'created_at'                    => now(),
            ]);

            $this->updateWalletBalanceStripe($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function updateWalletBalanceStripe($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function insertChargesStripe($output,$id) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $output['amount']->percent_charge,
                'fixed_charge'      => $output['amount']->fixed_charge,
                'total_charge'      => $output['amount']->total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => "Add Money",
                'message'       => "Your Wallet (".$output['wallet']->currency->code.") balance  has been added ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function insertDeviceStripe($output,$id) {
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
            throw new Exception($e->getMessage());
        }
    }

    public function removeTempDataStripe($output) {
        $token = session()->get('identifier');
        TemporaryData::where("identifier",$token)->delete();
    }
    //for api
    public function stripeInitApi($output = null) {
        if(!$output) $output = $this->output;
        $gatewayAlias = $output['gateway']['alias'];
        $identifier = generate_unique_string("transactions","trx_id",16);
        $this->stripeJunkInsert($identifier);
        $response=[
            'trx' => $identifier,
        ];
        return $response;
    }
    public function paymentConfirmedApi(Request $request){

         $validator = Validator::make($request->all(), [
            'track' => 'required',
            'name' => 'required',
            'cardNumber' => 'required',
            'cardExpiry' => 'required',
            'cardCVC' => 'required',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $track = $request->track;
        $data = TemporaryData::where('identifier',$track)->first();

        if(!$data){
            $error = ['error'=>["Sorry, your payment information is invalid"]];
            return Helpers::error($error);
        }
        $payment_gateway_currency = PaymentGatewayCurrency::where('id', $data->data->currency)->first();
        $gateway_request = ['currency' => $payment_gateway_currency->alias, 'amount'    => $data->data->amount->requested_amount];
        $output = PaymentGatewayApi::init($gateway_request)->gateway()->get();
        $credentials = $this->getStripeCredetials($output);

         $cc = $request->cardNumber;
         $exp = $request->cardExpiry;
         $cvc = $request->cardCVC;

         $exp = explode("/", $request->cardExpiry);
         $emo = trim($exp[0]);
         $eyr = trim($exp[1]);
         $cnts = round($data->data->amount->total_amount, 2) * 100;

         StripePackage::setApiKey(@$credentials->secret_key);
         StripePackage::setApiVersion("2020-03-02");

         try {
             $token = Token::create(array(
                     "card" => array(
                     "number" => "$cc",
                     "exp_month" => $emo,
                     "exp_year" => $eyr,
                     "cvc" => "$cvc"
                 )
             ));
             try {
                 $charge = Charge::create(array(
                     'card' => $token['id'],
                     'currency' => $data->data->amount->sender_cur_code,
                     'amount' => $cnts,
                     'description' => 'item',
                 ));

                 if ($charge['status'] == 'succeeded') {
                     $trx_id = 'AM'.getTrxNum();
                     $this->createTransactionStripe($output,$trx_id);
                     $user = auth()->user();
                     $user->notify(new ApprovedMail($user,$output,$trx_id));
                     $data->delete();
                     $message =  ['success'=>['Add Money Successfull']];
                     return Helpers::onlysuccess( $message);
                 }
             } catch (\Exception $e) {
                $error = ['error'=>[$e->getMessage()]];
                return Helpers::error($error);

             }
         } catch (\Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return Helpers::error($error);
         }


     }

}
